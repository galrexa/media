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
use App\Helpers\ThumbnailHelper;
use App\Models\User;
use App\Models\LogIsu;
use App\Services\IsuNotificationService;
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
use Carbon\Carbon;

class IsuController extends Controller
{

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
            'rangkuman' => 'nullable|string',
            'narasi_positif' => 'nullable|string|max:1000',
            'narasi_negatif' => 'nullable|string|max:1000',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => [
                'nullable',
                'url',
                'max:1000',
                // Validasi URL untuk mencegah injeksi
                function ($attribute, $value, $fail) {
                    $blockedDomains = ['evil.com', 'malicious.org']; // Contoh domain yang diblokir
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

            return redirect()->route('isu.show', $isu)
                            ->with('success', "Isu berhasil {$statusMessage}!");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                        ->withInput()
                        ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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

        // Jika mengakses halaman dengan filter "rejected", reset badge dengan lebih menyeluruh
        if ($filterStatus === 'rejected') {
            // Simpan ke session
            Session::put('rejected_badge_hidden', true);

            // Simpan ke cache untuk persistensi lebih lama
            $cacheKey = 'rejected_badge_hidden_' . $userId;
            Cache::put($cacheKey, true, now()->addDays(7)); // Simpan selama 7 hari

            // Log aksi untuk audit
            Log::info("Badge ditolak direset oleh User ID: {$userId} ({$user->name}) melalui akses halaman");
        }

        // Base queries dengan eager loading termasuk status
        $isusStrategisQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator'])
                                ->where('isu_strategis', true);

        $isusLainnyaQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris', 'status', 'creator'])
                                ->where('isu_strategis', false);

        // Filter dari sidebar (filter_status)
        if ($request->has('filter_status')) {
            $filterStatus = $request->input('filter_status');

            switch($filterStatus) {
                case 'draft':
                    // Status Draft
                    $isusStrategisQuery->where('status_id', RefStatus::getDraftId());
                    $isusLainnyaQuery->where('status_id', RefStatus::getDraftId());
                    break;
                case 'verifikasi1':
                    // Status Verifikasi 1
                    $isusStrategisQuery->where('status_id', RefStatus::getVerifikasi1Id());
                    $isusLainnyaQuery->where('status_id', RefStatus::getVerifikasi1Id());
                    break;
                case 'verifikasi2':
                    // Status Verifikasi 2
                    $isusStrategisQuery->where('status_id', RefStatus::getVerifikasi2Id());
                    $isusLainnyaQuery->where('status_id', RefStatus::getVerifikasi2Id());
                    break;
                case 'rejected':
                    // Status Ditolak
                    $isusStrategisQuery->where('status_id', RefStatus::getDitolakId());
                    $isusLainnyaQuery->where('status_id', RefStatus::getDitolakId());
                    break;
            }
        } else {
            // Filter berdasarkan role jika tidak ada filter sidebar
            if ($user->isEditor()) {
                // Editor hanya melihat isu yang dibuat olehnya
                $isusStrategisQuery->where('created_by', $user->id);
                $isusLainnyaQuery->where('created_by', $user->id);
            } elseif ($user->hasRole('verifikator1')) {
                // Verifikator 1 melihat isu yang perlu diperiksa dan yang telah disetujui
                $isusStrategisQuery->whereIn('status_id', [
                    RefStatus::getVerifikasi1Id(),
                    RefStatus::getVerifikasi2Id(),
                    RefStatus::getDipublikasiId(),
                    RefStatus::getDitolakId()
                ]);
                $isusLainnyaQuery->whereIn('status_id', [
                    RefStatus::getVerifikasi1Id(),
                    RefStatus::getVerifikasi2Id(),
                    RefStatus::getDipublikasiId(),
                    RefStatus::getDitolakId()
                ]);
            } elseif ($user->hasRole('verifikator2')) {
                // Verifikator 2 melihat isu yang perlu diperiksa dan yang telah dipublikasikan
                $isusStrategisQuery->whereIn('status_id', [
                    RefStatus::getVerifikasi1Id(),
                    RefStatus::getVerifikasi2Id(),
                    RefStatus::getDipublikasiId(),
                    RefStatus::getDitolakId()
                ]);
                $isusLainnyaQuery->whereIn('status_id', [
                    RefStatus::getVerifikasi1Id(),
                    RefStatus::getVerifikasi2Id(),
                    RefStatus::getDipublikasiId(),
                    RefStatus::getDitolakId()
                ]);
            }
            // Admin melihat semua isu (tidak ada filter tambahan)
        }

        // Filter berdasarkan status dari form jika dipilih
        if ($request->filled('status')) {
            $statusId = (int) $request->input('status');
            $isusStrategisQuery->where('status_id', $statusId);
            $isusLainnyaQuery->where('status_id', $statusId);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
            $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();

            $isusStrategisQuery->whereBetween('tanggal', [$dateFrom, $dateTo]);
            $isusLainnyaQuery->whereBetween('tanggal', [$dateFrom, $dateTo]);
        }

        // Pencarian global
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';

            // Menggunakan parameter binding untuk mencegah SQL injection
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
        $sortField = $request->get('sort', 'tanggal');
        $sortDirection = in_array(strtolower($request->get('direction', 'desc')), ['asc', 'desc'])
            ? strtolower($request->get('direction', 'desc'))
            : 'desc';


        // Whitelist kolom yang diizinkan untuk sorting, tambahkan status_id
        $allowedSortFields = ['tanggal', 'skala', 'tone', 'status_id'];

        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'tanggal';

        // Sorting normal
        $isusStrategisQuery->orderBy($sortField, $sortDirection);
        $isusLainnyaQuery->orderBy($sortField, $sortDirection);

        $isusStrategis = $isusStrategisQuery->paginate(10, ['*'], 'strategis');
        $isusLainnya = $isusLainnyaQuery->paginate(10, ['*'], 'lainnya');

        // Pastikan semua parameter disertakan di URL pagination
        $isusStrategis->appends($request->except('strategis'));
        $isusLainnya->appends($request->except('lainnya'));

        // Dapatkan daftar status untuk dropdown filter
        $statusList = RefStatus::getActive();

        // Kirim informasi tambahan ke view bahwa ini adalah halaman dengan filter rejected
        $isRejectedPage = ($filterStatus === 'rejected');

        return view('isu.index', compact('isusStrategis', 'isusLainnya', 'statusList', 'isRejectedPage'));
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

        // Validasi hak akses
        if (!$user->isAdmin() && !$isu->canBeEditedBy($role)) {
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
            'rangkuman' => 'nullable|string|max:1000',
            'narasi_positif' => 'nullable|string|max:1000',
            'narasi_negatif' => 'nullable|string|max:1000',
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
        // Gunakan null jika tidak ada nilai yang dipilih
        $skala = !empty($validated['skala']) ? $validated['skala'] : null;
        $tone = !empty($validated['tone']) ? $validated['tone'] : null;

        // Tentukan status berdasarkan action dan role
        $newStatusId = $isu->status_id; // Default: status tetap sama
        $statusAction = '';

        // Logika perubahan status berdasarkan action dan role
        if ($request->has('action')) {
            // Encapsulate complex status change logic in a separate method
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
            return redirect()->route('isu.show', $isu)
                            ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error untuk analisis
            /* \Log::error('Error saat memperbarui isu: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'isu_id' => $isu->id,
                'trace' => $e->getTraceAsString()
            ]); */

            return redirect()->back()
                        ->withInput()
                        ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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

        // if (!$user->isAdmin() && !$user->isVerifikator1() && !$user->isVerifikator2() &&
        //     $isu->created_by != $user->id) {
        //     return redirect()->route('isu.index')
        //         ->with('error', 'Anda tidak memiliki hak akses untuk melihat isu ini.');
        // }

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
        // Mengubah cek akses untuk mengizinkan verifikator1 dan verifikator2
        $user = Auth::user();
        $role = $user->getHighestRoleName();

        // Cek apakah user berhak mengedit isu ini berdasarkan role dan status
        if (!$isu->canBeEditedBy($role)) {
            return redirect()->route('isu.index')
                ->with('error', 'Anda tidak memiliki hak untuk mengedit isu ini.');
        }

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
        if (!auth()->user()->isAdmin() && !auth()->user()->isEditor() && $isu->created_by != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Ambil log dengan eager loading user
        $logs = $isu->logs()->with('user')->paginate(20);

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
        // Perbaikan: Menggunakan isAdmin() bukan hasRole()
        if (!auth()->user()->isAdmin() && !auth()->user()->isEditor() && $isu->created_by != auth()->id()) {
            abort(403, 'Unauthorized');
        }

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

        return redirect()->route('isu.index')
                         ->with('success', 'Isu berhasil dihapus!');
                        } catch (\Exception $e) {
                            DB::rollBack();
                            return redirect()->back()
                                            ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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
        LogIsu::create([
            'isu_id' => $isu->id,
            'user_id' => Auth::id(),
            'keterangan' => $keterangan,
            'status_id' => $isu->status_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
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
            return redirect()->route('isu.index')
                ->with('success', 'Isu berhasil ditolak dengan alasan yang diberikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log error untuk debugging
            // \Log::error('Error saat menolak isu: ' . $e->getMessage(), [
            //     'user_id' => Auth::id(),
            //     'isu_id' => $isu->id,
            //     'trace' => $e->getTraceAsString()
            // ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Format request tidak valid.');
        }

        // Decode selected IDs
        try {
            $selectedIds = json_decode($request->selected_ids, true);
        } catch (\Exception $e) {
            return back()->with('error', 'Format ID tidak valid.');
        }

        // Check apakah array kosong
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada isu yang dipilih.');
        }

        // Mendapatkan pengguna dan role
        $user = Auth::user();
        $role = $user->getHighestRoleName();

        // Mendapatkan isu yang dipilih
        $isus = Isu::whereIn('id', $selectedIds);

        // Handle different actions
        switch ($request->action) {
            case 'delete':
                return $this->handleDeleteAction($isus, $user, $selectedIds);

            case 'send-to-verif1':
                return $this->handleSendToVerif1Action($isus, $user, $selectedIds);

            case 'send-to-verif2':
                return $this->handleSendToVerif2Action($isus, $user, $selectedIds);

            case 'reject':
                return $this->handleRejectAction($isus, $user, $selectedIds, $request->rejection_reason);

            case 'publish':
                return $this->handlePublishAction($isus, $user, $selectedIds);

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
    private function handleDeleteAction($isus, $user, $selectedIds)
    {
        // Hanya admin dan editor yang bisa menghapus
        if (!$user->isAdmin() && !$user->isEditor()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus isu.');
        }

        $query = clone $isus;

        // Untuk editor, hanya bisa menghapus isu yang mereka buat dan masih draft
        if ($user->isEditor()) {
            $query->where('created_by', $user->id)
                ->where('status_id', RefStatus::getDraftId());
        }

        $deletedCount = $query->delete();

        if ($deletedCount > 0) {
            return back()->with('success', $deletedCount . ' isu berhasil dihapus.');
        } else {
            return back()->with('error', 'Tidak ada isu yang dihapus. Mungkin Anda tidak memiliki izin untuk menghapus isu yang dipilih.');
        }
    }

    /**
     * Handle send to Verifikator 1 action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $isus
     * @param \App\Models\User $user
     * @param array $selectedIds
     * @return \Illuminate\Http\Response
     */
    private function handleSendToVerif1Action($isus, $user, $selectedIds)
    {
        // Hanya admin dan editor yang bisa mengirim ke Verifikator 1
        if (!$user->isAdmin() && !$user->isEditor()) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengirim isu ke Verifikator 1.');
        }

        $query = clone $isus;

        // Untuk editor, hanya bisa mengirim isu yang mereka buat dan masih draft
        if ($user->isEditor()) {
            $query->where('created_by', $user->id)
                ->where('status_id', RefStatus::getDraftId());
        }

        // Update status ke Verifikasi 1
        $updatedCount = $query->update([
            'status_id' => RefStatus::getVerifikasi1Id(),
            'updated_by' => $user->id,
            'updated_at' => now()
        ]);

        // Log perubahan dan kirim notifikasi
        foreach ($query->get() as $isu) {
            LogIsu::create([
                'isu_id' => $isu->id,
                'user_id' => $user->id,
                'keterangan' => 'Isu dikirim ke Verifikator 1',
                'status_id' => RefStatus::getVerifikasi1Id()
            ]);

            // Kirim notifikasi ke verifikator 1
            IsuNotificationService::notifyForVerification($isu, RefStatus::getVerifikasi1Id(), $user);
        }

        if ($updatedCount > 0) {
            return back()->with('success', $updatedCount . ' isu berhasil dikirim ke Verifikator 1.');
        } else {
            return back()->with('error', 'Tidak ada isu yang dikirim. Mungkin Anda tidak memiliki izin untuk mengirim isu yang dipilih.');
        }
    }

    /**
     * Handle send to Verifikator 2 action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $isus
     * @param \App\Models\User $user
     * @param array $selectedIds
     * @return \Illuminate\Http\Response
     */
    private function handleSendToVerif2Action($isus, $user, $selectedIds)
    {
        // Hanya admin dan verifikator 1 yang bisa mengirim ke Verifikator 2
        if (!$user->isAdmin() && !$user->hasRole('verifikator1')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengirim isu ke Verifikator 2.');
        }

        $query = clone $isus;

        // Verifikator 1 hanya bisa mengirim isu yang sudah dalam status Verifikasi 1
        if ($user->hasRole('verifikator1')) {
            $query->where('status_id', RefStatus::getVerifikasi1Id());
        }

        // Update status ke Verifikasi 2
        $updatedCount = $query->update([
            'status_id' => RefStatus::getVerifikasi2Id(),
            'updated_by' => $user->id,
            'updated_at' => now()
        ]);

        // Log perubahan dan kirim notifikasi
        foreach ($query->get() as $isu) {
            LogIsu::create([
                'isu_id' => $isu->id,
                'user_id' => $user->id,
                'keterangan' => 'Isu dikirim ke Verifikator 2',
                'status_id' => RefStatus::getVerifikasi2Id()
            ]);

            // Kirim notifikasi ke verifikator 2
            IsuNotificationService::notifyForVerification($isu, RefStatus::getVerifikasi2Id(), $user);
        }

        if ($updatedCount > 0) {
            return back()->with('success', $updatedCount . ' isu berhasil dikirim ke Verifikator 2.');
        } else {
            return back()->with('error', 'Tidak ada isu yang dikirim. Mungkin Anda tidak memiliki izin untuk mengirim isu yang dipilih.');
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
    private function handleRejectAction($isus, $user, $selectedIds, $rejectionReason)
    {
        // Validasi alasan penolakan
        if (empty($rejectionReason)) {
            return back()->with('error', 'Alasan penolakan harus diisi.');
        }

        // Hanya admin, verifikator 1, dan verifikator 2 yang bisa menolak
        if (!$user->isAdmin() && !$user->hasRole('verifikator1') && !$user->hasRole('verifikator2')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menolak isu.');
        }

        $query = clone $isus;

        // Verifikator 1 hanya bisa menolak isu yang status Verifikasi 1
        if ($user->hasRole('verifikator1')) {
            $query->where('status_id', RefStatus::getVerifikasi1Id());
        }

        // Verifikator 2 hanya bisa menolak isu yang status Verifikasi 2
        if ($user->hasRole('verifikator2')) {
            $query->where('status_id', RefStatus::getVerifikasi2Id());
        }

        // Update status ke Ditolak
        $updatedCount = $query->update([
            'status_id' => RefStatus::getDitolakId(),
            'alasan_penolakan' => $rejectionReason,
            'updated_by' => $user->id,
            'updated_at' => now()
        ]);

        // Log perubahan dan kirim notifikasi
        foreach ($query->get() as $isu) {
            LogIsu::create([
                'isu_id' => $isu->id,
                'user_id' => $user->id,
                'keterangan' => 'Isu ditolak: ' . $rejectionReason,
                'status_id' => RefStatus::getDitolakId()
            ]);

            // Kirim notifikasi penolakan
            IsuNotificationService::notifyForRejection($isu, $rejectionReason, $user);
        }

        if ($updatedCount > 0) {
            return back()->with('success', $updatedCount . ' isu berhasil ditolak.');
        } else {
            return back()->with('error', 'Tidak ada isu yang ditolak. Mungkin Anda tidak memiliki izin untuk menolak isu yang dipilih.');
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
    private function handlePublishAction($isus, $user, $selectedIds)
    {
        // Hanya admin dan verifikator 2 yang bisa publikasi
        if (!$user->isAdmin() && !$user->hasRole('verifikator2')) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mempublikasikan isu.');
        }

        $query = clone $isus;

        // Verifikator 2 hanya bisa publikasi isu yang status Verifikasi 2
        if ($user->hasRole('verifikator2')) {
            $query->where('status_id', RefStatus::getVerifikasi2Id());
        }

        // Update status ke Dipublikasi
        $updatedCount = $query->update([
            'status_id' => RefStatus::getDipublikasiId(),
            'updated_by' => $user->id,
            'updated_at' => now()
        ]);

        // Log perubahan dan kirim notifikasi
        foreach ($query->get() as $isu) {
            LogIsu::create([
                'isu_id' => $isu->id,
                'user_id' => $user->id,
                'keterangan' => 'Isu dipublikasikan',
                'status_id' => RefStatus::getDipublikasiId()
            ]);

            // Kirim notifikasi publikasi
            IsuNotificationService::notifyForPublication($isu, $user);
        }

        if ($updatedCount > 0) {
            return back()->with('success', $updatedCount . ' isu berhasil dipublikasikan.');
        } else {
            return back()->with('error', 'Tidak ada isu yang dipublikasikan. Mungkin Anda tidak memiliki izin untuk mempublikasikan isu yang dipilih.');
        }
    }


}
