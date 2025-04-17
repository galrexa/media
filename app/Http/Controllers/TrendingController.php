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
        $trendingTrends24 = $this->fetchTrends24();
    
        // Ambil data dari RSS feed untuk Trending Google
        $trendingGoogle = $this->fetchGoogleTrends();

        // Tambahkan query untuk selected trendings (tambahkan di bawah baris di atas)
        $selectedGoogleTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'Google');
            })
            ->orderBy('display_order_google', 'asc')
            ->with('mediaSosial')
            ->get() ?? collect([]);

        $selectedXTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'X');
            })
            ->orderBy('display_order_x', 'asc')
            ->with('mediaSosial')
            ->get() ?? collect([]);
    
        return view('trending.index', compact(
                'trendingX',
                'trendingTrends24',
                'trendingGoogle',
                'selectedGoogleTrendings', // Tambahkan variabel ini
                'selectedXTrendings'      // Tambahkan variabel ini
            ));
    }

    /**
     * Mengambil data trending dari Trends24.in menggunakan metode yang lebih robust.
     *
     * @param bool $saveToDatabase Menyimpan hasil trending langsung ke database jika true
     * @return array
     */
    public function fetchTrends24($saveToDatabase = false)
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
                
                // Batasi hanya 50 trending teratas
                if ($counter > 50) break;
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
                    
                    if ($counter > 50) break;
                }
            }

            // Jika parameter saveToDatabase = true, simpan trending ke database
            if ($saveToDatabase && !empty($trending)) {
                $this->saveTrendsToDatabase($trending);
            }
        }
        
        curl_close($ch);
        
        return $trending;
    }

    /**
     * Menyimpan trending dari Trends24.in ke database
     * 
     * @param array $trending Data trending yang akan disimpan
     * @return void
     */
    private function saveTrendsToDatabase(array $trending)
    {
        // Dapatkan ID media sosial X
        $mediaSosialX = MediaSosial::where('nama', 'X')->first();
        
        if (!$mediaSosialX) {
            // Log error jika media sosial X tidak ditemukan
            \Log::error('Media sosial X tidak ditemukan saat mencoba menyimpan trending.');
            return;
        }

        $now = now();
        
        // Batasi hanya 20 trending teratas untuk disimpan ke database
        $trendingToSave = array_slice($trending, 0, 20);
        
        foreach ($trendingToSave as $trend) {
            // Cek apakah trending dengan judul yang sama sudah ada dalam 24 jam terakhir
            $existingTrending = Trending::where('judul', $trend['name'])
                ->where('media_sosial_id', $mediaSosialX->id)
                ->where('tanggal', '>=', $now->copy()->subHours(24))
                ->first();
            
            // Jika belum ada, simpan ke database
            if (!$existingTrending) {
                Trending::create([
                    'media_sosial_id' => $mediaSosialX->id,
                    'tanggal' => $now,
                    'judul' => $trend['name'],
                    'url' => $trend['url'],
                ]);
            }
        }
    }

    /**
     * Mengambil data trending dari RSS feed Google Trends.
     *
     * @param bool $saveToDatabase Menyimpan hasil trending langsung ke database jika true
     * @return array
     */
    public function fetchGoogleTrends($saveToDatabase = false)
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
                    $query = urlencode($title); // Encode judul sebagai parameter query
                    $url = "https://news.google.com/search?hl=id&gl=ID&ceid=ID:id&q=" . $query;

                    $trendingGoogle[] = [
                        'judul' => $title,
                        'tanggal' => $pubDate,
                        'url' => $url,
                        'traffic' => $traffic,
                        'rank' => $counter,
                    ];
                    
                    $counter++;
                }

                // Jika parameter saveToDatabase = true, simpan trending Google ke database
                if ($saveToDatabase && !empty($trendingGoogle)) {
                    $this->saveGoogleTrendsToDatabase($trendingGoogle);
                }
            }
        }
        
        curl_close($ch);
        return $trendingGoogle;
    }

    /**
     * Menyimpan trending dari Google Trends ke database
     * 
     * @param array $trendingGoogle Data trending yang akan disimpan
     * @return void
     */
    private function saveGoogleTrendsToDatabase(array $trendingGoogle)
    {
        // Dapatkan ID media sosial Google
        $mediaSosialGoogle = MediaSosial::where('nama', 'Google')->first();
        
        if (!$mediaSosialGoogle) {
            // Log error jika media sosial Google tidak ditemukan
            \Log::error('Media sosial Google tidak ditemukan saat mencoba menyimpan trending.');
            
            // Coba buat media sosial Google jika tidak ada
            $mediaSosialGoogle = MediaSosial::create([
                'nama' => 'Google',
                'icon' => 'google',
                'url' => 'https://trends.google.com'
            ]);
            
            if (!$mediaSosialGoogle) {
                return;
            }
        }

        $now = now();
        
        // Batasi hanya 10 trending teratas untuk disimpan ke database
        $trendingToSave = array_slice($trendingGoogle, 0, 10);
        
        foreach ($trendingToSave as $trend) {
            // Cek apakah trending dengan judul yang sama sudah ada dalam 24 jam terakhir
            $existingTrending = Trending::where('judul', $trend['judul'])
                ->where('media_sosial_id', $mediaSosialGoogle->id)
                ->where('tanggal', '>=', $now->copy()->subHours(24))
                ->first();
            
            // Jika belum ada, simpan ke database
            if (!$existingTrending) {
                Trending::create([
                    'media_sosial_id' => $mediaSosialGoogle->id,
                    'tanggal' => $now,
                    'judul' => $trend['judul'],
                    'url' => $trend['url'],
                ]);
            }
        }
    }

    /**
     * Update urutan trending yang dipilih (untuk kompatibilitas dengan kode lama)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:trendings,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        $items = $request->items;
        
        foreach ($items as $item) {
            $trending = Trending::find($item['id']);
            
            if ($trending) {
                // Update display_order umum
                $trending->display_order = $item['order'];
                
                // Update juga display_order spesifik berdasarkan media sosial
                if ($trending->mediaSosial->nama == 'Google') {
                    $trending->display_order_google = $item['order'];
                } else if ($trending->mediaSosial->nama == 'X') {
                    $trending->display_order_x = $item['order'];
                }
                
                $trending->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Urutan trending berhasil diperbarui!'
        ]);
    }    
    /**
     * Menyimpan trending dari feed dengan opsi selected
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveFromFeedWithSelection(Request $request)
    {
        // Validasi data yang diterima
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'source' => 'required|in:google,x',
            'is_selected' => 'boolean',
        ]);

        // Tentukan media sosial berdasarkan source
        $mediaSosialNama = $validated['source'] === 'google' ? 'Google' : 'X';
        $mediaSosial = MediaSosial::where('nama', $mediaSosialNama)->first();
        
        if (!$mediaSosial) {
            return redirect()->route('trending.manageSelected')
                            ->with('error', "Media sosial {$mediaSosialNama} tidak ditemukan!");
        }

        // Cek apakah trending dengan judul yang sama sudah ada
        $existingTrending = Trending::where('judul', $validated['name'])
                                ->where('media_sosial_id', $mediaSosial->id)
                                ->first();
        
        if ($existingTrending) {
            // Jika sudah ada dan diminta untuk dipilih, update status selected
            if (isset($validated['is_selected']) && $validated['is_selected']) {
                $existingTrending->is_selected = true;
                
                // Atur display order sesuai media sosial
                if ($mediaSosialNama == 'Google') {
                    // Ambil urutan terakhir Google
                    $maxOrderGoogle = Trending::where('is_selected', true)
                        ->whereHas('mediaSosial', function($query) {
                            $query->where('nama', 'Google');
                        })
                        ->max('display_order_google');
                    
                    $existingTrending->display_order_google = ($maxOrderGoogle !== null) ? $maxOrderGoogle + 1 : 0;
                } else if ($mediaSosialNama == 'X') {
                    // Ambil urutan terakhir X
                    $maxOrderX = Trending::where('is_selected', true)
                        ->whereHas('mediaSosial', function($query) {
                            $query->where('nama', 'X');
                        })
                        ->max('display_order_x');
                    
                    $existingTrending->display_order_x = ($maxOrderX !== null) ? $maxOrderX + 1 : 0;
                }
                
                // Update display_order umum untuk kompatibilitas
                $maxOrder = Trending::where('is_selected', true)->max('display_order');
                $existingTrending->display_order = ($maxOrder !== null) ? $maxOrder + 1 : 0;
                
                $existingTrending->save();
                
                return redirect()->route('trending.manageSelected')
                                ->with('success', 'Trending sudah ada dan ditandai sebagai selected!');
            }
            
            return redirect()->route('trending.manageSelected')
                            ->with('info', 'Trending dengan judul tersebut sudah ada!');
        }

        // Buat trending baru
        $newTrending = new Trending([
            'media_sosial_id' => $mediaSosial->id,
            'tanggal' => now(),
            'judul' => $validated['name'],
            'url' => $validated['url'],
            'is_selected' => isset($validated['is_selected']) ? $validated['is_selected'] : false,
        ]);
        
        // Jika trending baru dipilih, atur display_order sesuai media sosial
        if ($newTrending->is_selected) {
            if ($mediaSosialNama == 'Google') {
                // Ambil urutan terakhir Google
                $maxOrderGoogle = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'Google');
                    })
                    ->max('display_order_google');
                
                $newTrending->display_order_google = ($maxOrderGoogle !== null) ? $maxOrderGoogle + 1 : 0;
            } else if ($mediaSosialNama == 'X') {
                // Ambil urutan terakhir X
                $maxOrderX = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'X');
                    })
                    ->max('display_order_x');
                
                $newTrending->display_order_x = ($maxOrderX !== null) ? $maxOrderX + 1 : 0;
            }
            
            // Update display_order umum untuk kompatibilitas
            $maxOrder = Trending::where('is_selected', true)->max('display_order');
            $newTrending->display_order = ($maxOrder !== null) ? $maxOrder + 1 : 0;
        }
        
        $newTrending->save();

        return redirect()->route('trending.manageSelected')
                        ->with('success', 'Trending berhasil disimpan ke database!');
    }


    /**
     * Menyimpan semua trending dari Google Trends ke database
     * Method ini bisa dipanggil dari scheduler
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAllGoogleTrends()
    {
        // Ambil trending dari Google Trends dan simpan ke database
        $this->fetchGoogleTrends(true);
        
        return redirect()->route('trending.index')
                        ->with('success', 'Semua trending dari Google Trends berhasil disimpan ke database!');
    }

    /**
     * Menyimpan semua trending dari Trends24.in ke database
     * Method ini bisa dipanggil dari scheduler
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAllTrends24()
    {
        // Ambil trending dari Trends24.in dan simpan ke database
        $this->fetchTrends24(true);
        
        return redirect()->route('trending.index')
                        ->with('success', 'Semua trending dari Trends24.in berhasil disimpan ke database!');
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

    /**
     * Menampilkan halaman trending yang terpilih
     * 
     * @return \Illuminate\View\View
     */
    // Perubahan pada method selected() di TrendingController.php
    
    public function selected(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        
        // Ambil trending Google yang dipilih berdasarkan tanggal
        $selectedGoogleTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'Google');
            })
            ->whereDate('tanggal', $date)
            ->orderBy('display_order_google', 'asc')
            ->with('mediaSosial')
            ->get() ?? collect([]);

        // Ambil trending X yang dipilih berdasarkan tanggal
        $selectedXTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'X');
            })
            ->whereDate('tanggal', $date)
            ->orderBy('display_order_x', 'asc')
            ->with('mediaSosial')
            ->get() ?? collect([]);

        // Tidak perlu mengambil data dari tanggal lain jika tanggal hari ini tidak memiliki data
        // Biarkan view menampilkan pesan "belum ada topik yang dipilih" jika kosong

        return view('trending.index', compact('selectedGoogleTrendings', 'selectedXTrendings', 'date'));
    }
    
    /**
     * Toggle status selected pada trending
     *
     * @param Trending $trending
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleSelected(Trending $trending)
    {
        // Toggle status selected
        $trending->is_selected = !$trending->is_selected;
        
        // Jika dipilih, atur display_order sesuai media sosial
        if ($trending->is_selected) {
            $mediaSosialNama = $trending->mediaSosial->nama;
            
            if ($mediaSosialNama == 'Google') {
                // Ambil urutan terakhir Google
                $maxOrderGoogle = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'Google');
                    })
                    ->max('display_order_google');
                
                $trending->display_order_google = ($maxOrderGoogle !== null) ? $maxOrderGoogle + 1 : 0;
            } else if ($mediaSosialNama == 'X') {
                // Ambil urutan terakhir X
                $maxOrderX = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'X');
                    })
                    ->max('display_order_x');
                
                $trending->display_order_x = ($maxOrderX !== null) ? $maxOrderX + 1 : 0;
            }
            
            // Tetap update display_order umum untuk kompatibilitas
            $maxOrder = Trending::where('is_selected', true)->max('display_order');
            $trending->display_order = ($maxOrder !== null) ? $maxOrder + 1 : 0;
        }
        
        $trending->save();
        
        return redirect()->back()->with('success', 'Status trending berhasil diperbarui!');
    }
    
    /**
     * Mendapatkan daftar trending yang dipilih untuk ditampilkan di home
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSelectedTrendings()
    {
        $selectedTrendings = Trending::where('is_selected', true)
            ->orderBy('display_order', 'asc')
            ->with('mediaSosial')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $selectedTrendings
        ]);
    }

    /**
     * Menampilkan halaman manajemen trending Google
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    // Perubahan pada method manageGoogleSelected di TrendingController.php
    public function manageGoogleSelected(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        
        // Ambil trending Google dari API
        $trendingGoogle = $this->fetchGoogleTrends();
        
        // Ambil trending Google yang dipilih berdasarkan tanggal
        $selectedGoogleTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'Google');
            })
            ->whereDate('tanggal', $date)
            ->orderBy('display_order_google', 'asc')
            ->with('mediaSosial')
            ->get();
        
        return view('trending.manage-google-selected', compact('trendingGoogle', 'selectedGoogleTrendings', 'date'));
    }

    /**
     * Menampilkan halaman manajemen trending X
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function manageXSelected(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        
        // Ambil trending X dari API
        $trendingX = $this->fetchTrends24();
        
        // Ambil trending X yang dipilih berdasarkan tanggal
        $selectedXTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'X');
            })
            ->whereDate('tanggal', $date)
            ->orderBy('display_order_x', 'asc')
            ->with('mediaSosial')
            ->get();
        
        return view('trending.manage-x-selected', compact('trendingX', 'selectedXTrendings', 'date'));
    }

    /**
     * Refresh data Google Trends
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshGoogleTrends()
    {
        // Ambil trending Google dan simpan ke database
        $this->fetchGoogleTrends(true);
        
        return redirect()->back()->with('success', 'Data trending Google berhasil diperbarui!');
    }

    /**
     * Refresh data X Trends
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshXTrends()
    {
        // Ambil trending X dan simpan ke database
        $this->fetchTrends24(true);
        
        return redirect()->back()->with('success', 'Data trending X berhasil diperbarui!');
    }

    /**
     * Menyimpan trending Google dengan status selected
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveGoogleWithSelection(Request $request)
    {
        // Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'traffic' => 'nullable|string',
            'date' => 'required|date',
            'is_selected' => 'boolean',
        ]);
        
        // Cari media sosial Google
        $mediaSosialGoogle = MediaSosial::where('nama', 'Google')->first();
        
        if (!$mediaSosialGoogle) {
            return redirect()->back()->with('error', 'Media sosial Google tidak ditemukan!');
        }
        
        // Cek apakah trending dengan judul yang sama sudah ada pada tanggal tersebut
        $existingTrending = Trending::where('judul', $validated['name'])
            ->where('media_sosial_id', $mediaSosialGoogle->id)
            ->whereDate('tanggal', $validated['date'])
            ->first();
        
        if ($existingTrending) {
            // Update status selected
            $existingTrending->is_selected = $validated['is_selected'] ?? false;
            
            // Update display_order_google jika selected
            if ($existingTrending->is_selected) {
                $maxOrderGoogle = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'Google');
                    })
                    ->whereDate('tanggal', $validated['date'])
                    ->max('display_order_google');
                
                $existingTrending->display_order_google = ($maxOrderGoogle !== null) ? $maxOrderGoogle + 1 : 0;
            }
            
            $existingTrending->save();
            
            return redirect()->back()->with('success', 'Trending Google berhasil diupdate!');
        }
        
        // Buat trending baru
        $newTrending = new Trending([
            'media_sosial_id' => $mediaSosialGoogle->id,
            'tanggal' => $validated['date'],
            'judul' => $validated['name'],
            'url' => $validated['url'],
            'is_selected' => $validated['is_selected'] ?? false,
        ]);
        
        // Set display_order_google jika selected
        if ($newTrending->is_selected) {
            $maxOrderGoogle = Trending::where('is_selected', true)
                ->whereHas('mediaSosial', function($query) {
                    $query->where('nama', 'Google');
                })
                ->whereDate('tanggal', $validated['date'])
                ->max('display_order_google');
            
            $newTrending->display_order_google = ($maxOrderGoogle !== null) ? $maxOrderGoogle + 1 : 0;
        }
        
        $newTrending->save();
        
        return redirect()->back()->with('success', 'Trending Google berhasil disimpan!');
    }

    /**
     * Menyimpan trending X dengan status selected
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function saveXWithSelection(Request $request)
    {
        // Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'tweet_count' => 'nullable|string',
            'date' => 'required|date',
            'is_selected' => 'boolean',
        ]);
        
        // Cari media sosial X
        $mediaSosialX = MediaSosial::where('nama', 'X')->first();
        
        if (!$mediaSosialX) {
            return redirect()->back()->with('error', 'Media sosial X tidak ditemukan!');
        }
        
        // Cek apakah trending dengan judul yang sama sudah ada pada tanggal tersebut
        $existingTrending = Trending::where('judul', $validated['name'])
            ->where('media_sosial_id', $mediaSosialX->id)
            ->whereDate('tanggal', $validated['date'])
            ->first();
        
        if ($existingTrending) {
            // Update status selected
            $existingTrending->is_selected = $validated['is_selected'] ?? false;
            
            // Update display_order_x jika selected
            if ($existingTrending->is_selected) {
                $maxOrderX = Trending::where('is_selected', true)
                    ->whereHas('mediaSosial', function($query) {
                        $query->where('nama', 'X');
                    })
                    ->whereDate('tanggal', $validated['date'])
                    ->max('display_order_x');
                
                $existingTrending->display_order_x = ($maxOrderX !== null) ? $maxOrderX + 1 : 0;
            }
            
            $existingTrending->save();
            
            return redirect()->back()->with('success', 'Trending X berhasil diupdate!');
        }
        
        // Buat trending baru
        $newTrending = new Trending([
            'media_sosial_id' => $mediaSosialX->id,
            'tanggal' => $validated['date'],
            'judul' => $validated['name'],
            'url' => $validated['url'],
            'is_selected' => $validated['is_selected'] ?? false,
        ]);
        
        // Set display_order_x jika selected
        if ($newTrending->is_selected) {
            $maxOrderX = Trending::where('is_selected', true)
                ->whereHas('mediaSosial', function($query) {
                    $query->where('nama', 'X');
                })
                ->whereDate('tanggal', $validated['date'])
                ->max('display_order_x');
            
            $newTrending->display_order_x = ($maxOrderX !== null) ? $maxOrderX + 1 : 0;
        }
        
        $newTrending->save();
        
        return redirect()->back()->with('success', 'Trending X berhasil disimpan!');
    }

    /**
     * Update urutan trending Google
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGoogleOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:trendings,id',
            'items.*.order' => 'required|integer|min:0',
            'date' => 'nullable|date',
        ]);

        $items = $request->items;
        $date = $request->date ?? date('Y-m-d');
        
        foreach ($items as $item) {
            Trending::where('id', $item['id'])
                ->update(['display_order_google' => $item['order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Urutan trending Google berhasil diperbarui!'
        ]);
    }

    /**
     * Update urutan trending X
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateXOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:trendings,id',
            'items.*.order' => 'required|integer|min:0',
            'date' => 'nullable|date',
        ]);

        $items = $request->items;
        $date = $request->date ?? date('Y-m-d');
        
        foreach ($items as $item) {
            Trending::where('id', $item['id'])
                ->update(['display_order_x' => $item['order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Urutan trending X berhasil diperbarui!'
        ]);
    }
    
    /**
     * Hapus trending yang dipilih
     * 
     * @param int $id ID trending yang akan dihapus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteSelected($id)
    {
        $trending = Trending::findOrFail($id);
        
        // Atur is_selected menjadi false
        $trending->is_selected = false;
        
        // Reset urutan
        if ($trending->mediaSosial->nama === 'Google') {
            $trending->display_order_google = null;
        } else if ($trending->mediaSosial->nama === 'X') {
            $trending->display_order_x = null;
        }
        
        $trending->save();
        
        return redirect()->back()->with('success', 'Trending berhasil dihapus dari daftar terpilih!');
    }
    
    /**
     * Menampilkan detail trending
     * 
     * @param int $id ID trending
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $trending = Trending::with('mediaSosial')->findOrFail($id);
        
        return view('trending.show', compact('trending'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $trending = Trending::with('mediaSosial')->findOrFail($id);
        $mediaSosials = MediaSosial::all();
        
        return view('trending.edit', compact('trending', 'mediaSosials'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $trending = Trending::findOrFail($id);
        
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'media_sosial_id' => 'required|exists:media_sosials,id',
            'tanggal' => 'required|date',
            'is_selected' => 'boolean',
        ]);
        
        $trending->fill($validated);
        $trending->save();
        
        return redirect()->route('trending.index')->with('success', 'Trending berhasil diperbarui!');
    }
}