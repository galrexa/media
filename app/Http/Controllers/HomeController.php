<?php
// app/Http/Controllers/HomeController.php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\Trending;
use App\Models\Document;
use App\Models\RefStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
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
            ->where('status_id', 6)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(20)
            ->get();

        $isuLainnya = Isu::where('isu_strategis', false)
            ->where('status_id', 6)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(20)
            ->get();

        // Gunakan TrendingController untuk mengambil data trending
        $trendingController = new TrendingController();

        // Ambil data trending Google dari RSS feed
        $trendingGoogle = $trendingController->fetchGoogleTrends(true);

        $trendingX = Trending::whereHas('mediaSosial', fn($query) => $query->where('nama', 'X'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->limit(20)
            ->get();

        // Ambil data trending X dari Trends24.in dan simpan ke database
        $trendingXLive = $trendingController->fetchTrends24(true);

        // Ambil trending Google yang dipilih untuk tanggal tertentu
        $selectedGoogleTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'Google');
            })
            ->whereDate('tanggal', $selectedDate)
            ->orderBy('display_order_google', 'asc')
            ->with('mediaSosial')
            ->get();

        // Ambil trending X yang dipilih untuk tanggal tertentu
        $selectedXTrendings = Trending::where('is_selected', true)
            ->whereHas('mediaSosial', function($query) {
                $query->where('nama', 'X');
            })
            ->whereDate('tanggal', $selectedDate)
            ->orderBy('display_order_x', 'asc')
            ->with('mediaSosial')
            ->get();

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
            'maxOffset',
            'selectedGoogleTrendings',
            'selectedXTrendings'
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
