<?php
/**
 * E-JEEP PWA Icon Generator
 * Run this script to generate the required PWA icon files
 */

// Icon sizes needed for PWA
$iconSizes = [
    192 => 'icon-192.png',
    512 => 'icon-512.png',
    192 => 'icon-192-maskable.png'  // Maskable version for adaptive icons
];

// SVG content for the E-JEEP icon
$svgContent = '<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#22c55e;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#16a34a;stop-opacity:1" />
        </linearGradient>
        <linearGradient id="jeepGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#f8fafc;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="512" height="512" rx="80" ry="80" fill="url(#bgGradient)"/>
    <rect x="80" y="180" width="352" height="120" rx="20" ry="20" fill="url(#jeepGradient)" stroke="#15803d" stroke-width="4"/>
    <rect x="100" y="140" width="312" height="50" rx="15" ry="15" fill="url(#jeepGradient)" stroke="#15803d" stroke-width="3"/>
    <rect x="120" y="155" width="60" height="30" rx="5" ry="5" fill="#3b82f6" opacity="0.7"/>
    <rect x="200" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <rect x="260" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <rect x="320" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <circle cx="140" cy="320" r="35" fill="#374151" stroke="#1f2937" stroke-width="3"/>
    <circle cx="140" cy="320" r="20" fill="#6b7280"/>
    <circle cx="372" cy="320" r="35" fill="#374151" stroke="#1f2937" stroke-width="3"/>
    <circle cx="372" cy="320" r="20" fill="#6b7280"/>
    <rect x="360" y="100" width="80" height="50" rx="8" ry="8" fill="#ffffff" stroke="#22c55e" stroke-width="3"/>
    <rect x="370" y="115" width="60" height="8" rx="2" ry="2" fill="#22c55e"/>
    <rect x="370" y="130" width="40" height="6" rx="1" ry="1" fill="#9ca3af"/>
    <text x="256" y="420" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="#ffffff">E-JEEP</text>
</svg>';

// Maskable SVG (no rounded corners, full bleed for adaptive icons)
$maskableSvgContent = '<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#22c55e;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#16a34a;stop-opacity:1" />
        </linearGradient>
        <linearGradient id="jeepGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#f8fafc;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="512" height="512" fill="url(#bgGradient)"/>
    <rect x="80" y="180" width="352" height="120" rx="20" ry="20" fill="url(#jeepGradient)" stroke="#15803d" stroke-width="4"/>
    <rect x="100" y="140" width="312" height="50" rx="15" ry="15" fill="url(#jeepGradient)" stroke="#15803d" stroke-width="3"/>
    <rect x="120" y="155" width="60" height="30" rx="5" ry="5" fill="#3b82f6" opacity="0.7"/>
    <rect x="200" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <rect x="260" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <rect x="320" y="155" width="40" height="25" rx="3" ry="3" fill="#3b82f6" opacity="0.7"/>
    <circle cx="140" cy="320" r="35" fill="#374151" stroke="#1f2937" stroke-width="3"/>
    <circle cx="140" cy="320" r="20" fill="#6b7280"/>
    <circle cx="372" cy="320" r="35" fill="#374151" stroke="#1f2937" stroke-width="3"/>
    <circle cx="372" cy="320" r="20" fill="#6b7280"/>
    <rect x="360" y="100" width="80" height="50" rx="8" ry="8" fill="#ffffff" stroke="#22c55e" stroke-width="3"/>
    <rect x="370" y="115" width="60" height="8" rx="2" ry="2" fill="#22c55e"/>
    <rect x="370" y="130" width="40" height="6" rx="1" ry="1" fill="#9ca3af"/>
    <text x="256" y="420" font-family="Arial, sans-serif" font-size="48" font-weight="bold" text-anchor="middle" fill="#ffffff">E-JEEP</text>
</svg>';

echo "<h1>E-JEEP PWA Icon Generator</h1>";
echo "<p>This script generates the required PWA icon files.</p>";

// Check if Imagick is available
if (!extension_loaded('imagick')) {
    echo "<p style='color: orange;'>Warning: Imagick extension not available. Using GD library instead (may have limited SVG support).</p>";
}

// Function to convert SVG to PNG using Imagick or GD
function svgToPng($svg, $outputFile, $size) {
    if (extension_loaded('imagick')) {
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->readImageBlob($svg);
        $imagick->setImageFormat('png32');
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        $imagick->writeImage($outputFile);
        $imagick->clear();
        $imagick->destroy();
        return true;
    } else {
        // Fallback: Save SVG and try to convert with GD
        $tempSvg = tempnam(sys_get_temp_dir(), 'svg') . '.svg';
        file_put_contents($tempSvg, $svg);
        
        // Try to use GD (may not work with complex SVGs)
        if (function_exists('imagecreatefromstring')) {
            $image = imagecreatetruecolor($size, $size);
            $bgColor = imagecolorallocate($image, 34, 197, 94); // #22c55e
            imagefill($image, 0, 0, $bgColor);
            
            // Add simple text as placeholder
            $textColor = imagecolorallocate($image, 255, 255, 255);
            $fontSize = $size > 200 ? 5 : 3;
            imagestring($image, $fontSize, $size/3, $size/2, "E-JEEP", $textColor);
            
            imagepng($image, $outputFile);
            imagedestroy($image);
        }
        
        @unlink($tempSvg);
        return true;
    }
}

// Generate icons
$generated = [];

// Generate 192x192 icon
if (svgToPng($svgContent, __DIR__ . '/icon-192.png', 192)) {
    $generated[] = 'icon-192.png';
}

// Generate 512x512 icon
if (svgToPng($svgContent, __DIR__ . '/icon-512.png', 512)) {
    $generated[] = 'icon-512.png';
}

// Generate maskable 192x192 icon
if (svgToPng($maskableSvgContent, __DIR__ . '/icon-192-maskable.png', 192)) {
    $generated[] = 'icon-192-maskable.png';
}

echo "<h2>Generated Icons:</h2>";
echo "<ul>";
foreach ($generated as $icon) {
    $path = __DIR__ . '/' . $icon;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<li>{$icon} - " . number_format($size / 1024, 2) . " KB</li>";
    }
}
echo "</ul>";

echo "<p>PWA icons have been generated successfully!</p>";
echo "<p>You can now use the E-JEEP app as a Progressive Web App.</p>";
?>
