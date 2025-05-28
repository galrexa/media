<?php
// app/Http/Controllers/BadgeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class BadgeController extends Controller
{
    /**
     * Konstruktor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Reset badge notifikasi yang terkait dengan status tertentu
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetRejectedBadge(Request $request)
    {
        $userId = Auth::id();

        // Simpan ke session bahwa badge ditolak telah dilihat
        Session::put('rejected_badge_hidden', true);

        // Simpan juga ke cache untuk persisten lebih lama
        $cacheKey = 'rejected_badge_hidden_' . $userId;
        Cache::put($cacheKey, true, now()->addDays(7)); // Simpan selama 7 hari

        // Log untuk audit trail
        // Log::info('User ' . auth()->user()->name . ' (ID: ' . $userId . ') reset rejected badge notification');

        return response()->json([
            'success' => true,
            'message' => 'Badge notification has been reset',
            'userId' => $userId
        ]);
    }

    /**
     * Tampilkan kembali badge (untuk testing/debugging)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showRejectedBadge()
    {
        $userId = Auth::id();

        // Hapus flag dari session dan cache
        Session::forget('rejected_badge_hidden');

        $cacheKey = 'rejected_badge_hidden_' . $userId;
        Cache::forget($cacheKey);

        // Log untuk audit
        // Log::info('User ' . auth()->user()->name . ' (ID: ' . $userId . ') re-enabled rejected badge notification');

        return response()->json([
            'success' => true,
            'message' => 'Badge notification will be shown again',
            'userId' => $userId
        ]);
    }
}
