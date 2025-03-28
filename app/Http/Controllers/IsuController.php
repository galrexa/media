<?php
namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\ReferensiIsu;
use App\Models\RefSkala;
use App\Models\RefTone;
use App\Models\Kategori;
use Embed\Embed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
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
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'required|string',
            'skala' => 'required|string|max:255',
            'tone' => 'required|string|exists:ref_tone,kode', // Ubah rule ini
            'rangkuman' => 'required|string',
            'narasi_positif' => 'required|string',
            'narasi_negatif' => 'required|string',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url',
            'referensi_thumbnail_url.*' => 'nullable|url',
        ]);

        $validated['rangkuman'] = Purify::clean($validated['rangkuman']);
        $validated['narasi_positif'] = Purify::clean($validated['narasi_positif']);
        $validated['narasi_negatif'] = Purify::clean($validated['narasi_negatif']);

        // Simpan isu
        $isu = Isu::create([
            'judul' => $validated['judul'],
            'tanggal' => $validated['tanggal'],
            'isu_strategis' => $request->has('isu_strategis'),
            'skala' => $validated['skala'],
            'tone' => $validated['tone'],
            'rangkuman' => $validated['rangkuman'],
            'narasi_positif' => $validated['narasi_positif'],
            'narasi_negatif' => $validated['narasi_negatif'],
            ]);

        // Proses tags kategori
        $kategoriInput = $validated['kategori'];
        // Jika input adalah JSON dari Tagify, decode terlebih dahulu
        if (json_decode($kategoriInput, true)) {
            $tags = array_column(json_decode($kategoriInput, true), 'value');
        } else {
            // Jika sudah comma-separated
            $tags = array_filter(array_map('trim', explode(',', $kategoriInput)));
        }

        $kategoriIds = [];
        foreach ($tags as $tag) {
            $kategori = Kategori::firstOrCreate(['nama' => $tag]); // Simpan hanya nama sebagai string
            $kategoriIds[] = $kategori->id;
        }

        // Simpan relasi ke tabel pivot
        $isu->kategoris()->sync($kategoriIds);
        
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

        return redirect()->route('isu.show', $isu)
                         ->with('success', 'Isu berhasil dibuat!');
    }

    public function index()
    {
        // Query untuk isu strategis
        $isusStrategisQuery = Isu::with(['referensi', 'refSkala', 'refTone'])
                                ->where('isu_strategis', true)
                                ->orderBy('tanggal', 'desc');
        
        // Query untuk isu lainnya
        $isusLainnyaQuery = Isu::with(['referensi', 'refSkala', 'refTone'])
                            ->where('isu_strategis', false)
                            ->orderBy('tanggal', 'desc');
        
        $isusStrategis = $isusStrategisQuery->paginate(10, ['*'], 'strategis');
        $isusLainnya = $isusLainnyaQuery->paginate(10, ['*'], 'lainnya');
        
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
            'kategori' => 'required|string',
            'skala' => 'required|string|max:255',
            'tone' => 'required|exists:ref_tone,kode',
            'rangkuman' => 'required|string',
            'narasi_positif' => 'required|string',
            'narasi_negatif' => 'required|string',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url',
            'referensi_id.*' => 'nullable|exists:referensi_isus,id',
            'referensi_thumbnail_url.*' => 'nullable|url',
        ]);

        // Update isu
        $isu->update([
            'judul' => $validated['judul'],
            'tanggal' => $validated['tanggal'],
            'isu_strategis' => $request->has('isu_strategis'),
            'skala' => $validated['skala'],
            'tone' => $validated['tone'],
            'narasi_positif' => $validated['narasi_positif'],
            'narasi_negatif' => $validated['narasi_negatif'],
            'dokumen_url' => 'nullable|url|max:255',       
        ]);

        // Proses tags kategori
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

        return redirect()->route('isu.show', $isu)
                         ->with('success', 'Isu berhasil diperbarui!');
    }

    public function show(Isu $isu)
    {
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
    
        // Tidak perlu menghapus file thumbnail karena kita tidak menyimpannya di storage lagi
    
        $isu->delete();
        return redirect()->route('isu.index')
                         ->with('success', 'Isu berhasil dihapus!');
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