<?php

namespace App\Http\Controllers;

use App\Models\RefStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    /**
     * Display a listing of the statuses.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $statuses = RefStatus::orderBy('urutan')->get();
        return view('isu.index', compact('statuses'));
    }

    /**
     * Store a newly created status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:50|unique:ref_status,kode',
            'warna' => 'required|string|max:50',
            'urutan' => 'required|integer|min:0',
            'aktif' => 'boolean',
        ]);

        RefStatus::create([
            'nama' => $request->nama,
            'kode' => $request->kode,
            'warna' => $request->warna,
            'urutan' => $request->urutan,
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);

        return redirect()->route('status.index')
            ->with('success', 'Status berhasil ditambahkan');
    }

    /**
     * Update the specified status in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RefStatus  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, RefStatus $status)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ref_status', 'kode')->ignore($status->id)
            ],
            'warna' => 'required|string|max:50',
            'urutan' => 'required|integer|min:0',
            'aktif' => 'boolean',
        ]);

        $status->update([
            'nama' => $request->nama,
            'kode' => $request->kode,
            'warna' => $request->warna,
            'urutan' => $request->urutan,
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);

        return redirect()->route('status.index')
            ->with('success', 'Status berhasil diperbarui');
    }

    /**
     * Remove the specified status from storage.
     *
     * @param  \App\Models\RefStatus  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RefStatus $status)
    {
        // Cek jika status masih digunakan di tabel temp_isus
        $isBeingUsed = \App\Models\TempIsu::where('status_id', $status->id)->exists();

        if ($isBeingUsed) {
            return redirect()->route('status.index')
                ->with('error', 'Status tidak dapat dihapus karena masih digunakan pada isu');
        }

        $status->delete();

        return redirect()->route('status.index')
            ->with('success', 'Status berhasil dihapus');
    }

    /**
     * Reorder statuses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'statuses' => 'required|array',
            'statuses.*.id' => 'required|exists:ref_status,id',
            'statuses.*.urutan' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->statuses as $statusData) {
            RefStatus::where('id', $statusData['id'])->update([
                'urutan' => $statusData['urutan']
            ]);
        }

        return response()->json(['message' => 'Status berhasil diurutkan']);
    }
}
