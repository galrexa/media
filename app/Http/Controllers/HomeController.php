<?php
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
    
        // Data untuk view
        $viewData = compact(
            'isuStrategis',
            'isuLainnya',
            'trendingGoogle',
            'trendingX',
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