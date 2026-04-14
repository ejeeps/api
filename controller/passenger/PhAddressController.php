<?php
/**
 * Philippine address helpers for passenger registration (PSGC online + DB offline + Nominatim).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/connection.php';

const PSGC_BASE = 'https://psgc.gitlab.io/api';
const NCR_REGION = '130000000';

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function http_get_json(string $url): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "Accept: application/json\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
}

function psgc_provinces(): ?array
{
    return http_get_json(PSGC_BASE . '/provinces/');
}

function psgc_cities_for_province(string $provinceCode): ?array
{
    if ($provinceCode === NCR_REGION) {
        return http_get_json(PSGC_BASE . '/regions/' . NCR_REGION . '/cities-municipalities/');
    }
    return http_get_json(PSGC_BASE . '/provinces/' . $provinceCode . '/cities-municipalities/');
}

function merge_ncr_province(array $provinces): array
{
    $out = $provinces;
    $out[] = [
        'code' => NCR_REGION,
        'name' => 'Metro Manila (NCR)',
        'regionCode' => NCR_REGION,
    ];
    usort($out, static function ($a, $b) {
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    return $out;
}

function db_provinces(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT code, name FROM ph_provinces ORDER BY name');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    return array_map(static function ($r) {
        return ['code' => $r['code'], 'name' => $r['name']];
    }, $rows);
}

function db_cities(PDO $pdo, string $provinceCode): array
{
    $stmt = $pdo->prepare('SELECT name FROM ph_cities WHERE province_code = ? ORDER BY name');
    $stmt->execute([$provinceCode]);
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cities = [];
    foreach ($names as $n) {
        $cities[] = ['name' => (string) $n];
    }
    return $cities;
}

function nominatim_search(string $q): array
{
    $params = http_build_query([
        'q' => $q . ', Philippines',
        'format' => 'json',
        'addressdetails' => 1,
        'limit' => 8,
        'countrycodes' => 'ph',
    ]);
    $url = 'https://nominatim.openstreetmap.org/search?' . $params;
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: E-JEEP-PassengerReg/1.0 (contact: local)\r\nAccept-Language: en\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return [];
    }
    $items = json_decode($raw, true);
    if (!is_array($items)) {
        return [];
    }
    $knownNcr = ['manila', 'caloocan', 'las piñas', 'las pinas', 'makati', 'malabon', 'mandaluyong', 'marikina', 'muntinlupa', 'navotas', 'parañaque', 'paranaque', 'pasay', 'pasig', 'pateros', 'quezon city', 'san juan', 'taguig', 'valenzuela'];
    $out = [];
    foreach ($items as $item) {
        $a = $item['address'] ?? [];
        $parts = array_filter([
            $a['house_number'] ?? null,
            $a['road'] ?? null,
        ]);
        if (!$parts && !empty($a['neighbourhood'])) {
            $parts[] = $a['neighbourhood'];
        } elseif (!$parts && !empty($a['suburb'])) {
            $parts[] = $a['suburb'];
        }
        $line1 = trim(implode(' ', $parts));
        $city = $a['city'] ?? $a['town'] ?? $a['municipality'] ?? $a['village'] ?? '';
        if ($city === '' && !empty($a['suburb'])) {
            $city = $a['suburb'];
        }
        $province = $a['state'] ?? $a['province'] ?? $a['region'] ?? '';
        if ($province === '' && $city !== '') {
            $lc = strtolower(str_replace(['city of ', 'city'], '', trim($city)));
            foreach ($knownNcr as $nc) {
                if ($lc === $nc || strpos($lc, $nc) !== false) {
                    $province = 'Metro Manila (NCR)';
                    break;
                }
            }
        }
        $postcode = $a['postcode'] ?? '';
        $out[] = [
            'label' => $item['display_name'] ?? '',
            'address_line1' => $line1,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postcode,
        ];
    }
    return $out;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'provinces':
            $remote = psgc_provinces();
            if ($remote !== null) {
                json_out(['source' => 'psgc', 'provinces' => merge_ncr_province($remote)]);
            }
            $local = db_provinces($pdo);
            if (count($local) === 0) {
                json_out([
                    'source' => 'none',
                    'provinces' => [],
                    'message' => 'Province list unavailable. Run migrations 049–050 or check your internet connection.',
                ], 503);
            }
            json_out(['source' => 'database', 'provinces' => $local]);

        case 'cities':
            $code = preg_replace('/\D/', '', $_GET['province_code'] ?? '');
            if ($code === '') {
                json_out(['source' => 'error', 'cities' => [], 'message' => 'province_code required'], 400);
            }
            $remote = psgc_cities_for_province($code);
            if ($remote !== null) {
                $cities = [];
                foreach ($remote as $x) {
                    $cities[] = ['name' => (string) ($x['name'] ?? '')];
                }
                usort($cities, static function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
                json_out(['source' => 'psgc', 'cities' => $cities]);
            }
            $local = db_cities($pdo, $code);
            json_out(['source' => 'database', 'cities' => $local]);

        case 'geocode':
            $q = trim((string) ($_GET['q'] ?? ''));
            if (strlen($q) < 3) {
                json_out(['source' => 'nominatim', 'results' => []]);
            }
            json_out(['source' => 'nominatim', 'results' => nominatim_search($q)]);

        case 'local_search':
            $q = trim((string) ($_GET['q'] ?? ''));
            if (strlen($q) < 2) {
                json_out(['suggestions' => []]);
            }
            $like = '%' . $q . '%';
            $suggestions = [];

            $st = $pdo->prepare('SELECT code, name FROM ph_provinces WHERE name LIKE ? ORDER BY name LIMIT 25');
            $st->execute([$like]);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $suggestions[] = [
                    'label' => $r['name'] . ' (province)',
                    'province' => $r['name'],
                    'city' => '',
                    'province_code' => $r['code'],
                ];
            }

            $st2 = $pdo->prepare(
                'SELECT c.name AS city_name, p.name AS province_name, p.code AS province_code
                 FROM ph_cities c
                 INNER JOIN ph_provinces p ON p.code = c.province_code
                 WHERE c.name LIKE ?
                 ORDER BY c.name
                 LIMIT 25'
            );
            $st2->execute([$like]);
            while ($r = $st2->fetch(PDO::FETCH_ASSOC)) {
                $suggestions[] = [
                    'label' => $r['city_name'] . ', ' . $r['province_name'],
                    'province' => $r['province_name'],
                    'city' => $r['city_name'],
                    'province_code' => $r['province_code'],
                ];
            }
            json_out(['suggestions' => $suggestions]);

        default:
            json_out(['error' => 'Invalid action. Use provinces, cities, geocode, or local_search.'], 400);
    }
} catch (Throwable $e) {
    json_out(['error' => $e->getMessage()], 500);
}
