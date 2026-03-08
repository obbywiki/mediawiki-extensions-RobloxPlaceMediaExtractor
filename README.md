# RobloxPlaceMediaExtractor

A MediaWiki extension that creates a special page to extract and download thumbnails and icons from a given Roblox Place ID. Specifically developed to be used on the Obby Wiki to reduce thumbnail archival times.

Extracts Place Universe ID and Name automatically, then retrieves Game Icon and all Thumbnails in WebP format. Name formatting is as follows: `obby_{universe_id}_icon_1.webp` and `obby_{universe_id}_thumb_1.webp`.

## Prerequisites

- MediaWiki 1.43.0 or higher
- PHP 8.1 or higher
- Roblox API access on your server (*.roblox.com)

## Installation

1. Clone inside your `extensions/` directory in your MediaWiki installation:
   ```bash
   git clone https://github.com/obbywiki/mediawiki-extensions-RobloxPlaceMediaExtractor.git RobloxPlaceMediaExtractor
   ```
2. Add the following to your `LocalSettings.php`:
   ```php
   wfLoadExtension( 'RobloxPlaceMediaExtractor' );
   ```
3. Navigate to `Special:RobloxPlaceMediaExtractor` and `Special:Version` on your wiki to verify the extension is installed correctly.

# TODO

- Add support for downloading multiple thumbnails at once
- Add config for changing the download prefix
- Add support for using the place name instead of the universe ID
- Add support for different icon/thumbnail sizes