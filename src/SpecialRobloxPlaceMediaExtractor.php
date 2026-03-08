<?php

namespace MediaWiki\Extension\RobloxPlaceMediaExtractor;

use SpecialPage;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;

class SpecialRobloxPlaceMediaExtractor extends SpecialPage {
    public function __construct() {
        parent::__construct( 'RobloxPlaceMediaExtractor' );
    }

    public function execute( $subPage ) {
        $out = $this->getOutput();
        $request = $this->getRequest();
        
        $out->addModuleStyles( [ 'ext.RobloxPlaceMediaExtractor.styles' ] );

        if ( $request->getVal('dl_url') && $request->getVal('dl_name') ) {
            $this->handleProxyDownload( $request->getVal('dl_url'), $request->getVal('dl_name') );
            return;
        }

        $skinName = strtolower( $this->getSkin()->getSkinName() );
        if ( $skinName !== 'citizen' ) {
            $this->setHeaders();
            $this->getOutput()->setPageTitle( "Skin Not Supported" );
            $this->getOutput()->addHTML( Html::errorBox( "This special page is optimized for the Citizen skin. Current skin: " . $skinName . " Please switch to the Citizen skin in user preferences or by appending ?usekin=Citizen to the URL." ) );
            return;
        }

        $this->setHeaders();
        $this->outputHeader();

        $placeId = $request->getVal( 'placeid' );
        $universeIdInput = $request->getVal( 'universeid' );

        $introHtml = Html::openElement( 'div', [ 'class' => 'roblox-extractor-seo-content' ] );
        $introHtml .= Html::element( 'h2', [], 'How to Use the Roblox Media Extractor' );
        $introHtml .= Html::element( 'p', [], 'The Roblox Place Media Extractor allows you to quickly and consistently download high-resolution game icons and thumbnails directly from a Roblox place or universe. This tool is ideal for archiving game assets, analyzing Roblox thumbnails and imagery, or building wiki documentation. This tool was designed for the' );
        $introHtml .= Html::element( 'a', [ 'href' => 'https://obbywiki.com/wiki/Home' ], 'Obby Wiki.' );
        $introHtml .= Html::openElement( 'ul' );
        $introHtml .= Html::element( 'li', [], 'Find the Place URL or ID (located in the URL of any Roblox game page) or the Universe ID.' );
        $introHtml .= Html::element( 'li', [], 'Enter the URL or ID in the search form below and click the "Extract" button.' );
        $introHtml .= Html::element( 'li', [], 'Preview and download the original, uncompressed webp images directly to your device.' );
        $introHtml .= Html::closeElement( 'ul' );
        $introHtml .= Html::closeElement( 'div' );

        $out->addHTML( $introHtml );

        $form = Html::openElement( 'form', [
            'method' => 'get',
            'action' => wfScript(),
            'class' => 'roblox-extractor-form'
        ] );
        $form .= Html::hidden( 'title', $this->getPageTitle()->getPrefixedDBkey() );
        
        $form .= Html::openElement( 'div', [ 'class' => 'roblox-extractor-input-group' ] );
        $form .= Html::element( 'label', [ 'for' => 'placeid' ], $this->msg( 'robloxplacemediaextractor-placeid' )->text() );
        // Removed 'required' so they can use either or
        $form .= Html::input( 'placeid', $placeId, 'text', [ 'id' => 'placeid', 'placeholder' => 'e.g. https://www.roblox.com/games/1818/...' ] );
        $form .= Html::closeElement( 'div' );
        
        $form .= Html::openElement( 'div', [ 'class' => 'roblox-extractor-input-group' ] );
        $form .= Html::element( 'label', [ 'for' => 'universeid' ], $this->msg( 'robloxplacemediaextractor-universeid' )->text() );
        $form .= Html::input( 'universeid', $universeIdInput, 'text', [ 'id' => 'universeid', 'placeholder' => 'e.g. 13058' ] );
        $form .= Html::closeElement( 'div' );
        
        $form .= Html::input( 'wpExtract', $this->msg( 'robloxplacemediaextractor-submit' )->text(), 'submit', [ 'class' => 'mw-ui-button mw-ui-progressive' ] );
        $form .= Html::closeElement( 'form' );

        $out->addHTML( $form );

        if ( $placeId || $universeIdInput ) {
            $this->processExtraction( $placeId, $universeIdInput );
        }
    }

