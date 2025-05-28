<?php
// app/Http/Controllers/PreviewController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class PreviewController extends Controller
{
    public function getPreview(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $url = $request->input('url');

        try {
            $metadata = $this->getWebsiteMetadata($url);

            if (!empty($metadata['image'])) {
                Log::info('Preview image found: ' . $metadata['image']);
                return response()->json([
                    'success' => true,
                    'image' => $metadata['image'],
                    'title' => $metadata['title'],
                    'description' => $metadata['description'],
                    'favicon' => $metadata['favicon'],
                    'url' => $url
                ]);
            } else {
                Log::warning('No preview image found for URL: ' . $url);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menemukan gambar dari URL tersebut.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching preview: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan preview: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getWebsiteMetadata($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        $metadata = [
            'url' => $url,
            'title' => '',
            'description' => '',
            'image' => '',
            'favicon' => ''
        ];

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
                'timeout' => 10
            ]
        ]);
        $html = @file_get_contents($url, false, $context);
        if (!$html) {
            Log::error('Failed to fetch URL content: ' . $url);
            return $metadata;
        }

        $doc = new DOMDocument();
        if (@$doc->loadHTML($html) === false) {
            Log::error('Failed to parse HTML for URL: ' . $url);
            return $metadata;
        }
        $xpath = new DOMXPath($doc);

        $titleTags = $xpath->query('//title');
        if ($titleTags->length > 0) {
            $metadata['title'] = $titleTags->item(0)->nodeValue;
        }

        $metaTags = $xpath->query('//meta');
        foreach ($metaTags as $meta) {
            $property = $meta->getAttribute('property');
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');

            if ($property == 'og:image' || $name == 'og:image') {
                $metadata['image'] = $content;
            }
            if ($name == 'twitter:image' && empty($metadata['image'])) {
                $metadata['image'] = $content;
            }
            if ($name == 'description' || $property == 'og:description') {
                $metadata['description'] = $content;
            }
        }

        if (empty($metadata['image'])) {
            $imgTags = $xpath->query('//img');
            $largestImg = ['src' => '', 'size' => 0];

            foreach ($imgTags as $img) {
                $src = $img->getAttribute('src');
                $width = $img->getAttribute('width');
                $height = $img->getAttribute('height');

                $size = 0;
                if ($width && $height) {
                    $size = $width * $height;
                }

                if ($size > $largestImg['size'] && $size > 10000) {
                    $largestImg['src'] = $src;
                    $largestImg['size'] = $size;
                }
            }

            if (!empty($largestImg['src'])) {
                if (strpos($largestImg['src'], 'http') !== 0) {
                    $base = parse_url($url);
                    $largestImg['src'] = $base['scheme'] . '://' . $base['host'] .
                        (isset($base['port']) ? ':' . $base['port'] : '') .
                        (strpos($largestImg['src'], '/') === 0 ? $largestImg['src'] : '/' . $largestImg['src']);
                }
                $metadata['image'] = $largestImg['src'];
            }
        }

        $faviconPath = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/favicon.ico';
        $metadata['favicon'] = $faviconPath;

        if (!empty($metadata['image']) && strpos($metadata['image'], 'http') !== 0) {
            $base = parse_url($url);
            $metadata['image'] = $base['scheme'] . '://' . $base['host'] .
                (isset($base['port']) ? ':' . $base['port'] : '') .
                (strpos($metadata['image'], '/') === 0 ? $metadata['image'] : '/' . $metadata['image']);
        }

        return $metadata;
    }
}