<?php
// app/Http/Controllers/IsuController.php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\ReferensiIsu;
use App\Models\RefSkala;
use App\Models\RefTone;
use App\Models\Kategori;
use App\Helpers\LogHelper;
use App\Helpers\ThumbnailHelper;
use Embed\Embed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Stevebauman\Purify\Facades\Purify;
use Carbon\Carbon;

class IsuController extends Controller
{
    /**
     * Menyimpan isu baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'nullable|string',
            'skala' => 'nullable|string',
            'tone' => 'nullable|string', 
            'rangkuman' => 'nullable|string',
            'narasi_positif' => 'nullable|string',
            'narasi_negatif' => 'nullable|string',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url',
            'referensi_thumbnail_url.*' => 'nullable|url',
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

            // Simpan isu
            $isu = Isu::create([
                'judul' => $validated['judul'],
                'tanggal' => $validated['tanggal'],
                'isu_strategis' => $request->has('isu_strategis'),
                'skala' => $skalaId,
                'tone' => $tone,
                'rangkuman' => $rangkuman,
                'narasi_positif' => $narasi_positif,
                'narasi_negatif' => $narasi_negatif,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Variabel untuk menyimpan string kategori
            $kategoriString = '';
            
            // Proses tags kategori
            if (!empty($validated['kategori'])) {
                $kategoriInput = $validated['kategori'];
                // Jika input adalah JSON dari Tagify, decode terlebih dahulu
                if (json_decode($kategoriInput, true)) {
                    $tags = array_column(json_decode($kategoriInput, true), 'value');
                    $kategoriString = implode(',', $tags);
                } else {
                    // Jika sudah comma-separated
                    $tags = array_filter(array_map('trim', explode(',', $kategoriInput)));
                    $kategoriString = implode(',', $tags);
                }

                $kategoriIds = [];
                foreach ($tags as $tag) {
                    $kategori = Kategori::firstOrCreate(['nama' => $tag]); // Simpan hanya nama sebagai string
                    $kategoriIds[] = $kategori->id;
                }

                // Simpan relasi ke tabel pivot
                $isu->kategoris()->sync($kategoriIds);
            }
            
            // Simpan referensi jika ada
            if ($request->has('referensi_judul')) {
                foreach ($request->referensi_judul as $key => $judul) {
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
                                // Log error, tapi jangan batalkan proses
                                \Log::error('Error fetching thumbnail: ' . $e->getMessage());
                            }
                        }
                        
                        // Simpan referensi dengan thumbnail URL (bukan path storage)
                        ReferensiIsu::create([
                            'isu_id' => $isu->id,
                            'judul' => $judul,
                            'url' => $url,
                            'thumbnail' => $thumbnail, // Simpan URL langsung
                        ]);
                    }
                }
            }

            // Log aktivitas pembuatan isu
            LogHelper::logIsuActivity(
                $isu->id,
                'CREATE',
                null,
                null,
                json_encode($isu->toArray()),
                $request
            );
            
            // Log kategori jika ada
            if (!empty($kategoriString)) {
                LogHelper::logIsuActivity(
                    $isu->id,
                    'CREATE',
                    'kategori',
                    null,
                    $kategoriString,
                    $request
                );
            }
                
            DB::commit();
                
            return redirect()->route('isu.show', $isu)
                            ->with('success', 'Isu berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                        ->withInput()
                        ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        // Base queries dengan eager loading
        $isusStrategisQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris'])
                                ->where('isu_strategis', true);
        
        $isusLainnyaQuery = Isu::with(['referensi', 'refSkala', 'refTone', 'kategoris'])
                                ->where('isu_strategis', false);
        
        // Pencarian global
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            
            // Cari berdasarkan judul atau kategori
            $isusStrategisQuery->where(function($query) use ($searchTerm) {
                $query->where('judul', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('kategoris', function($q) use ($searchTerm) {
                          $q->where('nama', 'like', '%' . $searchTerm . '%');
                      });
            });
            
            $isusLainnyaQuery->where(function($query) use ($searchTerm) {
                $query->where('judul', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('kategoris', function($q) use ($searchTerm) {
                          $q->where('nama', 'like', '%' . $searchTerm . '%');
                      });
            });
        }
        
        // Sorting
        $sortField = $request->get('sort', 'tanggal');
        $sortDirection = $request->get('direction', 'desc');
        
        // Whitelist kolom yang diizinkan untuk sorting
        $allowedSortFields = ['tanggal', 'skala', 'tone'];
        
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'tanggal';
        }   
        
        // Sorting normal untuk judul dan tanggal
        $isusStrategisQuery->orderBy($sortField, $sortDirection);
        $isusLainnyaQuery->orderBy($sortField, $sortDirection);
        
        $isusStrategis = $isusStrategisQuery->paginate(10, ['*'], 'strategis');
        $isusLainnya = $isusLainnyaQuery->paginate(10, ['*'], 'lainnya');
        
        // Pastikan semua parameter disertakan di URL pagination
        $isusStrategis->appends($request->except('strategis'));
        $isusLainnya->appends($request->except('lainnya'));
        
        return view('isu.index', compact('isusStrategis', 'isusLainnya'));
    }

    /**
     * Menampilkan form untuk membuat isu baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $kategoriList = Kategori::all();
        
        $skalaList = RefSkala::where('aktif', true)
        ->orderBy('urutan')
        ->get();
    
        $toneList = RefTone::where('aktif', true)
            ->orderBy('urutan')
            ->get();
    
    return view('isu.create', compact('kategoriList','skalaList', 'toneList'));
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
        // Pastikan hanya admin atau pembuat isu yang bisa mengedit
        if (!auth()->user()->isAdmin() && !auth()->user()->isEditor() && $isu->created_by != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'nullable|string',
            'skala' => 'nullable|string',
            'tone' => 'nullable|string',
            'rangkuman' => 'nullable|string',
            'narasi_positif' => 'nullable|string',
            'narasi_negatif' => 'nullable|string',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url|max:255',
            'referensi_id.*' => 'nullable|exists:referensi_isus,id',
            'referensi_thumbnail_url.*' => 'nullable|url',
        ]);

        // Simpan data asli untuk log perubahan
        $originalData = $isu->toArray();

        // Bersihkan data dan tambahkan default jika kosong
        $rangkuman = !empty($request->rangkuman) ? Purify::clean($request->rangkuman) : '<p>Tidak ada data</p>';
        $narasi_positif = !empty($request->narasi_positif) ? Purify::clean($request->narasi_positif) : '<p>Tidak ada data</p>';
        $narasi_negatif = !empty($request->narasi_negatif) ? Purify::clean($request->narasi_negatif) : '<p>Tidak ada data</p>';
        
        // Tetapkan nilai untuk skala dan tone
        // Gunakan null jika tidak ada nilai yang dipilih
        $skala = !empty($validated['skala']) ? $validated['skala'] : null;
        $tone = !empty($validated['tone']) ? $validated['tone'] : null;

        // Begin transaction
        DB::beginTransaction();

        // Update isu
        try {
            // Update isu dengan user tracking
            $isu->update([
                'judul' => $validated['judul'],
                'tanggal' => $validated['tanggal'],
                'isu_strategis' => $request->has('isu_strategis'),
                'skala' => $skala,
                'tone' => $tone,
                'rangkuman' => $rangkuman,
                'narasi_positif' => $narasi_positif,
                'narasi_negatif' => $narasi_negatif,
                'updated_by' => Auth::id(), // Update dengan user id yang mengedit
            ]);

        // Proses tags kategori
        if (!empty($validated['kategori'])) {
            $kategoriInput = $validated['kategori'];
            if (json_decode($kategoriInput, true)) {
                $tags = array_column(json_decode($kategoriInput, true), 'value');
            } else {
                $tags = array_filter(array_map('trim', explode(',', $kategoriInput)));
            }

            $kategoriIds = [];
            foreach ($tags as $tag) {
                $kategori = Kategori::firstOrCreate(['nama' => $tag]);
                $kategoriIds[] = $kategori->id;
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
            
            // Hapus referensi yang tidak ada lagi di form
            $isu->referensi()->whereNotIn('id', array_filter($existingReferensiIds))->delete();
            
            foreach ($request->referensi_judul as $key => $judul) {
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
                                // Gunakan embed/embed untuk mendapatkan metadata
                                $embed = new Embed();
                                $info = $embed->get($url);
                                
                                // Ambil URL thumbnail/gambar dari metadata
                                $thumbnail = $info->image;
                            } catch (\Exception $e) {
                                // Log error
                                \Log::error('Error fetching thumbnail: ' . $e->getMessage());
                            }
                        } else {
                            // Gunakan thumbnail yang sudah ada
                            $thumbnail = $referensi->thumbnail;
                        }
                        
                        // Update referensi
                        $referensi->update([
                            'judul' => $judul,
                            'url' => $url,
                            'thumbnail' => $thumbnail, // Simpan URL langsung
                        ]);
                    } else {
                        // Ini referensi baru, coba ambil thumbnail
                        if (!$thumbnail) {
                            try {
                                // Gunakan embed/embed untuk mendapatkan metadata
                                $embed = new Embed();
                                $info = $embed->get($url);
                                
                                // Ambil URL thumbnail/gambar dari metadata
                                $thumbnail = $info->image;
                            } catch (\Exception $e) {
                                // Log error
                                \Log::error('Error fetching thumbnail: ' . $e->getMessage());
                            }
                        }
                        
                        // Buat referensi baru
                        ReferensiIsu::create([
                            'isu_id' => $isu->id,
                            'judul' => $judul,
                            'url' => $url,
                            'thumbnail' => $thumbnail, // Simpan URL langsung
                        ]);
                    }
                }
            }
        } else {
            // Hapus semua referensi jika tidak ada di form
            $isu->referensi()->delete();
        }

            // Log perubahan field-by-field
            LogHelper::logIsuChanges(
                $isu->id,
                $originalData,
                $request->all(),
                $request
            );
            
            DB::commit();

        return redirect()->route('isu.show', $isu)
                         ->with('success', 'Isu berhasil diperbarui!');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()
                        ->withInput()
                        ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

    public function show(Isu $isu)
    {
        
        foreach ($isu->referensi as $ref) {
            $metadata = ThumbnailHelper::getUrlMetadata($ref->url);
            $ref->meta_description = $metadata['description'];
        }
        $isu->load(['referensi', 'refSkala', 'refTone']);
        return view('isu.show', compact('isu'));
    }

    /**
     * Menampilkan form untuk mengedit isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function edit(Isu $isu)
    {
        // Pastikan hanya admin atau pembuat isu yang bisa mengedit
        if (!auth()->user()->isAdmin() && !auth()->user()->isEditor() && $isu->created_by != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Ambil data isu beserta referensinya
        $isu->load('referensi');

        $kategoriList = Kategori::all();
        
        $skalaList = RefSkala::where('aktif', true)
            ->orderBy('urutan')
            ->get();
        
        $toneList = RefTone::where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return view('isu.edit', compact('isu', 'kategoriList', 'skalaList', 'toneList'));
    }

    /**
     * Menampilkan riwayat perubahan isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function history(Isu $isu)
    {
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
            \Log::error('Error fetching preview: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memuat preview'], 500);
        }
    }
}