    private function processExtraction( ?string $placeId, ?string $universeIdInput ): void {
        $out = $this->getOutput();
        
        // Auto-extract Place ID from URL if provided
        if ( $placeId && strpos( $placeId, 'roblox.com' ) !== false ) {
            if ( preg_match( '/roblox\.com\/games\/(\d+)/i', $placeId, $matches ) ) {
                $placeId = $matches[1];
            }
        }

        if ( ($placeId && !is_numeric($placeId)) || ($universeIdInput && !is_numeric($universeIdInput)) ) {
            $out->addHTML( Html::errorBox( $this->msg( 'robloxplacemediaextractor-error-invalid-id' )->text() ) );
            return;
        }

        $httpFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();

        $universeId = null;
        $rawGameName = "";
        
        if ( $universeIdInput ) {
            $universeId = $universeIdInput;
            $rawGameName = "Universe " . $universeId;
            $safeName = "obby_" . $universeId;
        } else {
            // get universe from place ID if applicable
            $detailsUrl = "https://apis.roblox.com/universes/v1/places/" . urlencode((string)$placeId) . "/universe";
            $req = $httpFactory->create($detailsUrl, ['method' => 'GET'], __METHOD__);
            $status = $req->execute();

            if (!$status->isOK()) {
                $out->addHTML( Html::errorBox( $this->msg( 'robloxplacemediaextractor-error-api' )->text() ) );
                return;
            }

            $details = json_decode($req->getContent(), true);
            if (empty($details) || !isset($details['universeId'])) {
                $out->addHTML( Html::errorBox( "Invalid response or place not found. If using a URL, ensure it is a direct game link." ) );
                return;
            }

            $universeId = $details['universeId'];
            $rawGameName = "Place " . $placeId; // replace if universe name is found efficiently
            $safeName = "obby_" . $universeId;
        }

        // icon (prioritize webp)
        $iconUrl = "https://thumbnails.roblox.com/v1/games/icons?universeIds=" . urlencode((string)$universeId) . "&returnPolicy=PlaceHolder&size=512x512&format=webp&isCircular=false";
        $reqIcon = $httpFactory->create($iconUrl, ['method' => 'GET'], __METHOD__);
        $statusIcon = $reqIcon->execute();
        $iconData = '';
        if ($statusIcon->isOK()) {
            $resp = json_decode($reqIcon->getContent(), true);
            if (!empty($resp['data'][0]['imageUrl'])) {
                $iconData = $resp['data'][0]['imageUrl'];
            }
        }

        // get thumbnail IDs then thumbnails
        $mediaUrl = "https://games.roblox.com/v2/games/" . urlencode((string)$universeId) . "/media?fetchAllExperienceRelatedMedia=false";
        $reqMedia = $httpFactory->create($mediaUrl, ['method' => 'GET'], __METHOD__);
        $statusMedia = $reqMedia->execute();
        $thumbs = [];
        if ($statusMedia->isOK()) {
            $mediaResp = json_decode($reqMedia->getContent(), true, 512, JSON_BIGINT_AS_STRING);
            $imageIds = [];
            if (!empty($mediaResp['data'])) {
                foreach ($mediaResp['data'] as $mediaItem) {
                    if (
                        isset($mediaItem['assetType']) && $mediaItem['assetType'] === 'Image' &&
                        isset($mediaItem['imageId']) &&
                        isset($mediaItem['assetTypeId']) && ($mediaItem['assetTypeId'] === 1 || $mediaItem['assetTypeId'] === '1') &&
                        isset($mediaItem['approved']) && $mediaItem['approved'] === true
                    ) {
                        $imageIds[] = $mediaItem['imageId'];
                    }
                }
            }

            if (!empty($imageIds)) {
                $thumbIdsStr = implode(',', $imageIds);
                // get thumbnails
                $thumbUrl = "https://thumbnails.roblox.com/v1/games/" . urlencode((string)$universeId) . "/thumbnails?thumbnailIds=" . urlencode($thumbIdsStr) . "&size=768x432&format=Webp&isCircular=false";
                
                $reqThumb = $httpFactory->create($thumbUrl, ['method' => 'GET'], __METHOD__);
                $statusThumb = $reqThumb->execute();
                if ($statusThumb->isOK()) {
                    $thumbResp = json_decode($reqThumb->getContent(), true);
                    if (!empty($thumbResp['data'])) {
                        foreach ($thumbResp['data'] as $thumb) {
                            if (isset($thumb['state']) && $thumb['state'] === 'Completed' && !empty($thumb['imageUrl'])) {
                                $thumbs[] = $thumb['imageUrl'];
                            }
                        }
                    }
                }
            }
        }

        $out->addHTML( Html::element('h3', [], "Media for: " . $rawGameName) );
        $out->addHTML( Html::element('p', [], "Universe ID: " . $universeId) );

        if ($iconData) {
            $out->addHTML( Html::element('h4', [], "Game Icon") );
            $html = "<div class='roblox-extractor-results'>";
            $fn = "{$safeName}_icon.webp"; // webps when available
            $html .= $this->createMediaCard($iconData, $fn, "Icon", "512x512");
            $html .= "</div>";
            $out->addHTML( $html );
        }
        
        if (!empty($thumbs)) {
            $out->addHTML( Html::element('h4', [], "Thumbnails") );
            $html = "<div class='roblox-extractor-results'>";
            foreach ($thumbs as $idx => $url) {
                $num = $idx + 1;
                $fn = "{$safeName}_thumb_{$num}.webp"; // webps when available
                $html .= $this->createMediaCard($url, $fn, "Thumbnail {$num}", "768x432");
            }
            $html .= "</div>";
            $out->addHTML( $html );
        }

        if (empty($iconData) && empty($thumbs)) {
            $out->addHTML( "<p>No media found for this place.</p>" );
        }
    }

