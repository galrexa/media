<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Constructor untuk memastikan hanya admin yang dapat mengakses
     */
    public function __construct()
    {
        // $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Menampilkan halaman pengaturan
     */
    public function index()
    {
        // Ambil pengaturan modal dari database, hanya satu setting (modal_content)
        $modalSettings = Setting::where('key', 'modal_content')->get();
        
        // Jika tidak ada data, tambahkan data default
        if ($modalSettings->count() == 0) {
            $this->seedDefaultSettings();
            $modalSettings = Setting::where('key', 'modal_content')->get();
        }
        
        return view('settings.index', compact('modalSettings'));
    }

    /**
     * Menyimpan atau mengupdate pengaturan modal
     */
    public function update(Request $request, $id)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($id === 'create') {
            // Buat pengaturan baru jika tidak ada
            Setting::create([
                'key' => 'modal_content',
                'value' => $request->value,
                'category' => 'modal',
            ]);
        } else {
            // Update pengaturan yang sudah ada
            $setting = Setting::findOrFail($id);
            $setting->value = $request->value;
            $setting->save();
        }

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan modal berhasil diperbarui');
    }

    /**
     * Menyemai data pengaturan default
     */
    private function seedDefaultSettings()
    {
        // Konten default dalam format HTML
        $defaultContent = '<p>Laporan Monitoring Isu Strategis Nasional ini disusun oleh Tim Pengelolaan Media sebagai upaya untuk memahami perspektif media terhadap berbagai kebijakan, isu, dan topik yang berkembang di Indonesia pada {tanggal}.</p>
        <p>Isu-isu strategis yang disajikan dalam laporan ini bersumber dari penelitian kualitatif yang dikaji Tim Pengelolaan Media KSP melalui pemberitaan di media cetak dan media online.</p>
        <p>Analisis yang dilakukan bertujuan untuk memberikan wawasan bagi insan Kantor Staf Presiden (KSP) dalam mencermati dinamika pemberitaan di media massa. Selain itu, laporan ini diharapkan dapat menjadi referensi dalam diskusi serta landasan dalam menindaklanjuti isu-isu yang berkembang.</p>';
        
        Setting::create([
            'key' => 'modal_content',
            'value' => $defaultContent,
            'category' => 'modal',
        ]);
    }
}