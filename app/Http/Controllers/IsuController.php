<?php
// app/Http/Controllers/IsuController.php

namespace App\Http\Controllers;

use App\Models\Isu;
use App\Models\ReferensiIsu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IsuController extends Controller
{
    /**
     * Constructor untuk menerapkan middleware.
     */
    public function __construct()
    {
        
        //
    }

    /**
     * Menampilkan daftar isu.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $isusStrategis = Isu::where('isu_strategis', true)
                           ->orderBy('tanggal', 'desc')
                           ->paginate(10);
                          
                           
        $isusLainnya = Isu::where('isu_strategis', false)
                        ->orderBy('tanggal', 'desc')
                        ->paginate(10);
                         
        
        // Jika user adalah admin atau editor, gunakan layout admin
        if (auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isEditor())) {
            return view('isu.admin.index', compact('isusStrategis', 'isusLainnya'));
        }
        
        // Jika bukan, gunakan layout default
        return view('isu.index', compact('isusStrategis', 'isusLainnya'));
    }

    /**
     * Menampilkan form untuk membuat isu baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Jika user adalah admin atau editor, gunakan layout admin
        if (auth()->user()->isAdmin() || auth()->user()->isEditor()) {
            return view('isu.admin.create');
        }
        
        return view('isu.create');
    }

    /**
     * Menyimpan isu baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        
        //dd($request->all());

        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'required|string|max:255',
            'skala' => 'required|string|max:255',
            'tone' => 'required|string|in:positif,negatif',
            //'main_image' => 'nullable|image|max:5120',
            //'thumbnail_image' => 'nullable|image|max:5120',
            //'banner_image' => 'nullable|image|max:5120',
            'rangkuman' => 'required|string',
            'narasi_positif' => 'required|string',
            'narasi_negatif' => 'required|string',
            'referensi_judul.*' => 'nullable|string|max:255',
            'referensi_url.*' => 'nullable|url',
            'referensi_thumbnail.*' => 'nullable|image|max:2048',
        ]);

    // Proses upload gambar - PERBAIKAN: Inisialisasi variabel dengan null
    $mainImage = null;
    if ($request->hasFile('main_image')) {
        $mainImage = $request->file('main_image')->store('isu_images', 'public');
    }
    
    $thumbnailImage = null;
    if ($request->hasFile('thumbnail_image')) {
        $thumbnailImage = $request->file('thumbnail_image')->store('isu_thumbnails', 'public');
    }
    
    $bannerImage = null;
    if ($request->hasFile('banner_image')) {
        $bannerImage = $request->file('banner_image')->store('isu_banners', 'public');
    }

    // Simpan isu
    $isu = Isu::create([
        'judul' => $validated['judul'],
        'tanggal' => $validated['tanggal'],
        'isu_strategis' => $request->has('isu_strategis'),
        'kategori' => $validated['kategori'],
        'skala' => $validated['skala'],
        'tone' => $validated['tone'],
        //'main_image' => $mainImage,
        //'thumbnail_image' => $thumbnailImage,
        //'banner_image' => $bannerImage,
        'rangkuman' => $validated['rangkuman'],
        'narasi_positif' => $validated['narasi_positif'],
        'narasi_negatif' => $validated['narasi_negatif'],
    ]);

        // Simpan referensi jika ada
        if ($request->has('referensi_judul')) {
            foreach ($request->referensi_judul as $key => $judul) {
                if ($judul && isset($request->referensi_url[$key])) {
                    $thumbnail = null;
                    
                    // Proses thumbnail jika ada
                    if ($request->hasFile('referensi_thumbnail') && 
                        isset($request->file('referensi_thumbnail')[$key])) {
                        $file = $request->file('referensi_thumbnail')[$key];
                        $path = $file->store('thumbnails', 'public');
                        $thumbnail = $path;
                    }
                    
                    // Simpan referensi
                    ReferensiIsu::create([
                        'isu_id' => $isu->id,
                        'judul' => $judul,
                        'url' => $request->referensi_url[$key],
                        'thumbnail' => $thumbnail,
                    ]);
                }
            }
        }

        return redirect()->route('isu.show', $isu)
                ->with('success', 'Isu berhasil dibuat!');
    }

    /**
     * Menampilkan detail isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function show(Isu $isu)
    {
        // Load referensi isu
        $isu->load('referensi');

        // Tambahkan variabel today
        $today = \Carbon\Carbon::today();

        if (auth()->user()->isAdmin() || auth()->user()->isEditor()) {
            return view('isu.admin.show', compact('isu','today'));
        }

        return view('isu.show', compact('isu','today'));
    }

    /**
     * Menampilkan form untuk mengedit isu.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\View\View
     */
    public function edit(Isu $isu)
    {
        // Load referensi isu
        $isu->load('referensi');

        // Jika user adalah admin atau editor, gunakan layout admin
        if (auth()->user()->isAdmin() || auth()->user()->isEditor()) {
            return view('isu.admin.edit', compact('isu'));
        }
        
        return view('isu.edit', compact('isu'));
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
        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'isu_strategis' => 'boolean',
            'kategori' => 'required|string|max:255',
            'skala' => 'required|string|max:255',
            'tone' => 'required|string|in:positif,negatif',
            //'main_image' => 'nullable|image|max:5120',
            //'thumbnail_image' => 'nullable|image|max:5120',
            //'banner_image' => 'nullable|image|max:5120',
            'rangkuman' => 'required|string',
            'narasi_positif' => 'required|string',
            'narasi_negatif' => 'required|string',
        ]);

            // Proses upload gambar (hanya jika ada file baru)
    $mainImage = $isu->main_image;
    if ($request->hasFile('main_image')) {
        // Hapus gambar lama jika ada
        if ($isu->main_image && Storage::disk('public')->exists($isu->main_image)) {
            Storage::disk('public')->delete($isu->main_image);
        }
        $mainImage = $request->file('main_image')->store('isu_images', 'public');
    }
    
    $thumbnailImage = $isu->thumbnail_image;
    if ($request->hasFile('thumbnail_image')) {
        // Hapus gambar lama jika ada
        if ($isu->thumbnail_image && Storage::disk('public')->exists($isu->thumbnail_image)) {
            Storage::disk('public')->delete($isu->thumbnail_image);
        }
        $thumbnailImage = $request->file('thumbnail_image')->store('isu_thumbnails', 'public');
    }
    
    $bannerImage = $isu->banner_image;
    if ($request->hasFile('banner_image')) {
        // Hapus gambar lama jika ada
        if ($isu->banner_image && Storage::disk('public')->exists($isu->banner_image)) {
            Storage::disk('public')->delete($isu->banner_image);
        }
        $bannerImage = $request->file('banner_image')->store('isu_banners', 'public');
    }

    // Update isu
    $isu->update([
        'judul' => $validated['judul'],
        'tanggal' => $validated['tanggal'],
        'isu_strategis' => $request->has('isu_strategis'),
        'kategori' => $validated['kategori'],
        'skala' => $validated['skala'],
        'tone' => $validated['tone'],
        'main_image' => $mainImage,
        'thumbnail_image' => $thumbnailImage,
        'banner_image' => $bannerImage,
        'rangkuman' => $validated['rangkuman'],
        'narasi_positif' => $validated['narasi_positif'],
        'narasi_negatif' => $validated['narasi_negatif'],
    ]);

    return redirect()->route('isu.show', $isu)
                     ->with('success', 'Isu berhasil diperbarui!');
}

    /**
     * Menghapus isu dari database.
     *
     * @param  \App\Models\Isu  $isu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Isu $isu)
    {
        // Hapus isu dan semua referensi terkait (cascade)
        $isu->delete();
        
        return redirect()->route('isu.index')
                         ->with('success', 'Isu berhasil dihapus!');
    }
}