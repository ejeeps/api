# E-JEEP PWA Icons

This directory contains all the icons needed for the Progressive Web App (PWA) functionality.

## Icon Generation

To generate all required icon sizes:

1. Open `generate-icons.html` in your browser
2. The page will automatically generate all icon sizes from the SVG template
3. Click the download buttons to save each icon size
4. Place the downloaded files in this directory

## Required Icon Sizes

The following icon sizes are needed for full PWA compatibility:

### Standard Icons
- 16x16 (favicon)
- 32x32 (favicon)
- 192x192 (Android Chrome)
- 512x512 (Android Chrome)

### Apple Touch Icons
- 57x57 (iPhone original)
- 60x60 (iPhone)
- 72x72 (iPad)
- 76x76 (iPad)
- 114x114 (iPhone retina)
- 120x120 (iPhone retina)
- 144x144 (iPad retina)
- 152x152 (iPad retina)
- 180x180 (iPhone 6 Plus)

### Microsoft Tiles
- 70x70 (small tile)
- 150x150 (medium tile)
- 310x310 (large tile)
- 310x150 (wide tile)

### Additional Sizes
- 96x96 (Android)
- 128x128 (Chrome Web Store)
- 384x384 (Android splash)

## Manual Creation

If you prefer to create icons manually:

1. Use the `icon-template.svg` as a base
2. Export to PNG at each required size
3. Ensure proper naming convention (e.g., `icon-192x192.png`)
4. Optimize images for web delivery

## Current Status

- ✅ SVG template created
- ✅ Icon generator tool created
- ⏳ PNG files need to be generated (use generate-icons.html)

## Notes

- All icons should maintain the E-JEEP branding
- Icons should be optimized for different display contexts
- Test icons on various devices and browsers
- Consider creating maskable icons for Android adaptive icons