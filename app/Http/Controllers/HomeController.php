<?php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\Trending;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Ambil parameter navigasi
        $page = max(0, (int) $request->query('page', 0));
        $dayParam = (int) $request->query('day', 0);
        
        // Tanggal yang dipilih: day=0 adalah hari ini, day=1 adalah kemarin, dst
        $selectedDate = Carbon::today()->subDays($dayParam);
        
        // Rentang waktu untuk filter data (1 hari penuh)
        $startDate = $selectedDate->copy()->startOfDay();
        $endDate = $selectedDate->copy()->endOfDay();

        // Ambil tanggal-tanggal yang memiliki data
        $availableDates = $this->getAvailableDates($selectedDate, $dayParam);
        
        // Pengolahan untuk tampilan tanggal di navigasi (7 hari per halaman)
        $offset = $page * 7;
        $dates = array_slice($availableDates, $offset, 7);
        $hasNextPage = count($availableDates) > ($offset + 7);
        $hasPrevPage = $page > 0;

        // Ambil data untuk tanggal yang dipilih
        $dailyImages = Image::whereDate('tanggal', $selectedDate)->first();
        
        $isuStrategis = Isu::where('isu_strategis', true)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(15)
            ->get();
            
        $isuLainnya = Isu::where('isu_strategis', false)
            ->whereDate('tanggal', $selectedDate->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->limit(12)
            ->get();
            
        $trendingGoogle = Trending::whereHas('mediaSosial', fn($query) => $query->where('nama', 'Google'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->limit(6)
            ->get();
            
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
            'selectedDate'
        );

        $viewData['selectedDay'] = $dayParam;

        // Tentukan view berdasarkan peran pengguna
        $view = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor())
            ? 'home.admin'
            : 'home';

        return view($view, $viewData);
    }
    
    /**
     * Mendapatkan tanggal-tanggal yang tersedia dan mengurutkannya
     */
    private function getAvailableDates($selectedDate, $selectedDay)
    {
        // Ambil tanggal-tanggal yang memiliki data isu
        $datesFromDb = Isu::selectRaw('DISTINCT DATE(tanggal) as date')
            ->orderBy('date')
            ->pluck('date')
            ->toArray();
            
        // Jika database kosong atau tidak ada data isu, buat 7 hari secara manual
        if (empty($datesFromDb)) {
            $dateRange = [];
            for ($i = -3; $i <= 3; $i++) {
                $date = Carbon::today()->addDays($i);
                $dateRange[] = $date->format('Y-m-d');
            }
            $datesFromDb = $dateRange;
        }
        
        // Format tanggal untuk tampilan
        $formattedDates = [];
        foreach ($datesFromDb as $date) {
            $carbonDate = Carbon::parse($date)->startOfDay();
            $daysFromToday = Carbon::today()->startOfDay()->diffInDays($carbonDate, false);
            
            $formattedDates[] = [
                'date' => $carbonDate->format('d F Y'),
                'display_date' => $carbonDate->format('d F'),
                'day' => $daysFromToday >= 0 ? $daysFromToday : abs($daysFromToday),
                'active' => $carbonDate->isSameDay($selectedDate),
                'is_today' => $carbonDate->isToday(),
                'sort_key' => $daysFromToday // Kunci untuk pengurutan
            ];
        }
        
        // Urutkan: hari ini pertama, kemudian tanggal terdekat
        usort($formattedDates, function($a, $b) {
            if ($a['is_today']) return -1;
            if ($b['is_today']) return 1;
            return abs($a['sort_key']) - abs($b['sort_key']);
        });
        
        return $formattedDates;
    }
}