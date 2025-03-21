<?php
// app/Http/Controllers/ImageController.php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ImageController extends Controller
{
    public function __construct()
    {
        //$this->middleware('role:admin,editor')->except('index');
    }

    public function index()
    {
        $images = Image::orderBy('tanggal', 'desc')->paginate(10);
        return view('images.index', compact('images'));
    }

    public function create()
    {
        $today = Carbon::today();
        $existingImage = Image::whereDate('tanggal', $today)->first();
        
        return view('images.create', compact('existingImage', 'today'));
    }

    public function edit($date = null)
    {
        $selectedDate = $date ? Carbon::parse($date) : Carbon::today();
        $image = Image::whereDate('tanggal', $selectedDate)->first();
        $availableDates = Image::orderBy('tanggal', 'desc')
            ->pluck('tanggal')
            ->map(fn($date) => [
                'date' => $date->format('Y-m-d'),
                'formatted' => $date->format('d F Y')
            ]);

        return view('images.edit', compact('image', 'selectedDate', 'availableDates'));
    }

    public function store(Request $request)
    {
        return $this->handleImageUpload($request);
    }

    public function update(Request $request, $id)
    {
        return $this->handleImageUpload($request, $id);
    }

    /**
     * Menangani proses upload dan update gambar
     */
    private function handleImageUpload(Request $request, $id = null)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'image_1' => 'nullable|image|max:5120',
            'image_2' => 'nullable|image|max:5120',
            'image_3' => 'nullable|image|max:5120',
            'delete_image_1' => 'nullable|boolean',
            'delete_image_2' => 'nullable|boolean',
            'delete_image_3' => 'nullable|boolean',
        ]);

        $tanggal = Carbon::parse($validated['tanggal']);
        
        // Tentukan apakah ini update atau create
        $image = $id ? Image::findOrFail($id) : Image::whereDate('tanggal', $tanggal)->first();
        
        if (!$image) {
            $image = new Image();
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

        $image->save();

        $redirect = $id 
            ? redirect()->route('images.edit', $image->tanggal->format('Y-m-d'))
            : redirect()->route('home');

        return $redirect->with('success', $id 
            ? 'Gambar harian berhasil diperbarui!' 
            : 'Gambar harian berhasil diupload!');
    }
}

//<?php
// app/Http/Controllers/ImageController.php

//namespace App\Http\Controllers;

//use App\Models\Image;
//use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Storage;
// use Carbon\Carbon;

// // class ImageController extends Controller
// {
//     /**
//      * Constructor untuk menerapkan middleware.
//      */
//     public function __construct()
//     {
//         // $this->middleware('role:admin,editor');
//     }

//     /**
//      * Menampilkan form untuk upload gambar harian.
//      *
//      * @return \Illuminate\View\View
//      */
//     public function create()
//     {
//         $today = Carbon::today();
//         $existingImage = Image::whereDate('tanggal', $today)->first();
        
//         return view('images.create', compact('existingImage', 'today'));
//     }

//     /**
//      * Menyimpan gambar harian ke database.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @return \Illuminate\Http\RedirectResponse
//      */
//     public function store(Request $request)
//     {
//         // Validasi input
//         $validated = $request->validate([
//             'tanggal' => 'required|date',
//             'image_1' => 'nullable|image|max:5120',
//             'image_2' => 'nullable|image|max:5120',
//             'image_3' => 'nullable|image|max:5120',
//         ]);

//         $tanggal = Carbon::parse($validated['tanggal']);
        
//         // Cari apakah sudah ada gambar untuk tanggal ini
//         $image = Image::whereDate('tanggal', $tanggal)->first();
        
//         // Jika belum ada, buat baru
//         if (!$image) {
//             $image = new Image();
//             $image->tanggal = $tanggal;
//         }
        
//         // Proses upload gambar 1
//         if ($request->hasFile('image_1')) {
//             // Hapus gambar lama jika ada
//             if ($image->image_1 && Storage::disk('public')->exists($image->image_1)) {
//                 Storage::disk('public')->delete($image->image_1);
//             }
//             $image->image_1 = $request->file('image_1')->store('daily_images', 'public');
//         } elseif ($request->has('delete_image_1')) {
//             // Hapus gambar jika checkbox hapus dicentang
//             if ($image->image_1 && Storage::disk('public')->exists($image->image_1)) {
//                 Storage::disk('public')->delete($image->image_1);
//             }
//             $image->image_1 = null;
//         }
        
//         // Proses upload gambar 2
//         if ($request->hasFile('image_2')) {
//             // Hapus gambar lama jika ada
//             if ($image->image_2 && Storage::disk('public')->exists($image->image_2)) {
//                 Storage::disk('public')->delete($image->image_2);
//             }
//             $image->image_2 = $request->file('image_2')->store('daily_images', 'public');
//         } elseif ($request->has('delete_image_2')) {
//             // Hapus gambar jika checkbox hapus dicentang
//             if ($image->image_2 && Storage::disk('public')->exists($image->image_2)) {
//                 Storage::disk('public')->delete($image->image_2);
//             }
//             $image->image_2 = null;
//         }
        
//         // Proses upload gambar 3
//         if ($request->hasFile('image_3')) {
//             // Hapus gambar lama jika ada
//             if ($image->image_3 && Storage::disk('public')->exists($image->image_3)) {
//                 Storage::disk('public')->delete($image->image_3);
//             }
//             $image->image_3 = $request->file('image_3')->store('daily_images', 'public');
//         } elseif ($request->has('delete_image_3')) {
//             // Hapus gambar jika checkbox hapus dicentang
//             if ($image->image_3 && Storage::disk('public')->exists($image->image_3)) {
//                 Storage::disk('public')->delete($image->image_3);
//             }
//             $image->image_3 = null;
//         }
        
//         $image->save();
        
//         return redirect()->route('home')
//                          ->with('success', 'Gambar harian berhasil diupload!');
//     }
// }

