<?php
// app/Http/Controllers/TrendingController.php

namespace App\Http\Controllers;

use App\Models\Trending;
use App\Models\MediaSosial;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrendingController extends Controller
{
    /**
     * Constructor untuk menerapkan middleware.
     */
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
        $trendingGoogle = Trending::whereHas('mediaSosial', function($query) {
                                   $query->where('nama', 'Google');
                               })
                               ->orderBy('tanggal', 'desc')
                               ->paginate(10);
                               
        $trendingX = Trending::whereHas('mediaSosial', function($query) {
                               $query->where('nama', 'X');
                           })
                           ->orderBy('tanggal', 'desc')
                           ->paginate(10);
                           
        return view('trending.index', compact('trendingGoogle', 'trendingX'));
    }

    /**
     * Menampilkan form untuk membuat trending baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $mediaSosials = MediaSosial::all();
        return view('trending.create', compact('mediaSosials'));
    }

    /**
     * Menyimpan trending baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'media_sosial_id' => 'required|exists:media_sosials,id',
            'tanggal' => 'required|date',
            'judul' => 'required|string|max:255',
            'url' => 'required|url|max:255',
        ]);

        // Simpan trending
        $trending = Trending::create([
            'media_sosial_id' => $validated['media_sosial_id'],
            'tanggal' => $validated['tanggal'],
            'judul' => $validated['judul'],
            'url' => $validated['url'],
        ]);

        return redirect()->route('trending.index')
                         ->with('success', 'Trending berhasil dibuat!');
    }

    /**
     * Menghapus trending dari database.
     *
     * @param  \App\Models\Trending  $trending
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Trending $trending)
    {
        // Hapus trending
        $trending->delete();
        
        return redirect()->route('trending.index')
                        ->with('success', 'Trending berhasil dihapus!');
    }
}