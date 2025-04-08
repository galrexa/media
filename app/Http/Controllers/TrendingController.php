<?php
// app/Http/Controllers/TrendingController.php

namespace App\Http\Controllers;

use App\Models\Trending;
use App\Models\MediaSosial;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrendingController extends Controller
{
    public function __construct()
    {
        // $this->middleware('role:admin,editor')->except(['index', 'show']);
        // $this->middleware('auth');
    }
    
    /**
     * Menampilkan daftar trending.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil data dari database untuk Trending X
        $trendingX = Trending::whereHas('mediaSosial', function($query) {
            $query->where('nama', 'X');
        })
        ->orderBy('tanggal', 'desc')
        ->paginate(10);

        // Ambil data dari RSS feed untuk Trending Google
        $trendingGoogle = $this->fetchGoogleTrends();

        return view('trending.index', compact('trendingGoogle', 'trendingX'));
    }

    /**
     * Mengambil data trending dari RSS feed Google Trends.
     *
     * @return array
     */
    private function fetchGoogleTrends()
    {
        $rss_url = "https://trends.google.com/trending/rss?geo=ID";
        
        // Inisialisasi cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $rss_content = curl_exec($ch);
        $trendingGoogle = [];

        if (!curl_errno($ch)) {
            $rss = @simplexml_load_string($rss_content);
            
            if ($rss !== false) {
                $counter = 1;
                foreach ($rss->channel->item as $item) {
                    $title = (string)$item->title;
                    $ht = $item->children('ht', true);
                    $traffic = (string)$ht->approx_traffic;
                    $pubDate = Carbon::parse((string)$item->pubDate);
                    $url = isset($ht->news_item[0]->news_item_url) ? (string)$ht->news_item[0]->news_item_url : '#';

                    $trendingGoogle[] = [
                        'judul' => $title,
                        'tanggal' => $pubDate,
                        'url' => $url,
                        'traffic' => $traffic,
                        'rank' => $counter,
                    ];
                    
                    $counter++;
                }
            }
        }
        
        curl_close($ch);
        return $trendingGoogle;
    }

    // Fungsi lain seperti create, store, destroy tetap sama
    public function create()
    {
        $mediaSosials = MediaSosial::all();
        return view('trending.create', compact('mediaSosials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'media_sosial_id' => 'required|exists:media_sosials,id',
            'tanggal' => 'required|date',
            'judul' => 'required|string|max:255',
            'url' => 'required|url|max:255',
        ]);

        $trending = Trending::create([
            'media_sosial_id' => $validated['media_sosial_id'],
            'tanggal' => $validated['tanggal'],
            'judul' => $validated['judul'],
            'url' => $validated['url'],
        ]);

        return redirect()->route('trending.index')
                         ->with('success', 'Trending berhasil dibuat!');
    }

    public function destroy(Trending $trending)
    {
        $trending->delete();
        
        return redirect()->route('trending.index')
                        ->with('success', 'Trending berhasil dihapus!');
    }
}