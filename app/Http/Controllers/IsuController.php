<?php
// app/Http/Controllers/IsuController.php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\ReferensiIsu;
use App\Models\RefSkala;
use App\Models\RefStatus;
use App\Models\RefTone;
use App\Models\Kategori;
use App\Helpers\LogHelper;
use App\Helpers\AlertHelper;
use App\Helpers\ThumbnailHelper;
use App\Models\User;
use App\Models\LogIsu;
use App\Models\AIAnalysisResult;
use App\Services\IsuNotificationService;
use App\Services\AIAnalysisService;
use App\Services\WebScrapingService;
use App\Services\GroqAIService;
use Embed\Embed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;

class IsuController extends Controller
{

    protected $aiAnalysisService;
    protected $webScrapingService;

    public function __construct(
        AIAnalysisService $aiAnalysisService,
        WebScrapingService $webScrapingService
    ) {
        $this->aiAnalysisService = $aiAnalysisService;
        $this->webScrapingService = $webScrapingService;
    }

     /**
     * Menyimpan isu baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date|before_or_equal:today',
            'isu_strategis' => 'boolean',
            'kategori' => 'nullable|string',
            'skala' => 'nullable|string',
            'tone' => 'nullable|string',
            'rangkuman' => 'nullable|string|max:10000',
            'narasi_positif' => 'nullable|string|max:10000',
            'narasi_negatif' => 'nullable|string|max:10000',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => [
                'nullable',
                'url',
                'max:1000',
                // Validasi URL untuk mencegah injeksi
                function ($attribute, $value, $fail) {
                    $blockedDomains = ['evil.com', 'malicious.org'];
                    $parsed = parse_url($value);
                    if (isset($parsed['host']) && in_array($parsed['host'], $blockedDomains)) {
                        $fail('URL ini tidak diperbolehkan.');
                    }
                }
            ],
            'referensi_thumbnail_url.*' => 'nullable|url|max:1000',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Bersihkan data dan tambahkan default jika kosong
            $rangkuman = !empty($request->rangkuman) ? Purify::clean($request->rangkuman) : '<p>Tidak ada data</p>';
            $narasi_positif = !empty($request->narasi_positif) ? Purify::clean($request->narasi_positif) : '<p>Tidak ada data</p>';
            $narasi_negatif = !empty($request->narasi_negatif) ? Purify::clean($request->narasi_negatif) : '<p>Tidak ada data</p>';

            // Tetapkan nilai default untuk skala dan tone jika kosong
            $skalaId = null;
            if (!empty($validated['skala'])) {
                // Cek apakah input adalah ID atau nama
                if (is_numeric($validated['skala'])) {
                    $skalaId = (int)$validated['skala'];
                } else {
                    // Jika nama, cari ID yang sesuai
                    $skalaRef = RefSkala::where('nama', $validated['skala'])->first();
                    $skalaId = $skalaRef ? $skalaRef->id : null; // Ubah ke null
                }
            }

            $tone = !empty($validated['tone']) ? $validated['tone'] : null;

            // Set status berdasarkan action (simpan atau kirim)
            $statusId = RefStatus::getDraftId(); // Default: Draft
            if ($request->has('action') && $request->action === 'kirim') {
                $statusId = RefStatus::getVerifikasi1Id(); // Verifikasi 1
            }

            // Simpan isu
            $isu = Isu::create([
                'judul' => $validated['judul'],
                'tanggal' => $validated['tanggal'],
                'isu_strategis' => $request->has('isu_strategis'),
                'skala' => $skalaId,
                'tone' => $tone,
                'status_id' => $statusId, // Tambahkan status_id
                'rangkuman' => $rangkuman,
                'narasi_positif' => $narasi_positif,
                'narasi_negatif' => $narasi_negatif,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Variabel untuk menyimpan string kategori
            $kategoriString = '';

            // Proses tags kategori dengan validasi tambahan
            if (!empty($validated['kategori'])) {
                $kategoriInput = $validated['kategori'];
                $tags = [];

                // Jika input adalah JSON dari Tagify, decode terlebih dahulu
                if (json_decode($kategoriInput, true)) {
                    $tagsData = json_decode($kategoriInput, true);
                    // Validasi format data JSON
                    if (is_array($tagsData)) {
                        $tags = array_column($tagsData, 'value');
                    }
                } else {
                    // Jika sudah comma-separated
                    $tags = array_filter(array_map('trim', explode(',', $kategoriInput)));
                }

                // Batasi jumlah tag untuk performa dan keamanan
                $tags = array_slice($tags, 0, 10); // Maksimal 5 kategori
                $kategoriString = implode(',', $tags);

                $kategoriIds = [];
                foreach ($tags as $tag) {
                    // Sanitasi input
                    $sanitizedTag = substr(trim($tag), 0, 50); // Batasi panjang tag
                    if (!empty($sanitizedTag)) {
                        $kategori = Kategori::firstOrCreate(['nama' => $sanitizedTag]);
                        $kategoriIds[] = $kategori->id;
                    }
                }

                // Simpan relasi ke tabel pivot
                $isu->kategoris()->sync($kategoriIds);
            }

            // Simpan referensi jika ada
            if ($request->has('referensi_judul')) {
                $savedRefCount = 0; // Hitung jumlah referensi yang berhasil disimpan

                foreach ($request->referensi_judul as $key => $judul) {

                    if ($savedRefCount >= 10) break; // Maksimal 10 referensi

                    if ($judul && isset($request->referensi_url[$key])) {
                        $url = $request->referensi_url[$key];

                        // Gunakan thumbnail URL langsung dari form jika tersedia
                        $thumbnail = $request->referensi_thumbnail_url[$key] ?? null;

                        // Jika thumbnail tidak tersedia, coba ambil dari URL dengan Embed
                        if (!$thumbnail) {
                            try {
                                // Gunakan embed/embed untuk mendapatkan metadata
                                $embed = new Embed();
                                $info = $embed->get($url);

                                // Ambil URL thumbnail/gambar dari metadata
                                $thumbnail = $info->image;
                            } catch (\Exception $e) {

                                $thumbnail = null;
                            }
                        }

                        // Simpan referensi dengan thumbnail URL (bukan path storage)
                        ReferensiIsu::create([
                            'isu_id' => $isu->id,
                            'judul' => Purify::clean($judul),
                            'url' => $url,
                            'thumbnail' => $thumbnail, // Simpan URL langsung
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $savedRefCount++;
                    }
                }
            }

            // Log aktivitas pembuatan isu dengan status
            LogHelper::logIsuActivity(
                $isu->id,
                'CREATE',
                null,
                null,
                json_encode($isu->toArray()),
                $request,
                $statusId // Tambahkan status_id ke log
            );

            // Log kategori jika ada
            if (!empty($kategoriString)) {
                LogHelper::logIsuActivity(
                    $isu->id,
                    'CREATE',
                    'kategori',
                    null,
                    $kategoriString,
                    $request,
                    $statusId // Tambahkan status_id ke log
                );
            }

            DB::commit();

            // Pesan sukses yang lebih informatif berdasarkan status
            $statusMessage = $statusId == RefStatus::getDraftId()
                ? 'disimpan sebagai draft'
                : 'dikirim untuk verifikasi';
            
            AlertHelper::success('Berhasil', "Isu berhasil {$statusMessage}!");
            return redirect()->route('isu.show', $isu);
        } catch (\Exception $e) {
            DB::rollBack();

            AlertHelper::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Menampilkan daftar isu yang difilter berdasarkan role dan status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->getHighestRoleName();
        $userId = $user->id;

        // Ambil filter status dari request
        $filterStatus = $request->input('filter_status');

        // Reset badge untuk filter rejected
        if ($filterStatus === 'rejected') {
            Session::put('rejected_badge_hidden', true);
            $cacheKey = 'rejected_badge_hidden_' . $userId;
            Cache::put($cacheKey, true, now()->addDays(7));
        }

        // Sorting
        $sortField = $request->get('sort', 'tanggal');
        $sortDirection = in_array(strtolower($request->get('direction', 'desc')), ['asc', 'desc'])
            ? strtolower($request->get('direction', 'desc'))
            : 'desc';

        // Whitelist kolom yang diizinkan untuk sorting
        $allowedSortFields = ['tanggal', 'skala', 'tone', 'status_id'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'tanggal';
        
        // NEW: Determine items per page
        $perPage = $request->input('perPage', 10);
        $isShowAll = $perPage === 'all';
        $perPageValue = $isShowAll ? PHP_INT_MAX : (int) $perPage;
        
        // Cek apakah user adalah verifikator
        $isVerifikator = $user->hasRole('verifikator1') || $user->hasRole('verifikator2');
        
        if ($isVerifikator) {
            // Query gabungan untuk verifikator
            $isusGabunganQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator']);
            
            // Filter berdasarkan status yang relevan untuk verifikator
            $relevantStatuses = [
                RefStatus::getVerifikasi1Id(),
                RefStatus::getVerifikasi2Id(),
                RefStatus::getDipublikasiId(),
                RefStatus::getDitolakId()
            ];
            
            // Jika verifikator1, tambahkan kondisi untuk melihat isu yang sudah dikirim ke verifikator2
            $isusGabunganQuery->whereIn('status_id', $relevantStatuses);
            
            // Filter dari sidebar (filter_status)
            if ($request->has('filter_status')) {
                switch($filterStatus) {
                    case 'draft':
                        $isusGabunganQuery->where('status_id', RefStatus::getDraftId());
                        break;
                    case 'verifikasi1':
                        $isusGabunganQuery->where('status_id', RefStatus::getVerifikasi1Id());
                        break;
                    case 'verifikasi2':
                        $isusGabunganQuery->where('status_id', RefStatus::getVerifikasi2Id());
                        break;
                    case 'rejected':
                        $isusGabunganQuery->where('status_id', RefStatus::getDitolakId());
                        break;
                }
            }
            
            // Filter berdasarkan status dari form
            if ($request->filled('status')) {
                $statusId = (int) $request->input('status');
                $isusGabunganQuery->where('status_id', $statusId);
            }
            
            // Filter tanggal
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
                $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();
                $isusGabunganQuery->whereBetween('tanggal', [$dateFrom, $dateTo]);
            }
            
            // Pencarian global
            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $isusGabunganQuery->where(function($query) use ($searchTerm) {
                    $query->where('judul', 'like', $searchTerm)
                        ->orWhereHas('kategoris', function($q) use ($searchTerm) {
                            $q->where('nama', 'like', $searchTerm);
                        });
                });
            }
            
            // Sorting
            $isusGabunganQuery->orderBy($sortField, $sortDirection);
            
            // NEW: Handle 'all' option
            if ($isShowAll) {
                $allResults = $isusGabunganQuery->get();
                $totalCount = $allResults->count();
                
                // Create a LengthAwarePaginator with all results
                $isusGabungan = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allResults,
                    $totalCount,
                    $totalCount > 0 ? $totalCount : 1,
                    $request->input('semua', 1),
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                // Regular pagination
                $isusGabungan = $isusGabunganQuery->paginate($perPageValue, ['*'], 'semua');
            }
            
            $isusGabungan->appends($request->except('semua'));
            
            // Dapatkan daftar status untuk dropdown filter
            $statusList = RefStatus::getActive();
            
            // Return view untuk verifikator
            return view('isu.index', [
                'isusGabungan' => $isusGabungan,
                'statusList' => $statusList,
                'isRejectedPage' => ($filterStatus === 'rejected'),
                'perPage' => $perPage // NEW: Pass perPage to view
            ]);
        } else {
            
            // Base queries dengan eager loading
            $isusStrategisQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator'])
                                    ->where('isu_strategis', true);
            
            $isusLainnyaQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator'])
                                    ->where('isu_strategis', false);
            
            // Filter dari sidebar (filter_status)
            if ($request->has('filter_status')) {
                switch($filterStatus) {
                    case 'draft':
                        $isusStrategisQuery->where('status_id', RefStatus::getDraftId());
                        $isusLainnyaQuery->where('status_id', RefStatus::getDraftId());
                        
                        // Untuk editor, hanya tampilkan draft miliknya
                        if ($user->isEditor()) {
                            $isusStrategisQuery->where('created_by', $userId);
                            $isusLainnyaQuery->where('created_by', $userId);
                        }
                        break;
                    case 'verifikasi1':
                        $isusStrategisQuery->where('status_id', RefStatus::getVerifikasi1Id());
                        $isusLainnyaQuery->where('status_id', RefStatus::getVerifikasi1Id());
                        break;
                    case 'verifikasi2':
                        $isusStrategisQuery->where('status_id', RefStatus::getVerifikasi2Id());
                        $isusLainnyaQuery->where('status_id', RefStatus::getVerifikasi2Id());
                        break;
                    case 'rejected':
                        $isusStrategisQuery->where('status_id', RefStatus::getDitolakId());
                        $isusLainnyaQuery->where('status_id', RefStatus::getDitolakId());
                        break;
                }
            }
            
            // Filter berdasarkan status dari form
            if ($request->filled('status')) {
                $statusId = (int) $request->input('status');
                $isusStrategisQuery->where('status_id', $statusId);
                $isusLainnyaQuery->where('status_id', $statusId);
                
                // Jika status ditolak dan user adalah editor, tampilkan hanya miliknya
                if ($statusId == RefStatus::getDitolakId() && $user->isEditor()) {
                    $isusStrategisQuery->where('created_by', $userId);
                    $isusLainnyaQuery->where('created_by', $userId);
                }
            }
            
            // Filter tanggal
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
                $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();
                $isusStrategisQuery->whereBetween('tanggal', [$dateFrom, $dateTo]);
                $isusLainnyaQuery->whereBetween('tanggal', [$dateFrom, $dateTo]);
            }
            
            // Pencarian global
            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $isusStrategisQuery->where(function($query) use ($searchTerm) {
                    $query->where('judul', 'like', $searchTerm)
                        ->orWhereHas('kategoris', function($q) use ($searchTerm) {
                            $q->where('nama', 'like', $searchTerm);
                        });
                });
                $isusLainnyaQuery->where(function($query) use ($searchTerm) {
                    $query->where('judul', 'like', $searchTerm)
                        ->orWhereHas('kategoris', function($q) use ($searchTerm) {
                            $q->where('nama', 'like', $searchTerm);
                        });
                });
            }
            
            // Sorting
            $isusStrategisQuery->orderBy($sortField, $sortDirection);
            $isusLainnyaQuery->orderBy($sortField, $sortDirection);
            
            // NEW: Handle 'all' option for both queries
            if ($isShowAll) {
                // Get all results for strategis
                $allStrategisResults = $isusStrategisQuery->get();
                $totalStrategisCount = $allStrategisResults->count();
                
                // Create a LengthAwarePaginator with all strategis results
                $isusStrategis = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allStrategisResults,
                    $totalStrategisCount,
                    $totalStrategisCount > 0 ? $totalStrategisCount : 1,
                    $request->input('strategis', 1),
                    ['path' => $request->url(), 'query' => $request->query()]
                );
                
                // Get all results for lainnya
                $allLainnyaResults = $isusLainnyaQuery->get();
                $totalLainnyaCount = $allLainnyaResults->count();
                
                // Create a LengthAwarePaginator with all lainnya results
                $isusLainnya = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allLainnyaResults,
                    $totalLainnyaCount,
                    $totalLainnyaCount > 0 ? $totalLainnyaCount : 1,
                    $request->input('lainnya', 1),
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                // Regular pagination
                $isusStrategis = $isusStrategisQuery->paginate($perPageValue, ['*'], 'strategis');
                $isusLainnya = $isusLainnyaQuery->paginate($perPageValue, ['*'], 'lainnya');
            }
            
            // Pastikan semua parameter disertakan di URL pagination
            $isusStrategis->appends($request->except('strategis'));
            $isusLainnya->appends($request->except('lainnya'));
            
            // Dapatkan daftar status untuk dropdown filter
            $statusList = RefStatus::getActive();
            
            // Siapkan data sidebar
            $sidebarData = $this->prepareSidebarData();
            
            // Return view untuk non-verifikator
            return view('isu.index', array_merge([
                'isusStrategis' => $isusStrategis,
                'isusLainnya' => $isusLainnya,
                'statusList' => $statusList,
                'isRejectedPage' => ($filterStatus === 'rejected'),
                'perPage' => $perPage // NEW: Pass perPage to view
            ], $sidebarData));
        }
    }

    /**
     * Mengambil dan menyiapkan data untuk sidebar
     * 
     * @return array
     */
    private function prepareSidebarData()
    {
        $user = Auth::user();
        $userId = $user->id;
        $data = [];
        
        // Hitung jumlah isu berdasarkan status
        if ($user->isAdmin()) {
            // Admin bisa melihat semua isu
            $data['draftIsuCount'] = Isu::where('status_id', RefStatus::getDraftId())->count();
            $data['verifikasi1IsuCount'] = Isu::where('status_id', RefStatus::getVerifikasi1Id())->count();
            $data['verifikasi2IsuCount'] = Isu::where('status_id', RefStatus::getVerifikasi2Id())->count();
            $data['rejectedIsuCount'] = Isu::where('status_id', RefStatus::getDitolakId())->count();
            $data['pendingIsuCount'] = $data['verifikasi1IsuCount'] + $data['verifikasi2IsuCount'];
        } 
        elseif ($user->isEditor()) {
            // Editor hanya bisa melihat draft isu yang dia buat
            $data['draftIsuCount'] = Isu::where('status_id', RefStatus::getDraftId())
                                ->where('created_by', $userId)
                                ->count();
                                
            // Untuk rejected, hanya tampilkan jumlah isu yang dibuat oleh editor tersebut
            $data['rejectedIsuCount'] = Isu::where('status_id', RefStatus::getDitolakId())
                                    ->where('created_by', $userId)
                                    ->count();
            
            // Cek apakah badge ditolak perlu disembunyikan berdasarkan cache
            $cacheKey = 'rejected_badge_hidden_' . $userId;
            if (Cache::has($cacheKey) && Cache::get($cacheKey) === true) {
                $data['rejectedIsuCount'] = 0; // Set ke 0 untuk menyembunyikan badge
            }
        }
        elseif ($user->hasRole('verifikator1')) {
            // Verifikator 1 hanya perlu melihat isu yang perlu diverifikasi level 1
            $data['verifikasi1IsuCount'] = Isu::where('status_id', RefStatus::getVerifikasi1Id())->count();
            $data['pendingIsuCount'] = $data['verifikasi1IsuCount'];
        }
        elseif ($user->hasRole('verifikator2')) {
            // Verifikator 2 hanya perlu melihat isu yang perlu diverifikasi level 2
            $data['verifikasi2IsuCount'] = Isu::where('status_id', RefStatus::getVerifikasi2Id())->count();
            $data['pendingIsuCount'] = $data['verifikasi2IsuCount'];
        }
        
        return $data;
    }    

