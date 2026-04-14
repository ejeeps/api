<?php
$path = __DIR__ . '/psgc-provinces.json';
$j = json_decode(file_get_contents($path), true);
if (!is_array($j)) {
    fwrite(STDERR, "Invalid JSON\n");
    exit(1);
}
$rows = [];
foreach ($j as $p) {
    $c = str_replace("'", "''", $p['code']);
    $n = str_replace("'", "''", $p['name']);
    $rows[] = "('$c','$n')";
}
$rows[] = "('130000000','Metro Manila (NCR)')";
echo "INSERT INTO ph_provinces (code, name) VALUES\n" . implode(",\n", $rows) . ";\n";
