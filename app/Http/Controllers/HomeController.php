<?php
// app/Http/Controllers/HomeController.php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\Trending;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Cek apakah ada parameter day atau offset
        $hasDay = $request->has('day');
        $hasOffset = $request->has('offset');
        
        // Tentukan offset: Prioritaskan penggunaan parameter offset jika ada
        if ($hasOffset) {
            $offset = (int) $request->query('offset', 0);
        } elseif ($hasDay) {
            // Jika hanya parameter day yang ada, konversi day ke offset
            $dayParam = (int) $request->query('day', 0);
            $offset = -$dayParam;  // Konversi langsung, tanpa redirect
        } else {
            // Default ke hari ini jika tidak ada parameter
            $offset = 0;
        }
        
        // Batasi offset agar tidak melampaui batas (opsional)
        $minOffset = -30; // 30 hari ke masa lalu
        $maxOffset = 0;   // hingga hari ini (tidak dapat melihat masa depan)
        
        if ($offset < $minOffset) $offset = $minOffset;
        if ($offset > $maxOffset) $offset = $maxOffset;
        
        // Untuk kompatibilitas dengan view yang menggunakan selectedDay
        $selectedDay = -$offset; // Konversi offset ke format day lama
        
        // Tanggal yang dipilih berdasarkan offset
        $selectedDate = Carbon::today()->addDays($offset);
        
        // Ambil page untuk navigasi multi-halaman (jika masih digunakan)
        $page = max(0, (int) $request->query('page', 0));
        
        // Rentang waktu untuk filter data (1 hari penuh)
        $startDate = $selectedDate->copy()->startOfDay();
        $endDate = $selectedDate->copy()->endOfDay();
    
        // Ambil tanggal-tanggal yang memiliki data
        $availableDates = $this->getAvailableDates();
        
        // Pengolahan untuk tampilan tanggal di navigasi (7 hari per halaman)
        $offsetPage = $page * 7;
        $dates = array_slice($availableDates, $offsetPage, 7);
        $hasNextPage = count($availableDates) > ($offsetPage + 7);
        $hasPrevPage = $page > 0;
    
        // Ambil data untuk tanggal yang dipilih
        $dailyImages = Document::whereDate('tanggal', $selectedDate)->first();

        $isuStrategis = Isu::where('isu_strategis', true)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();
            
        $isuLainnya = Isu::where('isu_strategis', false)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(12)
            ->get();
            
        // Ambil data trending Google dari RSS feed
        $trendingGoogle = $this->fetchGoogleTrends();
            
        $trendingX = Trending::whereHas('mediaSosial', fn($query) => $query->where('nama', 'X'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->limit(6)
            ->get();
            
        // Tambahkan data trending X live dari Trends24.in
        $trendingXLive = $this->fetchTrends24();
    
        // Data untuk view
        $viewData = compact(
            'isuStrategis',
            'isuLainnya',
            'trendingGoogle',
            'trendingX',
            'trendingXLive',  // Tambahkan trendingXLive ke viewData
            'dates',
            'page',
            'hasNextPage',
            'hasPrevPage',
            'dailyImages',
            'selectedDay',     // Untuk kompatibilitas mundur
            'selectedDate',
            'availableDates',  // Pastikan availableDates disertakan
            'offset',          // Tambahkan offset untuk digunakan di view baru
            'minOffset',       // Tambahkan batas minimum offset
            'maxOffset'        // Tambahkan batas maksimum offset
        );

        // Ambil tanggal isu terakhir
        $latestIsuDate = Isu::latest('tanggal')->first()->tanggal ?? Carbon::now();
        
        // Tambahkan ke viewData
        $viewData['latestIsuDate'] = $latestIsuDate;
        
        // Cek jika ada intended_url setelah login
        if ($request->session()->has('intended_url')) {
            $intendedUrl = $request->session()->pull('intended_url');
            return redirect($intendedUrl);
        }
    
        // Sekarang gunakan satu view untuk semua pengguna
        return view('home', $viewData);
    }

    /**
     * Mengambil data trending dari RSS feed Google Trends.
     * 
     * @param Carbon|null $selectedDate Filter tanggal (opsional)
     * @return array
     */
    private function fetchGoogleTrends($selectedDate = null)
    {
        $rss_url = "https://trends.google.com/trending/rss?geo=ID";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
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
    
                    // Filter berdasarkan tanggal jika diberikan
                    if ($selectedDate && !$pubDate->isSameDay($selectedDate)) {
                        continue;
                    }
    
                    $trendingGoogle[] = [
                        'judul' => $title,
                        'tanggal' => $pubDate,
                        'url' => $url,
                        'traffic' => $traffic,
                        'rank' => $counter,
                    ];
                    $counter++;
                    if ($counter > 10) break; // Batasi ke 10 item
                }
            }
        }
        curl_close($ch);
        return $trendingGoogle;
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
     * Mendapatkan tanggal-tanggal yang tersedia (hanya tanggal sebelum atau sama dengan hari ini)
     */
    private function getAvailableDates()
    {
        // Ambil tanggal-tanggal yang memiliki data isu, hanya sebelum atau sama dengan hari ini
        $datesFromDb = Isu::selectRaw('DISTINCT DATE(tanggal) as date')
            ->where('tanggal', '<=', Carbon::today())
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
            
        // Jika database kosong, kembalikan array kosong
        if (empty($datesFromDb)) {
            return [];
        }
        
        return $datesFromDb;
    }
}