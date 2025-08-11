<?php
// app/Helpers/ThumbnailHelper.php

namespace App\Helpers;

use Embed\Embed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThumbnailHelper
{
    /**
     * Ambil thumbnail dari URL
     *
     * @param string $url
     * @return string|null Path thumbnail atau null jika gagal
     */
    public static function fetchThumbnail($url)
    {
        try {
            // Gunakan embed/embed untuk mendapatkan metadata
            $embed = new Embed();
            $info = $embed->get($url);
            
            // Ambil URL thumbnail/gambar dari metadata
            $imageUrl = $info->image;
            
            // Jika gambar ditemukan, download dan simpan
            if ($imageUrl) {
                // Download gambar
                $imageContents = Http::get($imageUrl)->body();
                
                // Generate nama file unik
                $filename = 'thumbnails/' . Str::random(40) . '.jpg';
                
                // Simpan gambar
                Storage::disk('public')->put($filename, $imageContents);
                return $filename;
            }
        } catch (\Exception $e) {
            // Log error
            // Log::error('Error fetching thumbnail: ' . $e->getMessage());
        }
        
        return null;
    }
    // Tambahkan method ini di ThumbnailHelper

    public static function getUrlMetadata($url)
    {
        try {
            $embed = new Embed();
            $info = $embed->get($url);
            
            return [
                'title' => $info->title,
                'description' => $info->description,
                'image' => $info->image,
                'favicon' => $info->favicon,
                'url' => $url
            ];
        } catch (\Exception $e) {
            // Log::error('Error fetching URL metadata: ' . $e->getMessage());
            return [
                'title' => '',
                'description' => '',
                'image' => '',
                'favicon' => '',
                'url' => $url
            ];
        }
    }
}