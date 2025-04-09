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

        // Ambil data trending X dari Trends24.in
        $trendingXLive = $this->fetchTrends24();

        // Ambil data dari RSS feed untuk Trending Google
        $trendingGoogle = $this->fetchGoogleTrends();

        return view('trending.index', compact('trendingGoogle', 'trendingX', 'trendingXLive'));
    }

    /**
     * Menampilkan halaman test untuk trending X dari Trends24.in
     *
     * @return \Illuminate\View\View
     */
    public function test()
    {
        // Mengambil data trending dari Trends24.in
        $trending = $this->fetchTrends24();
        
        return view('trending.test', compact('trending'));
    }

    /**
     * Mengambil data trending dari Trends24.in menggunakan metode yang lebih robust.
     *
     * @return array
     */
    private function fetchTrends24()
    {
        $url = "https://trends24.in/indonesia/";
        $trending = [];
        
        // Inisialisasi cURL dengan pengaturan yang lebih baik
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Matikan verifikasi SSL untuk mencegah masalah koneksi
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        // Tambahkan header browser-like untuk menghindari pendeteksian sebagai bot
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // User agent yang lebih spesifik dan realistis
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0');
        
        // Menambahkan timeout untuk mencegah pengambilan yang terlalu lama
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Tambahkan referrer untuk menyerupai klik dari Google
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
        
        // Eksekusi cURL
        $html = curl_exec($ch);
        
        if (!curl_errno($ch) && $html) {
            // Gunakan DOM dan XPath untuk parsing HTML yang lebih akurat
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true); // suppress warning HTML error
            $dom->loadHTML($html);
            libxml_clear_errors();
            
            $xpath = new \DOMXPath($dom);
            
            // XPath untuk menemukan elemen trending
            $nodes = $xpath->query("//div[contains(@class, 'list-container')][1]//ol[contains(@class, 'trend-card__list')]/li/span[contains(@class, 'trend-name')]/a");
            
            $counter = 1;
            foreach ($nodes as $node) {
                $title = $node->nodeValue;
                $url = $node->getAttribute('href');
                
                // Coba mendapatkan jumlah tweet jika tersedia
                $tweetCount = '';
                $parentNode = $node->parentNode->parentNode; // li element
                $tweetCountNode = $xpath->query(".//span[contains(@class, 'tweet-count')]", $parentNode);
                if ($tweetCountNode->length > 0) {
                    $tweetCount = $tweetCountNode->item(0)->nodeValue;
                }
                
                $trending[] = [
                    'name' => trim($title),
                    'url' => $url,
                    'rank' => $counter,
                    'tweet_count' => $tweetCount
                ];
                
                $counter++;
                
                // Batasi hanya 20 trending teratas
                if ($counter > 20) break;
            }
            
            // Jika XPath pertama gagal, coba metode cadangan dengan regex
            if (empty($trending)) {
                preg_match_all('/<a href="(https:\/\/twitter\.com\/search\?q=[^"]+)"[^>]*>\s*<span class="trend-name">([^<]+)<\/span>/i', $html, $matches, PREG_SET_ORDER);
                
                $counter = 1;
                foreach ($matches as $match) {
                    $trending[] = [
                        'name' => trim($match[2]),
                        'url' => $match[1],
                        'rank' => $counter,
                        'tweet_count' => ''
                    ];
                    $counter++;
                    
                    if ($counter > 20) break;
                }
            }
        }
        
        curl_close($ch);
        
        return $trending;
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

    /**
     * Menyimpan trending dari feed langsung ke database.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveFromFeed(Request $request)
    {
        // Validasi data yang diterima
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
        ]);

        // Dapatkan ID media sosial X
        $mediaSosialX = MediaSosial::where('nama', 'X')->first();
        
        if (!$mediaSosialX) {
            return redirect()->route('trending.index')
                            ->with('error', 'Media sosial X tidak ditemukan!');
        }

        // Cek apakah trending dengan judul yang sama sudah ada
        $existingTrending = Trending::where('judul', $validated['name'])
                                   ->where('media_sosial_id', $mediaSosialX->id)
                                   ->first();
        
        if ($existingTrending) {
            return redirect()->route('trending.index')
                            ->with('info', 'Trending dengan judul tersebut sudah ada!');
        }

        // Buat trending baru
        Trending::create([
            'media_sosial_id' => $mediaSosialX->id,
            'tanggal' => now(),
            'judul' => $validated['name'],
            'url' => $validated['url'],
        ]);

        return redirect()->route('trending.index')
                        ->with('success', 'Trending berhasil disimpan ke database!');
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