    private function createMediaCard(string $url, string $filename, string $title, string $dimensions = ""): string {
        $proxyUrl = $this->getPageTitle()->getLocalURL([
            'dl_url' => $url,
            'dl_name' => $filename
        ]);

        $html = "<div class='roblox-extractor-card'>";
        $html .= "<div class='roblox-extractor-card-header'>";
        $html .= "<h4>" . htmlspecialchars($title) . "</h4>";
        if ($dimensions) {
            $html .= "<span class='roblox-extractor-dimensions'>" . htmlspecialchars($dimensions) . "</span>";
        }
        $html .= "</div>";
        $html .= "<img src='" . htmlspecialchars($url) . "' alt=''/>";
        $html .= "<div class='roblox-extractor-card-bottom'>";
        $html .= "<span>" . htmlspecialchars($filename) . "</span>";
        $html .= "<a href='" . htmlspecialchars($proxyUrl) . "' class='mw-ui-button mw-ui-progressive' download='" . htmlspecialchars($filename) . "'>Download</a>";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }

    private function handleProxyDownload(string $url, string $name): void {
        // safety domain check
        if (!preg_match('/^https:\/\/[a-z0-9\-]+\.rbxcdn\.com\//i', $url) && !preg_match('/^https:\/\/[a-z0-9\-]+\.roblox\.com\//i', $url)) {
            die("Invalid URL source. Please try again later or contact support.");
        }

        $httpFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
        $req = $httpFactory->create($url, ['method' => 'GET'], __METHOD__);
        $status = $req->execute();
        
        if ($status->isOK()) {
            // disable output to prevent MW skin from printing
            $this->getOutput()->disable();
            
            $content = $req->getContent();
            $mime = 'image/webp'; // we explicitly request webp in the API
            
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . basename($name) . '"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit;
        } else {
            die("Failed to fetch image. Please try again later or contact support.");
        }
    }
}