    /**
     * Menampilkan form untuk membuat isu baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Cek jika user adalah editor atau admin
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isEditor()) {
            return redirect()->route('isu.index')
                ->with('error', 'Anda tidak memiliki hak untuk membuat isu baru.');
        }

        // Menggunakan with query untuk optimasi performa
        $kategoriList = Kategori::orderBy('nama')->get();
        $skalaList = RefSkala::where('aktif', true)->orderBy('urutan')->get();
        $toneList = RefTone::where('aktif', true)->orderBy('urutan')->get();
        $statusList = RefStatus::where('aktif', true)->orderBy('urutan')->get();

        return view('isu.create', compact('kategoriList', 'skalaList', 'toneList', 'statusList'));
    }

    /**
     * Memperbarui isu di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Isu $isu)
    {
        // Cek hak akses berdasarkan role dan status isu
        $user = Auth::user();
        $role = $user->getHighestRoleName();
        $action = $request->input('action', 'simpan');

        // Simpan data asli untuk log perubahan
        $originalData = $isu->toArray();
        $oldStatusId = $isu->status_id;

        // Pengecekan hak akses spesifik untuk kasus khusus
        $allowAccess = false;
        
        // Admin selalu memiliki akses
        if ($user->isAdmin()) {
            $allowAccess = true;
        }
        // Editor hanya bisa mengirim isu draft yang dia buat 
        else if ($user->isEditor() && $action === 'kirim' && 
                $isu->status_id === RefStatus::getDraftId() && 
                $isu->created_by === $user->id) {
            $allowAccess = true;
        }
        // Untuk kasus lain, gunakan pengecekan standar
        else if ($isu->canBeEditedBy($role)) {
            $allowAccess = true;
        }

        if (!$allowAccess) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengedit isu ini.');
        }

        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'nullable|string',
            'skala' => 'nullable|string',
            'tone' => 'nullable|string',
            'rangkuman' => 'nullable|string|max:10000',
            'narasi_positif' => 'nullable|string|max:10000',
            'narasi_negatif' => 'nullable|string|max:10000',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url|max:255',
            'referensi_id.*' => 'nullable|exists:referensi_isus,id',
            'referensi_thumbnail_url.*' => 'nullable|url|max:1000',
        ]);

        // Simpan data asli untuk log perubahan
        $originalData = $isu->toArray();
        $oldStatusId = $isu->status_id;

        // Bersihkan data dan tambahkan default jika kosong
        $rangkuman = !empty($request->rangkuman) ? Purify::clean($request->rangkuman) : '<p>Tidak ada data</p>';
        $narasi_positif = !empty($request->narasi_positif) ? Purify::clean($request->narasi_positif) : '<p>Tidak ada data</p>';
        $narasi_negatif = !empty($request->narasi_negatif) ? Purify::clean($request->narasi_negatif) : '<p>Tidak ada data</p>';

        // Tetapkan nilai untuk skala dan tone
        $skala = !empty($validated['skala']) ? $validated['skala'] : null;
        $tone = !empty($validated['tone']) ? $validated['tone'] : null;

        // Tentukan status berdasarkan action dan role
        $newStatusId = $isu->status_id;
        $statusAction = '';

        // Logika perubahan status berdasarkan action dan role
        if ($request->has('action')) {
            list($newStatusId, $statusAction) = $this->determineNewStatus($request->action, $role, $isu->status_id);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Update isu dengan user tracking dan status baru
            $isu->update([
                'judul' => $validated['judul'],
                'tanggal' => $validated['tanggal'],
                'isu_strategis' => $request->has('isu_strategis'),
                'skala' => $skala,
                'tone' => $tone,
                'status_id' => $newStatusId,
                'rangkuman' => $rangkuman,
                'narasi_positif' => $narasi_positif,
                'narasi_negatif' => $narasi_negatif,
                'updated_by' => Auth::id(),
                'updated_at' => now(), // Explicit timestamp
            ]);

            // Proses tags kategori
            if (!empty($validated['kategori'])) {
                $kategoriInput = $validated['kategori'];
                $tags = [];

                // Jika input adalah JSON dari Tagify, decode terlebih dahulu
                if (json_decode($kategoriInput, true)) {
                    $tagsData = json_decode($kategoriInput, true);
                    if (is_array($tagsData)) {
                        $tags = array_column($tagsData, 'value');
                    }
                } else {
                    // Jika sudah comma-separated
                    $tags = array_filter(array_map('trim', explode(',', $kategoriInput)));
                }

                // Batasi jumlah tag untuk performa dan keamanan
                $tags = array_slice($tags, 0, 10); // Maksimal 20 kategori

                $kategoriIds = [];
                foreach ($tags as $tag) {
                    // Sanitasi input
                    $sanitizedTag = substr(trim($tag), 0, 50); // Batasi panjang tag
                    if (!empty($sanitizedTag)) {
                        $kategori = Kategori::firstOrCreate(['nama' => $sanitizedTag]);
                        $kategoriIds[] = $kategori->id;
                    }
                }

                $isu->kategoris()->sync($kategoriIds);
            } else {
                // Jika tidak ada kategori, hapus semua relasi
                $isu->kategoris()->sync([]);
            }

            // Perbarui atau tambahkan referensi
            if ($request->has('referensi_judul')) {
                // Dapatkan ID referensi yang ada di form
                $existingReferensiIds = $request->input('referensi_id', []);
                $existingReferensiIds = array_filter($existingReferensiIds); // Hapus nilai null/empty

                // Hapus referensi yang tidak ada lagi di form
                $isu->referensi()->whereNotIn('id', $existingReferensiIds)->delete();

                $savedRefCount = 0; // Hitung referensi yang disimpan

                foreach ($request->referensi_judul as $key => $judul) {
                    // Batasi jumlah referensi untuk performa
                    if ($savedRefCount >= 10) break; // Maksimal 10 referensi

                    if ($judul && isset($request->referensi_url[$key])) {
                        $url = $request->referensi_url[$key];
                        $thumbnail = $request->referensi_thumbnail_url[$key] ?? null;
                        $referensiId = $request->referensi_id[$key] ?? null;

                        // Jika ada ID referensi, update referensi yang ada
                        if ($referensiId) {
                            $referensi = ReferensiIsu::find($referensiId);

                            // Jika URL berubah dan thumbnail belum ada, coba ambil thumbnail baru
                            if ($referensi && $referensi->url !== $url && !$thumbnail) {
                                try {
                                    // Gunakan embed/embed untuk mendapatkan metadata dengan timeout
                                    $embed = new Embed();
                                    $info = $embed->get($url);

                                    // Ambil URL thumbnail/gambar dari metadata
                                    $thumbnail = $info->image;
                                } catch (\Exception $e) {
                                    // \Log::warning('Error fetching thumbnail: ' . $e->getMessage());
                                    $thumbnail = $referensi->thumbnail; // Gunakan thumbnail lama
                                }
                            } else if ($referensi) {
                                // Gunakan thumbnail yang sudah ada
                                $thumbnail = $thumbnail ?: $referensi->thumbnail;
                            }

                            // Update referensi dengan sanitasi
                            if ($referensi) {
                                $referensi->update([
                                    'judul' => Purify::clean($judul),
                                    'url' => $url,
                                    'thumbnail' => $thumbnail,
                                    'updated_at' => now(),
                                ]);
                                $savedRefCount++;
                            }
                        } else {
                            // Ini referensi baru, coba ambil thumbnail
                            if (!$thumbnail) {
                                try {
                                    $embed = new Embed();
                                    $info = $embed->get($url);
                                    $thumbnail = $info->image;
                                } catch (\Exception $e) {
                                    // \Log::warning('Error fetching thumbnail: ' . $e->getMessage());
                                }
                            }

                            // Buat referensi baru dengan sanitasi
                            ReferensiIsu::create([
                                'isu_id' => $isu->id,
                                'judul' => Purify::clean($judul),
                                'url' => $url,
                                'thumbnail' => $thumbnail,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $savedRefCount++;
                        }
                    }
                }
            } else {
                // Hapus semua referensi jika tidak ada di form
                $isu->referensi()->delete();
            }

            // Log perubahan status jika berubah
            if ($oldStatusId != $newStatusId) {
                LogHelper::logIsuActivity(
                    $isu->id,
                    'UPDATE',
                    'status',
                    RefStatus::getNamaById($oldStatusId),
                    RefStatus::getNamaById($newStatusId),
                    $request,
                    $newStatusId
                );
            }

            // Log perubahan field-by-field
            LogHelper::logIsuChanges(
                $isu->id,
                $originalData,
                $request->all(),
                $request,
                $newStatusId
            );

            DB::commit();

            // Gunakan pesan sukses berdasarkan aksi yang dilakukan
            $successMessage = $statusAction ? "Isu berhasil {$statusAction}!" : "Isu berhasil diperbarui!";
            AlertHelper::animationBounce('Berhasil', $successMessage);
            return redirect()->route('isu.index', $isu);

        } catch (\Exception $e) {
            DB::rollBack();

            AlertHelper::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Helper method untuk menentukan status baru berdasarkan aksi dan role
     *
     * @param string $action
     * @param string $role
     * @param int $currentStatusId
     * @return array [$newStatusId, $statusAction]
     */
    private function determineNewStatus($action, $role, $currentStatusId)
    {
        $newStatusId = $currentStatusId; // Default: status tetap sama
        $statusAction = '';

        if ($role === 'editor') {
            if ($action === 'kirim') {
                $newStatusId = RefStatus::getVerifikasi1Id();
                $statusAction = 'dikirim ke verifikator 1';
            } else if ($action === 'simpan') {
                $newStatusId = RefStatus::getDraftId();
                $statusAction = 'disimpan sebagai draft';
            }
        } elseif ($role === 'verifikator1') {
            if ($action === 'teruskan') {
                $newStatusId = RefStatus::getVerifikasi2Id();
                $statusAction = 'diteruskan ke verifikator 2';
            } else if ($action === 'simpan') {
                // Status tetap Verifikasi 1
                $statusAction = 'diperbarui oleh verifikator 1';
            }
        } elseif ($role === 'verifikator2') {
            if ($action === 'submit') {
                $newStatusId = RefStatus::getDipublikasiId();
                $statusAction = 'dipublikasikan';
            } else if ($action === 'simpan') {
                // Status tetap Verifikasi 2
                $statusAction = 'diperbarui oleh verifikator 2';
            }
        } elseif ($role === 'admin') {
            // Admin dapat mengubah ke semua status
            if ($action === 'kirim') {
                $newStatusId = RefStatus::getVerifikasi1Id();
                $statusAction = 'dikirim ke verifikator 1';
            } else if ($action === 'teruskan') {
                $newStatusId = RefStatus::getVerifikasi2Id();
                $statusAction = 'diteruskan ke verifikator 2';
            } else if ($action === 'submit') {
                $newStatusId = RefStatus::getDipublikasiId();
                $statusAction = 'dipublikasikan';
            } else if ($action === 'simpan') {
                // Status tidak berubah
                $statusAction = 'diperbarui oleh admin';
            }
        }

        return [$newStatusId, $statusAction];
    }

