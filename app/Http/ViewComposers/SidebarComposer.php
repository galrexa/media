<?php
// app/Http/ViewComposers/SidebarComposer.php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Isu;
use App\Models\Document;
use App\Models\Trending;
use App\Models\RefStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class SidebarComposer
{
    /**
     * Bind data ke view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Hanya jalankan query jika user sudah login
        if (Auth::check()) {
            try {
                Log::info('SidebarComposer: Composing view for user ' . Auth::id());

                // Menggunakan konstanta dari RefStatus untuk mengambil data
                $draftIsuCount = Isu::where('status_id', RefStatus::DRAFT)->count();
                $verifikasi1IsuCount = Isu::where('status_id', RefStatus::VERIFIKASI_1)->count();
                $verifikasi2IsuCount = Isu::where('status_id', RefStatus::VERIFIKASI_2)->count();
                $rejectedIsuCount = Isu::where('status_id', RefStatus::DITOLAK)->count();

                // Cek apakah user telah melihat isu yang ditolak
                $rejectedBadgeHidden = Session::get('rejected_badge_hidden', false);

                // Logging informasi jumlah untuk debugging
                Log::info("Badge counts - Draft: $draftIsuCount, Verifikasi L1: $verifikasi1IsuCount, Verifikasi L2: $verifikasi2IsuCount, Rejected: $rejectedIsuCount");
                Log::info("Rejected badge hidden: " . ($rejectedBadgeHidden ? 'true' : 'false'));

                // Total isu yang pending (sesuai role masing-masing)
                $pendingIsuCount = 0;
                $userRole = '';

                if (Auth::user()->isAdmin()) {
                    $userRole = 'admin';
                    // Admin melihat semua
                    $pendingIsuCount = $draftIsuCount + $verifikasi1IsuCount + $verifikasi2IsuCount;
                    // Hanya tambahkan rejectedIsuCount jika badge tidak disembunyikan
                    if (!$rejectedBadgeHidden) {
                        $pendingIsuCount += $rejectedIsuCount;
                    }
                } elseif (Auth::user()->isEditor()) {
                    $userRole = 'editor';
                    // Editor melihat jumlah draft dan rejected (jika badge tidak disembunyikan)
                    $pendingIsuCount = $draftIsuCount;
                    if (!$rejectedBadgeHidden) {
                        $pendingIsuCount += $rejectedIsuCount;
                    }
                } elseif (Auth::user()->hasRole('verifikator1')) {
                    $userRole = 'verifikator1';
                    // Verifikator 1 melihat jumlah yang perlu diverifikasi level 1
                    $pendingIsuCount = $verifikasi1IsuCount;
                } elseif (Auth::user()->hasRole('verifikator2')) {
                    $userRole = 'verifikator2';
                    // Verifikator 2 melihat jumlah yang perlu diverifikasi level 2
                    $pendingIsuCount = $verifikasi2IsuCount;
                }

                Log::info("User role: $userRole, Pending isu count: $pendingIsuCount");

                // Hitung dokumen dan trending yang pending
                $pendingDocumentCount = 0;
                $pendingTrendingCount = 0;

                // Cek kolom status di tabel documents (jika ada)
                try {
                    $pendingDocumentCount = Document::where('status', 'pending')->count();
                    Log::info("Pending document count: $pendingDocumentCount");
                } catch (\Exception $e) {
                    Log::warning('Error counting pending documents: ' . $e->getMessage());
                }

                // Cek kolom status di tabel trending (jika ada)
                try {
                    $pendingTrendingCount = Trending::where('status', 'pending')->count();
                    Log::info("Pending trending count: $pendingTrendingCount");
                } catch (\Exception $e) {
                    Log::warning('Error counting pending trending items: ' . $e->getMessage());
                }

                // Cek jika URL saat ini adalah halaman rejected
                $isRejectedPage = request()->routeIs('isu.index') &&
                                 request()->input('filter_status') === 'rejected';

                // Pass semua data ke view
                $data = [
                    'draftIsuCount' => $draftIsuCount,
                    'verifikasi1IsuCount' => $verifikasi1IsuCount,
                    'verifikasi2IsuCount' => $verifikasi2IsuCount,
                    'rejectedIsuCount' => $rejectedIsuCount,
                    'pendingIsuCount' => $pendingIsuCount,
                    'pendingDocumentCount' => $pendingDocumentCount,
                    'pendingTrendingCount' => $pendingTrendingCount,
                    'rejectedBadgeHidden' => $rejectedBadgeHidden,
                    'isRejectedPage' => $isRejectedPage,
                ];

                $view->with($data);

                Log::info('SidebarComposer: View composed successfully with data');

            } catch (\Exception $e) {
                // Tangani error secara graceful
                Log::error('Error in SidebarComposer: ' . $e->getMessage());
                Log::error($e->getTraceAsString());

                // Set nilai default untuk mencegah error view
                $view->with([
                    'draftIsuCount' => 0,
                    'verifikasi1IsuCount' => 0,
                    'verifikasi2IsuCount' => 0,
                    'rejectedIsuCount' => 0,
                    'pendingIsuCount' => 0,
                    'pendingDocumentCount' => 0,
                    'pendingTrendingCount' => 0,
                    'rejectedBadgeHidden' => false,
                    'isRejectedPage' => false,
                ]);
            }
        }
    }
}
