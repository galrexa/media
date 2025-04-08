<?php
// app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DocumentController extends Controller
{
    public function __construct()
    {
        //$this->middleware('role:admin,editor')->except('index');
    }

    public function index()
    {
        $images = Document::orderBy('tanggal', 'desc')->paginate(10);
        return view('documents.index', compact('images'));
    }

    public function create(Request $request)
    {
        // Gunakan tanggal dari request jika ada, jika tidak gunakan hari ini
        $date = $request->has('tanggal') ? Carbon::parse($request->tanggal) : Carbon::today();
        
        // Hanya cari data jika memang menggunakan tanggal hari ini
        $existingImage = null;
        if ($date->isToday()) {
            $existingImage = Document::whereDate('tanggal', $date)->first();
        }
        
        return view('documents.create', compact('existingImage', 'date'));
    }

    public function edit($date = null)
    {
        $selectedDate = $date ? Carbon::parse($date) : Carbon::today();
        $image = Document::whereDate('tanggal', $selectedDate)->first();
        $availableDates = Document::orderBy('tanggal', 'desc')
            ->pluck('tanggal')
            ->map(fn($date) => [
                'date' => $date->format('Y-m-d'),
                'formatted' => $date->format('d F Y')
            ]);

        return view('documents.edit', compact('image', 'selectedDate', 'availableDates'));
    }

    public function store(Request $request)
    {
        return $this->handleDocumentUpload($request);
    }

    public function update(Request $request, $id)
    {
        return $this->handleDocumentUpload($request, $id);
    }

    /**
     * Menangani proses upload dan update gambar
     */
    private function handleDocumentUpload(Request $request, $id = null)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'image_1' => 'nullable|image|max:5120',
            'image_2' => 'nullable|image|max:5120',
            'image_3' => 'nullable|image|max:5120',
            'delete_image_1' => 'nullable|boolean',
            'delete_image_2' => 'nullable|boolean',
            'delete_image_3' => 'nullable|boolean',
            'dokumen_url' => 'nullable|url|max:255',
            'delete_dokumen' => 'nullable|boolean',
        ]);

        $tanggal = Carbon::parse($validated['tanggal']);
        
        // Tentukan apakah ini update atau create
        $image = $id ? Document::findOrFail($id) : Document::whereDate('tanggal', $tanggal)->first();
        
        if (!$image) {
            $image = new Document();
            $image->tanggal = $tanggal;
        } elseif ($id && !$image->tanggal->isSameDay($tanggal)) {
            $image->tanggal = $tanggal;
        }

        // Proses semua gambar menggunakan loop
        for ($i = 1; $i <= 3; $i++) {
            $imageField = "image_$i";
            $deleteField = "delete_image_$i";

            if ($request->hasFile($imageField)) {
                // Hapus gambar lama jika ada
                if ($image->$imageField && Storage::disk('public')->exists($image->$imageField)) {
                    Storage::disk('public')->delete($image->$imageField);
                }
                $image->$imageField = $request->file($imageField)->store('daily_images', 'public');
            } elseif ($request->has($deleteField) && $request->$deleteField) {
                if ($image->$imageField && Storage::disk('public')->exists($image->$imageField)) {
                    Storage::disk('public')->delete($image->$imageField);
                }
                $image->$imageField = null;
            }
        }

        // Proses dokumen URL
        if ($request->has('dokumen_url') && $request->dokumen_url) {
            $image->dokumen_url = $request->dokumen_url;
        } elseif ($request->has('delete_dokumen') && $request->delete_dokumen) {
            $image->dokumen_url = null;
        }

        $image->save();

        $redirect = $id 
            ? redirect()->route('documents.edit', $image->tanggal->format('Y-m-d'))
            : redirect()->route('home');

        return $redirect->with('success', $id 
            ? 'Dokumen berhasil diperbarui!' 
            : 'Dokumen harian berhasil diupload!');
    }
}