    /**
     * Menampilkan detail isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function show(Isu $isu)
    {

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk melihat isu.');
        }

        // Load relasi yang dibutuhkan termasuk status
        $isu->load([
            'referensi',
            'refSkala',
            'refTone', 'status',
            'creator' => function($query) {
                $query->select('id', 'name'); // Pilih hanya kolom yang dibutuhkan
            },
            'editor' => function($query) {
                $query->select('id', 'name'); // Pilih hanya kolom yang dibutuhkan
            }]);

        // Ambil metadata untuk referensi secara efisien
        foreach ($isu->referensi as $ref) {
            try {
                $metadata = ThumbnailHelper::getUrlMetadata($ref->url);
                $ref->meta_description = $metadata['description'] ?? '';
            } catch (\Exception $e) {
                // \Log::warning('Error fetching metadata for URL: ' . $ref->url, ['error' => $e->getMessage()]);
                $ref->meta_description = '';
            }
        }

        // Dapatkan log aktivitas terbaru untuk isu ini dengan eager loading
        $recentLogs = $isu->logs()
            ->with(['user' => function($query) {
                $query->select('id', 'name');
            }])
            ->latest()
            ->take(5)
            ->get();

        return view('isu.show', compact('isu', 'recentLogs'));
    }

    /**
     * Menampilkan form untuk mengedit isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function edit(Isu $isu)
    {
        // Ambil data isu beserta referensinya
        $isu->load('referensi');

        // Siapkan data untuk dropdown dan multi-select
        $kategoriList = Kategori::orderBy('nama')->get();
        $skalaList = RefSkala::where('aktif', true)->orderBy('urutan')->get();
        $toneList = RefTone::where('aktif', true)->orderBy('urutan')->get();

        // Dapatkan kategori yang sudah dipilih untuk form
        $selectedKategoris = $isu->kategoris->pluck('nama')->toArray();

        return view('isu.edit', compact(
            'isu',
            'kategoriList',
            'skalaList',
            'toneList',
            'selectedKategoris'
        ));
    }

    /**
     * Menampilkan riwayat perubahan isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function history(Isu $isu)
    {

        $user = Auth::user();
        // Cek izin akses
        if (!$user->isAdmin() && !$user->isEditor() && !$user->isVerifikator1() && !$user->isVerifikator2()) {
            abort(403, 'Unauthorized');
        }

        $logs = LogIsu::where('isu_id', $isu->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('isu.history', compact('isu', 'logs'));
    }

    /**
     * Menghapus isu dari database.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Isu $isu)
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isVerifikator2() || ($user->isEditor() && $isu->created_by == $user->id)) {
            DB::beginTransaction();
            try {
                // Log penghapusan sebelum menghapus data
                LogHelper::logIsuActivity(
                    $isu->id,
                    'DELETE',
                    null,
                    json_encode($isu->toArray()),
                    null,
                    request()
                );
                $isu->delete();

                DB::commit();

                AlertHelper::toastSuccess('Isu berhasil dihapus!', 'top-end', 3000);
                return redirect()->route('isu.index');
            } catch (\Exception $e) {
                DB::rollBack();
                AlertHelper::toastError('Terjadi kesalahan: ' . $e->getMessage());
                return redirect()->back();
            }
        } else {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus isu ini.');
        }
    }

    /**
     * Mengambil preview dari URL untuk referensi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['success' => false, 'message' => 'URL tidak diberikan'], 400);
        }

        try {
            $embed = new Embed();
            $info = $embed->get($url);

            $image = $info->image;
            $title = $info->title;

            if ($image) {
                return response()->json([
                    'success' => true,
                    'image' => $image,
                    'title' => $title,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Tidak dapat menemukan gambar'], 404);
        } catch (\Exception $e) {
            // \Log::error('Error fetching preview: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memuat preview'], 500);
        }
    }

    // Method untuk pencatatan log
    private function logActivity(Isu $isu, $keterangan)
    {
        if ($oldValue !== $newValue && LogIsu::isSignificantChange($field, $oldValue, $newValue)) {
            // Hanya catat jika perubahan signifikan
            LogIsu::create([
                'isu_id' => $isu->id,
                'user_id' => auth()->id(),
                'action' => 'UPDATE',
                'field_changed' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status_id' => $isu->status_id ?? null,
            ]);
        }
        
    }

    /**
     * Memeriksa apakah perubahan cukup signifikan untuk dicatat
     * Static method untuk digunakan di controller
     */
    public static function isSignificantChange($field, $oldValue, $newValue)
    {
        // Skip jika keduanya null atau empty string
        if (($oldValue === null || $oldValue === '') && 
            ($newValue === null || $newValue === '')) {
            return false;
        }
        
        // Jika field adalah kategori, lakukan pengecekan khusus
        if ($field === 'kategori') {
            // Pastikan nilai old_value dan new_value dalam urutan yang benar
            // Jika ini adalah penambahan kategori (nilai baru lebih banyak)
            if (is_string($oldValue) && is_string($newValue) && 
                count(explode(',', $newValue)) > count(explode(',', $oldValue))) {
                // Tukar nilai untuk mencatat dengan benar
                $tempOldValue = $oldValue;
                $oldValue = $newValue;
                $newValue = $tempOldValue;
            }
            
            // Atau jika nilai lama adalah string dan nilai baru adalah JSON
            $newValueJson = json_decode($newValue, true);
            if (is_string($oldValue) && $newValueJson !== null && isset($newValueJson[0]['value'])) {
                // Bandingkan panjang untuk menentukan mana yang lebih detail
                if (strlen($newValueJson[0]['value']) > strlen($oldValue)) {
                    // Tukar nilai jika nilai JSON memiliki lebih banyak informasi
                    $tempOldValue = $oldValue;
                    $oldValue = $newValue;
                    $newValue = $tempOldValue;
                }
            }
        }
        
        // Jika field tanggal, normalisasi dulu
        if (strpos($field, 'tanggal') !== false) {
            try {
                if (!empty($oldValue)) {
                    $oldValue = Carbon::parse($oldValue)->format('Y-m-d');
                }
                if (!empty($newValue)) {
                    $newValue = Carbon::parse($newValue)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Jika gagal parse sebagai tanggal, gunakan nilai asli
            }
        }
        
        // Untuk field HTML, normalisasi whitespace
        if (strpos($oldValue, '<') !== false || strpos($newValue, '<') !== false) {
            $oldNormalized = preg_replace('/\s+|&nbsp;/', ' ', strip_tags($oldValue));
            $newNormalized = preg_replace('/\s+|&nbsp;/', ' ', strip_tags($newValue));
            
            return trim($oldNormalized) !== trim($newNormalized);
        }
        
        // Untuk nilai JSON
        $oldJson = json_decode($oldValue, true);
        $newJson = json_decode($newValue, true);
        
        if ($oldJson !== null && $newJson !== null) {
            // Untuk format [{"value":"x"}]
            if (is_array($oldJson) && isset($oldJson[0]['value']) && 
                is_array($newJson) && isset($newJson[0]['value'])) {
                return $oldJson[0]['value'] !== $newJson[0]['value'];
            }
            
            // Fungsi untuk ekstrak nilai dari struktur JSON yang mungkin berbeda
            $extractValue = function($json) {
                if (is_array($json)) {
                    // Cek format [{"value":"x"}]
                    if (isset($json[0]['value'])) {
                        return $json[0]['value'];
                    }
                    // Cek format {"value":"x"}
                    if (isset($json['value'])) {
                        return $json['value'];
                    }
                    // Cek format ["x"]
                    if (isset($json[0]) && is_string($json[0])) {
                        return $json[0];
                    }
                }
                return $json;
            };
            
            $oldExtracted = $extractValue($oldJson);
            $newExtracted = $extractValue($newJson);
            
            // Bandingkan nilai yang sudah diekstrak
            if (is_scalar($oldExtracted) && is_scalar($newExtracted)) {
                return (string)$oldExtracted !== (string)$newExtracted;
            }
            
            // Untuk format JSON lainnya, bandingkan sebagai string yang dinormalisasi
            return json_encode($oldJson, \JSON_UNESCAPED_UNICODE) !== json_encode($newJson, \JSON_UNESCAPED_UNICODE);
        }
        
        // Pengecekan tambahan untuk perubahan format string ke JSON
        if (is_string($oldValue) && $newJson !== null) {
            // Ekstrak nilai dari JSON dan bandingkan dengan string
            if (isset($newJson[0]['value']) && $oldValue === $newJson[0]['value']) {
                return false;
            }
            
            // Coba format lain jika ada
            if (isset($newJson['value']) && $oldValue === $newJson['value']) {
                return false;
            }
            
            if (isset($newJson[0]) && is_string($newJson[0]) && $oldValue === $newJson[0]) {
                return false;
            }
        }
        
        // Pengecekan sebaliknya: dari JSON ke string
        if ($oldJson !== null && is_string($newValue)) {
            if (isset($oldJson[0]['value']) && $oldJson[0]['value'] === $newValue) {
                return false;
            }
            
            if (isset($oldJson['value']) && $oldJson['value'] === $newValue) {
                return false;
            }
            
            if (isset($oldJson[0]) && is_string($oldJson[0]) && $oldJson[0] === $newValue) {
                return false;
            }
        }
        
        // Default: perubahan signifikan jika nilai berbeda
        return $oldValue !== $newValue;
    }

    // Method untuk menampilkan form alasan penolakan
    public function formPenolakan(Isu $isu)
    {
        return view('isu.penolakan', compact('isu'));
    }

    /**
     * Memproses penolakan isu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPenolakan(Request $request, Isu $isu)
    {
        // Cek hak akses
        $user = Auth::user();
        $role = $user->getHighestRoleName();


        // Validasi input dengan pesan error yang lebih spesifik
        $validated = $request->validate([
            'alasan_penolakan' => 'required|string|min:10'
        ], [
            'alasan_penolakan.required' => 'Silakan isi alasan penolakan',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 10 karakter'
        ]);

        // Verifikasi hak akses - hanya verifikator atau admin yang bisa menolak
        if (!$user->isAdmin() && !$user->isVerifikator1() && !$user->isVerifikator2()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk menolak isu ini.');
        }

            // Begin transaction
        DB::beginTransaction();

        try {
            // Simpan status lama untuk log
            $oldStatusId = $isu->status_id;

            // Update isu dengan status ditolak dan alasan penolakan
            $isu->update([
                'status_id' => RefStatus::getDitolakId(), // Status Ditolak
                'alasan_penolakan' => $validated['alasan_penolakan'],
                'updated_by' => Auth::id()
            ]);

            // Log perubahan status
            LogHelper::logIsuActivity(
                $isu->id,
                'UPDATE',
                'status',
                RefStatus::getNamaById($oldStatusId),
                'Ditolak',
                $request,
                RefStatus::getDitolakId()
            );

            // Log alasan penolakan
            LogHelper::logIsuActivity(
                $isu->id,
                'UPDATE',
                'alasan_penolakan',
                null,
                $validated['alasan_penolakan'],
                $request,
                RefStatus::getDitolakId()
            );

            DB::commit();

            // Redirect ke halaman index dengan pesan sukses
            AlertHelper::success('Berhasil', 'Isu berhasil ditolak dengan alasan yang diberikan.', [
                'icon' => 'warning'
            ]);
            return redirect()->route('isu.index');
        } catch (\Exception $e) {
            DB::rollBack();

            AlertHelper::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Handle mass actions for multiple selected isu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massAction(Request $request)
    {
        // Validasi request
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,send-to-verif1,send-to-verif2,reject,publish,export',
            'selected_ids' => 'required',
            'rejection_reason' => 'required_if:action,reject',
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Format request tidak valid: ' . $validator->errors()->first());
        }

        // Decode selected IDs - ini adalah perbaikan utama
        try {
            // Coba decode sebagai JSON
            $selectedIds = json_decode($request->selected_ids);
            
            // Jika bukan array/object JSON, coba parse sebagai comma-separated string
            if ($selectedIds === null && json_last_error() !== JSON_ERROR_NONE) {
                $selectedIds = array_filter(explode(',', $request->selected_ids));
            }
            
            // Pastikan hasil decode adalah array
            if (!is_array($selectedIds)) {
                $selectedIds = [$selectedIds]; // Convert ke array jika bukan array
            }
            
            // Check apakah array kosong
            if (empty($selectedIds)) {
                return back()->with('error', 'Tidak ada isu yang dipilih.');
            }
            
            // Log untuk debugging
            Log::info('Mass action ids received', [
                'selected_ids_raw' => $request->selected_ids,
                'parsed_ids' => $selectedIds,
                'action' => $request->action
            ]);
        } catch (\Exception $e) {
            Log::error('Error parsing selected IDs', [
                'error' => $e->getMessage(),
                'input' => $request->selected_ids
            ]);
            return back()->with('error', 'Format ID tidak valid: ' . $e->getMessage());
        }

        // Mendapatkan pengguna dan role
        $user = Auth::user();
        $role = $user->getHighestRoleName();

        // Handle different actions
        switch ($request->action) {
            case 'delete':
                return $this->handleDeleteAction($selectedIds, $user);

            case 'send-to-verif1':
                return $this->handleSendToVerif1Action($selectedIds, $user);

            case 'send-to-verif2':
                return $this->handleSendToVerif2Action($selectedIds, $user);

            case 'reject':
                return $this->handleRejectAction($selectedIds, $user, $request->rejection_reason);

            case 'publish':
                return $this->handlePublishAction($selectedIds, $user);

            case 'export':
                return $this->handleExportAction($selectedIds, $user);

            default:
                return back()->with('error', 'Aksi tidak dikenal.');
        }
    }


    /**
     * Handle delete action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $isus
     * @param \App\Models\User $user
     * @param array $selectedIds
     * @return \Illuminate\Http\Response
     */
    private function handleDeleteAction($selectedIds, $user)
    {
        // Hanya admin dan editor yang bisa menghapus
        if (!$user->isAdmin() && !$user->isEditor()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus isu.');
        }

        DB::beginTransaction();
        try {
            // Query untuk menyeleksi isu yang akan dihapus
            $query = Isu::whereIn('id', $selectedIds);

            // Untuk editor, hanya bisa menghapus isu yang mereka buat dan masih draft
            if ($user->isEditor()) {
                $query->where('created_by', $user->id)
                      ->whereIn('status_id', [RefStatus::getDraftId(), RefStatus::getDitolakId()]);
            }

            $isusToDelete = $query->get();
            $deletedCount = 0;

            foreach ($isusToDelete as $isu) {
                // Log penghapusan sebelum menghapus data
                LogHelper::logIsuActivity(
                    $isu->id,
                    'DELETE',
                    null,
                    json_encode($isu->toArray()),
                    null,
                    request(),
                    $isu->status_id
                );

                $isu->delete();
                $deletedCount++;
            }

            DB::commit();

            if ($deletedCount > 0) {
                return back()->with('success', $deletedCount . ' isu berhasil dihapus.');
            } else {
                return back()->with('error', 'Tidak ada isu yang dihapus. Mungkin Anda tidak memiliki izin untuk menghapus isu yang dipilih.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menghapus isu: ' . $e->getMessage());
        }
    }

    /**
     * Handle send to Verifikator 1 action.
     *
     * @param array $selectedIds
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    private function handleSendToVerif1Action($selectedIds, $user)
    {
        // Hanya admin dan editor yang bisa mengirim ke Verifikator 1
        if (!$user->isAdmin() && !$user->isEditor()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengirim isu ke Verifikator 1.');
        }

        // Pastikan $selectedIds adalah array
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
            if (!is_array($selectedIds)) {
                return back()->with('error', 'Format data tidak valid.');
            }
        }

        // Periksa jika array kosong
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada isu yang dipilih.');
        }

        // Log untuk debugging
        Log::info('Sending to Verifikator 1', ['selected_ids' => $selectedIds, 'user_id' => $user->id]);

        $updatedCount = 0;
        $errors = [];

        // Mulai transaksi
        DB::beginTransaction();
        
        try {
            // Query untuk menyeleksi isu yang akan dikirim ke verifikator 1
            $query = Isu::whereIn('id', $selectedIds);

            // Untuk editor, hanya bisa mengirim isu yang mereka buat dan masih draft
            if ($user->isEditor()) {
                $query->where('created_by', $user->id)
                    ->where('status_id', RefStatus::getDraftId());
            }

            $isusToUpdate = $query->get();
            
            foreach ($isusToUpdate as $isu) {
                try {
                    // Simpan status lama untuk log
                    $oldStatusId = $isu->status_id;
                    
                    // Update status ke Verifikasi 1
                    $isu->update([
                        'status_id' => RefStatus::getVerifikasi1Id(),
                        'updated_by' => $user->id,
                        'updated_at' => now()
                    ]);

                    // Log perubahan status
                    LogHelper::logIsuActivity(
                        $isu->id,
                        'UPDATE',
                        'status',
                        RefStatus::getNamaById($oldStatusId),
                        RefStatus::getNamaById(RefStatus::getVerifikasi1Id()),
                        request(),
                        RefStatus::getVerifikasi1Id()
                    );

                    $updatedCount++;
                } catch (\Exception $e) {
                    // Catat error untuk isu tertentu tapi terus lanjutkan dengan isu lainnya
                    $errors[] = "Isu #{$isu->id}: " . $e->getMessage();
                    Log::error("Error updating isu #{$isu->id}", ['error' => $e->getMessage()]);
                }
            }

            // Jika ada yang berhasil, commit transaksi
            if ($updatedCount > 0) {
                DB::commit();

                $message = $updatedCount . ' isu berhasil dikirim ke Verifikator 1.';
                
                // Jika ada error, tambahkan ke pesan
                if (!empty($errors)) {
                    Log::warning('Some issues encountered errors', ['errors' => $errors]);
                    // Tidak perlu menampilkan pesan error karena aksi utama berhasil
                }
                
                AlertHelper::positionTopCenter('Berhasil', $message, 'success');
                return back();
            } else {
                // Tidak ada yang berhasil, rollback
                DB::rollBack();
                AlertHelper::error('Tidak Ada Perubahan', 'Tidak ada isu yang dikirim. Mungkin Anda tidak memiliki izin untuk mengirim isu yang dipilih.');
                return back();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mass action error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan saat mengirim isu ke Verifikator 1: ' . $e->getMessage());
        }
    }

    /**
     * Handle send to Verifikator 2 action.
     *
     * @param array $selectedIds
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    private function handleSendToVerif2Action($selectedIds, $user)
    {
        // Hanya admin dan verifikator 1 yang bisa mengirim ke Verifikator 2
        if (!$user->isAdmin() && !$user->hasRole('verifikator1')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengirim isu ke Verifikator 2.');
        }

        // Pastikan $selectedIds adalah array
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
            if (!is_array($selectedIds)) {
                return back()->with('error', 'Format data tidak valid.');
            }
        }

        // Periksa jika array kosong
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada isu yang dipilih.');
        }

        // Log untuk debugging
        Log::info('Sending to Verifikator 2', ['selected_ids' => $selectedIds, 'user_id' => $user->id]);

        $updatedCount = 0;
        $errors = [];

        // Mulai transaksi
        DB::beginTransaction();
        
        try {
            // Query untuk menyeleksi isu yang akan dikirim ke verifikator 2
            $query = Isu::whereIn('id', $selectedIds);

            // Verifikator 1 hanya bisa mengirim isu yang sudah dalam status Verifikasi 1
            if ($user->hasRole('verifikator1')) {
                $query->where('status_id', RefStatus::getVerifikasi1Id());
            }

            $isusToUpdate = $query->get();
            
            foreach ($isusToUpdate as $isu) {
                try {
                    // Simpan status lama untuk log
                    $oldStatusId = $isu->status_id;
                    
                    // Update status ke Verifikasi 2
                    $isu->update([
                        'status_id' => RefStatus::getVerifikasi2Id(),
                        'updated_by' => $user->id,
                        'updated_at' => now()
                    ]);

                    // Log perubahan status
                    LogHelper::logIsuActivity(
                        $isu->id,
                        'UPDATE',
                        'status',
                        RefStatus::getNamaById($oldStatusId),
                        RefStatus::getNamaById(RefStatus::getVerifikasi2Id()),
                        request(),
                        RefStatus::getVerifikasi2Id()
                    );

                    $updatedCount++;
                } catch (\Exception $e) {
                    // Catat error untuk isu tertentu tapi terus lanjutkan dengan isu lainnya
                    $errors[] = "Isu #{$isu->id}: " . $e->getMessage();
                    Log::error("Error updating isu #{$isu->id}", ['error' => $e->getMessage()]);
                }
            }

            // Jika ada yang berhasil, commit transaksi
            if ($updatedCount > 0) {
                DB::commit();
                
                // Kirim notifikasi setelah transaksi DB berhasil (pisahkan dari transaksi utama)
                foreach ($isusToUpdate as $isu) {
                    try {
                        // Kirim notifikasi ke verifikator 2 jika service tersedia
                        if (class_exists('App\\Services\\IsuNotificationService')) {
                            IsuNotificationService::notifyForVerification($isu, RefStatus::getVerifikasi2Id(), $user);
                        }
                    } catch (\Exception $e) {
                        // Logging notifikasi error tapi jangan menggagalkan seluruh operasi
                        Log::warning("Notification error for isu #{$isu->id}", ['error' => $e->getMessage()]);
                    }
                }

                $message = $updatedCount . ' isu berhasil dikirim ke Verifikator 2.';
                
                // Jika ada error, tambahkan ke pesan
                if (!empty($errors)) {
                    Log::warning('Some issues encountered errors', ['errors' => $errors]);
                    // Tidak perlu menampilkan pesan error karena aksi utama berhasil
                }
                
                return back()->with('success', $message);
            } else {
                // Tidak ada yang berhasil, rollback
                DB::rollBack();
                return back()->with('error', 'Tidak ada isu yang dikirim. Mungkin Anda tidak memiliki izin untuk mengirim isu yang dipilih.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mass action error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan saat mengirim isu ke Verifikator 2: ' . $e->getMessage());
        }
    }

    /**
     * Handle reject action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $isus
     * @param \App\Models\User $user
     * @param array $selectedIds
     * @param string $rejectionReason
     * @return \Illuminate\Http\Response
     */
    private function handleRejectAction($selectedIds, $user, $rejectionReason)
    {
        // Validasi alasan penolakan
        if (empty($rejectionReason)) {
            AlertHelper::error('Validasi Gagal', 'Alasan penolakan harus diisi.');
            return back();
        }

        // Hanya admin, verifikator 1, dan verifikator 2 yang bisa menolak
        if (!$user->isAdmin() && !$user->hasRole('verifikator1') && !$user->hasRole('verifikator2')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menolak isu.');
        }

        // Log untuk debugging
        Log::info('Processing rejection for IDs', [
            'ids' => $selectedIds,
            'user' => $user->name,
            'role' => $user->getHighestRoleName()
        ]);
        

        DB::beginTransaction();
        try {
            // Query untuk menyeleksi isu yang akan ditolak
            $query = Isu::whereIn('id', $selectedIds);

            // Verifikator 1 hanya bisa menolak isu yang status Verifikasi 1
            if ($user->hasRole('verifikator1')) {
                $query->where('status_id', RefStatus::getVerifikasi1Id());
            }

            // Verifikator 2 hanya bisa menolak isu yang status Verifikasi 2
            if ($user->hasRole('verifikator2')) {
                $query->where('status_id', RefStatus::getVerifikasi2Id());
            }

            // Log query untuk debugging jika diperlukan
            $querySQL = $query->toSql();
            Log::info('Rejection query', ['sql' => $querySQL, 'bindings' => $query->getBindings()]);

            $isusToReject = $query->get();
            $rejectedCount = 0;

            // Log jumlah isu yang akan diupdate
            Log::info('Found issues to reject', ['count' => $isusToReject->count()]);


            foreach ($isusToReject as $isu) {
                // Simpan status lama untuk log
                $oldStatusId = $isu->status_id;
                
                // Update status ke Ditolak
                $isu->update([
                    'status_id' => RefStatus::getDitolakId(),
                    'alasan_penolakan' => $rejectionReason,
                    'updated_by' => $user->id,
                    'updated_at' => now()
                ]);

                // Log perubahan status
                LogHelper::logIsuActivity(
                    $isu->id,
                    'UPDATE',
                    'status',
                    RefStatus::getNamaById($oldStatusId),
                    'Ditolak',
                    request(),
                    RefStatus::getDitolakId()
                );

                // Log alasan penolakan
                LogHelper::logIsuActivity(
                    $isu->id,
                    'UPDATE',
                    'alasan_penolakan',
                    null,
                    $rejectionReason,
                    request(),
                    RefStatus::getDitolakId()
                );

                $rejectedCount++;
            }

            DB::commit();

            if ($rejectedCount > 0) {
                AlertHelper::warning('Berhasil', $rejectedCount . ' isu berhasil ditolak.', [
                    'timer' => 4000,
                    'timerProgressBar' => true
                ]);
                return back();
            } else {
                Log::warning('No issues were rejected despite finding issues', [
                    'selected_ids' => $selectedIds,
                    'found_issues' => $isusToReject->count()
                ]);
                return back()->with('error', 'Tidak ada isu yang ditolak. Mungkin Anda tidak memiliki izin untuk menolak isu yang dipilih.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rejection error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            AlertHelper::error('Tidak Ada Perubahan', 'Tidak ada isu yang ditolak. Mungkin Anda tidak memiliki izin untuk menolak isu yang dipilih.');
            return back();
        }
    }

    /**
     * Handle publish action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $isus
     * @param \App\Models\User $user
     * @param array $selectedIds
     * @return \Illuminate\Http\Response
     */
    private function handlePublishAction($selectedIds, $user)
    {
        // Hanya admin dan verifikator 2 yang bisa publikasi
        if (!$user->isAdmin() && !$user->hasRole('verifikator2')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mempublikasikan isu.');
        }

        DB::beginTransaction();
        try {
            // Query untuk menyeleksi isu yang akan dipublikasikan
            $query = Isu::whereIn('id', $selectedIds);

            // Verifikator 2 hanya bisa publikasi isu yang status Verifikasi 2
            if ($user->hasRole('verifikator2')) {
                $query->where('status_id', RefStatus::getVerifikasi2Id());
            }

            $isusToPublish = $query->get();
            $publishedCount = 0;

            foreach ($isusToPublish as $isu) {
                // Simpan status lama untuk log
                $oldStatusId = $isu->status_id;
                
                // Update status ke Dipublikasi
                $isu->update([
                    'status_id' => RefStatus::getDipublikasiId(),
                    'updated_by' => $user->id,
                    'updated_at' => now()
                ]);

                // Log perubahan status
                LogHelper::logIsuActivity(
                    $isu->id,
                    'UPDATE',
                    'status',
                    RefStatus::getNamaById($oldStatusId),
                    RefStatus::getNamaById(RefStatus::getDipublikasiId()),
                    request(),
                    RefStatus::getDipublikasiId()
                );

                // Kirim notifikasi publikasi jika service tersedia
                // if (class_exists('App\\Services\\IsuNotificationService')) {
                //     IsuNotificationService::notifyForPublication($isu, $user);
                // }

                $publishedCount++;
            }

            DB::commit();

            if ($publishedCount > 0) {
                AlertHelper::customImage(
                    'Berhasil', 
                    $publishedCount . ' isu berhasil dipublikasikan.', 
                    'https://cdn.example.com/assets/publish-success.svg',
                    'Publikasi Berhasil'
                );
                return back();
            } else {
                AlertHelper::error('Tidak Ada Perubahan', 'Tidak ada isu yang dipublikasikan. Mungkin Anda tidak memiliki izin untuk mempublikasikan isu yang dipilih.');
                return back();            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat mempublikasikan isu: ' . $e->getMessage());
        }
    }

    //AI part
    /**
     * Display AI Create Interface
     */
    public function aiCreate()
    {
        return view('isu.ai-create');
    }

    /**
     * Display AI Results Interface
     */
    public function aiResults($sessionId = null)
    {
        if (!$sessionId) {
            return redirect()->route('isu.ai.create')
                ->with('error', 'Session ID tidak valid.');
        }

        try {
            // Get analysis result from database
            $analysisResult = AIAnalysisResult::where('session_id', $sessionId)->first();
            
            if (!$analysisResult) {
                return redirect()->route('isu.ai.create')
                    ->with('error', 'Hasil analisis tidak ditemukan.');
            }

            // Check if user has permission to view this result
            if ($analysisResult->user_id !== Auth::id()) {
                return redirect()->route('isu.ai.create')
                    ->with('error', 'Anda tidak memiliki izin untuk melihat hasil analisis ini.');
            }

            // Calculate confidence scores if not available
            $confidenceScores = $analysisResult->confidence_scores ?? $this->getDefaultConfidenceScores();
            
            return view('isu.ai-results', compact('analysisResult', 'confidenceScores'));
            
        } catch (\Exception $e) {
            Log::error('Error loading AI results', [
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('isu.ai.create')
                ->with('error', 'Terjadi kesalahan saat memuat hasil analisis.');
        }
    }

    /**
     * Process AI Analysis with improved error handling
     */
    public function aiAnalyze(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'urls' => 'required|array|min:1|max:5',
            'urls.*' => 'required|url|max:1000',
            'analysis_mode' => 'nullable|string|in:fast,balanced,accurate',
            'ai_provider' => 'nullable|string|in:auto,groq,openai,claude,gemini'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Data yang Anda masukkan tidak valid.');
        }

        try {
            // Clean and validate URLs
            $urls = array_filter($request->urls, function($url) {
                return !empty(trim($url));
            });

            if (empty($urls)) {
                return back()
                    ->withInput()
                    ->with('error', 'Minimal satu URL harus dimasukkan.');
            }

            // Quick URL validation
            foreach ($urls as $url) {
                if (!$this->webScrapingService->validateUrl($url)) {
                    return back()
                        ->withInput()
                        ->with('error', "URL tidak valid atau tidak dapat diakses: {$url}");
                }
            }

            // Prepare analysis options
            $options = [
                'analysis_mode' => $request->input('analysis_mode', 'balanced'),
                'ai_provider' => $request->input('ai_provider', 'auto'),
                'user_preferences' => $this->getUserPreferences()
            ];

            // Start AI analysis with improved error handling
            try {
                $sessionId = $this->aiAnalysisService->analyzeUrls(
                    $urls, 
                    Auth::id(), 
                    $options
                );

                // Redirect to results page with session ID
                return redirect()->route('isu.ai.results', $sessionId)
                    ->with('success', 'Analisis AI berhasil dimulai! Silakan tunggu hasilnya.');
                    
            } catch (\Exception $analysisException) {
                // Parse dan handle specific AI errors
                $userFriendlyError = $this->parseAIError($analysisException);
                
                Log::error('AI analysis failed to start', [
                    'user_id' => Auth::id(),
                    'urls' => $urls,
                    'error' => $analysisException->getMessage(),
                    'parsed_error' => $userFriendlyError
                ]);
                
                return back()
                    ->withInput()
                    ->with('error', $userFriendlyError['message'])
                    ->with('error_details', $userFriendlyError['details'] ?? null);
            }
            
        } catch (\Exception $e) {
            Log::error('General AI analysis error', [
                'user_id' => Auth::id(),
                'urls' => $request->urls ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi dalam beberapa menit.');
        }
    }

    /**
     * Parse AI service errors into user-friendly messages
     */
    private function parseAIError(\Exception $exception): array
    {
        $message = $exception->getMessage();
        
        // Rate limit error detection
        if (str_contains($message, 'rate_limit_exceeded') || str_contains($message, 'Too Many Requests')) {
            return $this->parseRateLimitError($message);
        }
        
        // API connection errors
        if (str_contains($message, 'Connection timeout') || str_contains($message, 'cURL error')) {
            return [
                'message' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi dalam beberapa menit.',
                'details' => [
                    'type' => 'connection_error',
                    'suggestion' => 'Periksa koneksi internet Anda atau coba lagi nanti.'
                ]
            ];
        }
        
        // Authentication errors
        if (str_contains($message, 'Unauthorized') || str_contains($message, 'Invalid API key')) {
            return [
                'message' => 'Konfigurasi layanan AI bermasalah. Silakan hubungi administrator.',
                'details' => [
                    'type' => 'auth_error',
                    'suggestion' => 'Hubungi tim teknis untuk pemeriksaan konfigurasi API.'
                ]
            ];
        }
        
        // Content length errors
        if (str_contains($message, 'content too long') || str_contains($message, 'token limit')) {
            return [
                'message' => 'Konten terlalu panjang untuk diproses. Coba dengan URL yang lebih sedikit.',
                'details' => [
                    'type' => 'content_length_error',
                    'suggestion' => 'Kurangi jumlah URL atau pilih artikel yang lebih pendek.'
                ]
            ];
        }
        
        // Generic API errors
        if (str_contains($message, 'Groq API failed') || str_contains($message, 'API request failed')) {
            return [
                'message' => 'Layanan analisis AI sedang mengalami gangguan. Silakan coba lagi nanti.',
                'details' => [
                    'type' => 'api_error',
                    'suggestion' => 'Sistem akan kembali normal dalam beberapa menit.'
                ]
            ];
        }
        
        // Undefined array key "results" - specific fix for your issue
        if (str_contains($message, 'Undefined array key "results"')) {
            return [
                'message' => 'Layanan AI tidak dapat menyelesaikan analisis. Silakan coba lagi.',
                'details' => [
                    'type' => 'processing_error',
                    'suggestion' => 'Tunggu beberapa menit dan coba lagi, atau gunakan mode manual.'
                ]
            ];
        }
        
        // Default fallback
        return [
            'message' => 'Gagal memulai analisis AI. Silakan coba lagi atau gunakan mode manual.',
            'details' => [
                'type' => 'unknown_error',
                'suggestion' => 'Jika masalah berlanjut, hubungi administrator sistem.'
            ]
        ];
    }

    /**
     * Parse rate limit specific errors and extract timing information
     */
    private function parseRateLimitError(string $errorMessage): array
    {
        // Extract wait time from error message
        $waitTime = null;
        if (preg_match('/try again in ([\d.]+)s/', $errorMessage, $matches)) {
            $waitTime = (float) $matches[1];
        } elseif (preg_match('/try again in ([\d.]+) seconds/', $errorMessage, $matches)) {
            $waitTime = (float) $matches[1];
        }
        
        // Extract usage information
        $currentUsage = null;
        $limit = null;
        if (preg_match('/Used (\d+)/', $errorMessage, $matches)) {
            $currentUsage = (int) $matches[1];
        }
        if (preg_match('/Limit (\d+)/', $errorMessage, $matches)) {
            $limit = (int) $matches[1];
        }
        
        // Build user-friendly message
        if ($waitTime) {
            $waitMinutes = ceil($waitTime / 60);
            $message = "Layanan AI sedang sibuk. Silakan coba lagi dalam {$waitMinutes} menit.";
            
            if ($waitTime < 60) {
                $waitSeconds = ceil($waitTime);
                $message = "Layanan AI sedang sibuk. Silakan coba lagi dalam {$waitSeconds} detik.";
            }
        } else {
            $message = "Layanan AI mencapai batas maksimum. Silakan coba lagi dalam beberapa menit.";
        }
        
        return [
            'message' => $message,
            'details' => [
                'type' => 'rate_limit',
                'wait_time' => $waitTime,
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'suggestion' => 'Gunakan mode manual sementara atau tunggu hingga layanan tersedia kembali.'
            ]
        ];
    }

    /**
     * Get AI Analysis Status with enhanced error info
     */
    public function aiStatus($sessionId)
    {
        try {
            $status = $this->aiAnalysisService->getAnalysisStatus($sessionId);
            
            // Check if status indicates an error
            if (isset($status['status']) && $status['status'] === 'error') {
                // Parse error for better user display
                if (isset($status['error_message'])) {
                    $parsedError = $this->parseAIError(new \Exception($status['error_message']));
                    $status['user_friendly_error'] = $parsedError;
                }
            }
            
            // Add additional status information
            $status['timestamp'] = now()->toISOString();
            $status['user_can_edit'] = true;
            
            return response()->json($status);
            
        } catch (\Exception $e) {
            Log::error('Error getting AI status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            $parsedError = $this->parseAIError($e);
            
            return response()->json([
                'status' => 'error',
                'message' => $parsedError['message'],
                'user_friendly_error' => $parsedError
            ], 500);
        }
    }

    /**
     * Enhanced AI Store method with better error handling
     */
    public function aiStore(Request $request)
    {
        // Validate the AI results before storing
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date|before_or_equal:today',
            'isu_strategis' => 'boolean',
            'kategori' => 'nullable|string',
            'skala' => 'required|string|in:rendah,sedang,tinggi',
            'tone' => 'required|string|in:positif,negatif,netral',
            'rangkuman' => 'required|string|max:10000',
            'narasi_positif' => 'required|string|max:10000',
            'narasi_negatif' => 'required|string|max:10000',
            'referensi_urls' => 'nullable|array',
            'referensi_urls.*' => 'url|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Get the analysis result to verify ownership
            $analysisResult = AIAnalysisResult::where('session_id', $request->session_id)->first();
            
            if (!$analysisResult || $analysisResult->user_id !== Auth::id()) {
                throw new \Exception('Session tidak valid atau Anda tidak memiliki izin.');
            }

            // Map skala to ID from RefSkala
            $skalaId = null;
            if (!empty($request->skala)) {
                $skalaRef = RefSkala::where('nama', $request->skala)->first();
                $skalaId = $skalaRef ? $skalaRef->id : null;
            }

            $toneId = null;
            if (!empty($request->tone)) {
                $toneRef = RefTone::where('nama', $request->tone)->first();
                $toneId = $toneRef ? $toneRef->id : null;
            }

            // Create new Isu from AI results
            $isu = Isu::create([
                'judul' => trim($request->judul),
                'tanggal' => $request->tanggal,
                'isu_strategis' => $request->boolean('isu_strategis'),
                'skala' => $skalaId,
                'tone' => $toneId,
                'rangkuman' => $this->cleanHtmlContent($request->rangkuman),
                'narasi_positif' => $this->cleanHtmlContent($request->narasi_positif),
                'narasi_negatif' => $this->cleanHtmlContent($request->narasi_negatif),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'status_id' => RefStatus::getDraftId(), // Always start as draft
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add reference URLs if provided
            if ($request->has('referensi_urls') && is_array($request->referensi_urls)) {
                foreach ($request->referensi_urls as $url) {
                    if (!empty(trim($url))) {
                        $this->addReferenceUrl($isu, trim($url));
                    }
                }
            }

            // Log the creation
            LogHelper::logIsuActivity(
                $isu->id,
                'CREATE',
                null,
                null,
                json_encode($isu->toArray()),
                $request,
                RefStatus::getDraftId()
            );

            DB::commit();

            return redirect()->route('isu.show', $isu->id)
                ->with('success', 'Isu berhasil dibuat dari hasil AI.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to store AI results as Isu', [
                'session_id' => $request->session_id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            $parsedError = $this->parseAIError($e);

            return response()->json([
                'success' => false,
                'message' => $parsedError['message'],
                'error_details' => $parsedError['details'] ?? null
            ], 500);
        }
    }

    /**
     * Preview AI Analysis Results (AJAX endpoint)
     */
    public function aiPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'urls' => 'required|array|min:1|max:2', // Limit for preview
            'urls.*' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'URL tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $previews = [];
            
            foreach ($request->urls as $url) {
                $extraction = $this->webScrapingService->extractContent($url);
                
                if ($extraction['success']) {
                    $previews[] = [
                        'url' => $url,
                        'title' => $extraction['title'] ?? 'Tidak ada judul',
                        'excerpt' => Str::limit($extraction['content'] ?? '', 200),
                        'domain' => parse_url($url, PHP_URL_HOST),
                        'suitable' => $this->webScrapingService->isContentSuitable($extraction)
                    ];
                } else {
                    $previews[] = [
                        'url' => $url,
                        'title' => 'Gagal memuat',
                        'excerpt' => 'Konten tidak dapat diakses',
                        'domain' => parse_url($url, PHP_URL_HOST),
                        'suitable' => ['suitable' => false, 'issues' => ['URL tidak dapat diakses']]
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'previews' => $previews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper Methods
     */
    private function getUserPreferences(): array
    {
        return [
            'language' => 'id',
            'tone_preference' => 'balanced',
            'length_preference' => 'medium'
        ];
    }

    private function getDefaultConfidenceScores(): array
    {
        return [
            'resume' => 85,
            'judul' => 80,
            'narasi_positif' => 82,
            'narasi_negatif' => 82,
            'tone' => 90,
            'skala' => 85
        ];
    }

    private function cleanHtmlContent(string $content): string
    {
        // Remove dangerous HTML and clean content
        return Purify::clean($content);
    }

    private function addReferenceUrl(Isu $isu, string $url): void
    {
        try {
            // Extract metadata from URL
            $metadata = $this->webScrapingService->extractMetadata($url);
            
            ReferensiIsu::create([
                'isu_id' => $isu->id,
                'judul' => $metadata['title'] ?? 'Referensi',
                'url' => $url,
                'thumbnail_url' => $metadata['image'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to add reference URL', [
                'isu_id' => $isu->id,
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Export isu yang dipublikasikan per hari ke PDF
     */
    public function exportDailyPdf(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date|before_or_equal:today',
            'format' => 'nullable|in:detail,ringkas'
        ]);

        $tanggal = $validated['tanggal'];
        $format = $validated['format'] ?? 'detail';

        try {
            // Ambil isu yang dipublikasikan
            $isus = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator'])
                ->whereDate('tanggal', $tanggal)
                ->where('status_id', RefStatus::getDipublikasiId())
                ->orderBy('created_at', 'asc')
                ->get();

            if ($isus->isEmpty()) {
                return back()
                    ->withInput()
                    ->with('error', 'Tidak ada isu yang dipublikasikan pada tanggal ' . date('d F Y', strtotime($tanggal)));
            }

            // Handle logo untuk PDF
            $logoPath = public_path('logo.png');
            $logoBase64 = null;
            
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $logoBase64 = base64_encode($logoData);
            }

            // Generate PDF dengan data logo
            $pdf = PDF::loadView('isu.export-daily-pdf', [
                'isus' => $isus,
                'tanggal' => $tanggal,
                'format' => $format,
                'exported_at' => now(),
                'exported_by' => auth()->user(),
                'logo_base64' => $logoBase64  // Pass logo ke template
            ])
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'chroot' => public_path(),
                'logOutputFile' => storage_path('logs/dompdf.log'),
                'isPhpEnabled' => false
            ]);

            $filename = 'laporan-isu-harian-' . date('Y-m-d', strtotime($tanggal)) . '-' . date('His') . '.pdf';

            // Log successful export
            \Log::info('PDF Export Success', [
                'user_id' => auth()->id(),
                'export_date' => $tanggal,
                'isu_count' => $isus->count(),
                'filename' => $filename,
                'has_logo' => !is_null($logoBase64)
            ]);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('PDF Export Error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'export_date' => $tanggal
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat membuat PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show export form for daily PDF
     *
     * @return \Illuminate\View\View
     */
    public function showExportForm()
    {
        // Ambil tanggal-tanggal yang memiliki isu dipublikasikan
        $availableDates = Isu::where('status_id', RefStatus::getDipublikasiId())
            ->selectRaw('DATE(tanggal) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30) // 30 hari terakhir
            ->get();

        \Log::info('Available dates for export:', $availableDates->toArray());
        return view('isu.export-form', compact('availableDates'));
    }

    /**
     * AJAX endpoint untuk preview count isu
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreviewCount(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date'
        ]);

        $tanggal = $request->tanggal;
        
        // Hitung isu yang dipublikasikan pada tanggal tersebut
        $count = Isu::whereDate('tanggal', $tanggal)
            ->where('status_id', RefStatus::getDipublikasiId())
            ->count();
        
        // Ambil detail isu untuk preview
        $isus = Isu::with(['refTone', 'refSkala'])
            ->whereDate('tanggal', $tanggal)
            ->where('status_id', RefStatus::getDipublikasiId())
            ->select('id', 'judul', 'isu_strategis', 'tone', 'skala')
            ->get();
        
        $summary = [
            'total' => $count,
            'strategis' => $isus->where('isu_strategis', true)->count(),
            'biasa' => $isus->where('isu_strategis', false)->count(),
            'tone_breakdown' => [
                'positif' => $isus->filter(function($isu) {
                    return $isu->refTone && strtolower($isu->refTone->nama) == 'positif';
                })->count(),
                'negatif' => $isus->filter(function($isu) {
                    return $isu->refTone && strtolower($isu->refTone->nama) == 'negatif';
                })->count(),
                'netral' => $isus->filter(function($isu) {
                    return !$isu->refTone || strtolower($isu->refTone->nama) == 'netral';
                })->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'count' => $count,
            'summary' => $summary,
            'message' => $count > 0 ? 
                "Ditemukan {$count} isu yang dipublikasikan pada tanggal " . date('d F Y', strtotime($tanggal)) :
                "Tidak ada isu yang dipublikasikan pada tanggal " . date('d F Y', strtotime($tanggal))
        ]);
    }

    /**
     * Get count of published issues for a specific date (AJAX)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExportPreview(Request $request)
    {
        try {
            $request->validate([
                'tanggal' => 'required|date'
            ]);

            $tanggal = $request->tanggal;

            \Log::info('Preview request for date: ' . $tanggal);
            
            // Query isu yang dipublikasikan
            $isusQuery = Isu::with(['refTone', 'refSkala', 'kategoris'])
                ->whereDate('tanggal', $tanggal)
                ->where('status_id', RefStatus::getDipublikasiId());
            
            $count = $isusQuery->count();
            
            \Log::info('Found count: ' . $count . ' for date: ' . $tanggal);
            
            if ($count > 0) {
                $isus = $isusQuery->get();
                
                // Hitung breakdown
                $strategis = $isus->where('isu_strategis', true)->count();
                $biasa = $isus->where('isu_strategis', false)->count();
                
                $toneBreakdown = [
                    'positif' => $isus->filter(function($isu) {
                        return $isu->refTone && strtolower($isu->refTone->nama) === 'positif';
                    })->count(),
                    'negatif' => $isus->filter(function($isu) {
                        return $isu->refTone && strtolower($isu->refTone->nama) === 'negatif';
                    })->count(),
                    'netral' => $isus->filter(function($isu) {
                        return !$isu->refTone || strtolower($isu->refTone->nama) === 'netral';
                    })->count(),
                ];

                $skalaBreakdown = [
                    'nasional' => $isus->filter(function($isu) {
                        return $isu->refSkala && strtolower($isu->refSkala->nama) === 'nasional';
                    })->count(),
                    'regional' => $isus->filter(function($isu) {
                        return $isu->refSkala && strtolower($isu->refSkala->nama) === 'regional';
                    })->count(),
                    'lokal' => $isus->filter(function($isu) {
                        return !$isu->refSkala || strtolower($isu->refSkala->nama) === 'lokal';
                    })->count(),
                ];

                return response()->json([
                    'success' => true,
                    'count' => $count,
                    'breakdown' => [
                        'strategis' => $strategis,
                        'biasa' => $biasa,
                        'tone' => $toneBreakdown,
                        'skala' => $skalaBreakdown
                    ],
                    'message' => "Siap untuk diekspor: {$count} isu pada " . date('d F Y', strtotime($tanggal)),
                    'formatted_date' => date('d F Y', strtotime($tanggal))
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'count' => 0,
                    'message' => 'Tidak ada isu yang dipublikasikan pada tanggal ' . date('d F Y', strtotime($tanggal)),
                    'formatted_date' => date('d F Y', strtotime($tanggal))
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error in export preview: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'count' => 0,
                'message' => 'Terjadi kesalahan saat memuat data. Silakan coba lagi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug method untuk testing export functionality
     * Hapus method ini setelah fitur berfungsi normal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugExport()
    {
        try {
            $debugInfo = [
                'total_isu' => Isu::count(),
                'published_isu' => Isu::where('status_id', RefStatus::getDipublikasiId())->count(),
                'dipublikasi_status_id' => RefStatus::getDipublikasiId(),
                'all_statuses' => RefStatus::all()->pluck('nama', 'id')->toArray(),
                'sample_isu' => Isu::with(['status', 'refTone', 'refSkala'])
                    ->where('status_id', RefStatus::getDipublikasiId())
                    ->first(),
                'available_dates_raw' => Isu::where('status_id', RefStatus::getDipublikasiId())
                    ->selectRaw('DATE(tanggal) as date, COUNT(*) as count, MIN(tanggal) as first_time, MAX(tanggal) as last_time')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(10)
                    ->get(),
                'recent_isu_dates' => Isu::orderBy('tanggal', 'desc')
                    ->limit(10)
                    ->pluck('tanggal', 'id'),
                'current_user' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'roles' => auth()->user()->roles->pluck('name')
                ]
            ];

            return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Test route untuk simulasi data (development only)
     */
    public function createTestData()
    {
        if (!app()->isLocal()) {
            abort(403, 'Only available in local environment');
        }

        try {
            // Buat beberapa isu test dengan status dipublikasikan
            $testDates = [
                now()->format('Y-m-d'),
                now()->subDay()->format('Y-m-d'),
                now()->subDays(2)->format('Y-m-d'),
            ];

            foreach ($testDates as $date) {
                $isu = Isu::create([
                    'judul' => 'Test Isu untuk Export - ' . $date,
                    'tanggal' => $date . ' 10:00:00',
                    'isu_strategis' => true,
                    'skala' => 1, // Sesuaikan dengan ID skala yang ada
                    'tone' => 1,  // Sesuaikan dengan ID tone yang ada
                    'status_id' => RefStatus::getDipublikasiId(),
                    'rangkuman' => '<p>Ini adalah rangkuman test untuk isu tanggal ' . $date . '</p>',
                    'narasi_positif' => '<p>Narasi positif untuk testing export PDF.</p>',
                    'narasi_negatif' => '<p>Narasi negatif untuk testing export PDF.</p>',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Tambahkan referensi test
                $isu->referensi()->create([
                    'judul' => 'Berita Test - ' . $date,
                    'url' => 'https://example.com/berita-' . $date,
                    'description' => 'Deskripsi berita test'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test data created successfully',
                'created_dates' => $testDates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
