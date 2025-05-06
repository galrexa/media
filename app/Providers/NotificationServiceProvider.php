<?php

namespace App\Providers;

use App\Models\Isu;
use App\Models\RefStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $userId = $user->id;

                // Hitung jumlah isu yang perlu ditangani berdasarkan role
                if ($user->isAdmin()) {
                    // Admin bisa melihat semua isu yang memerlukan tindakan
                    $pendingIsuCount = Isu::whereIn('status_id', [
                        RefStatus::getDraftId(),
                        RefStatus::getVerifikasi1Id(),
                        RefStatus::getVerifikasi2Id(),
                        RefStatus::getDitolakId()
                    ])->count();

                    $draftIsuCount = Isu::where('status_id', RefStatus::getDraftId())->count();
                    $verifikasi1IsuCount = Isu::where('status_id', RefStatus::getVerifikasi1Id())->count();
                    $verifikasi2IsuCount = Isu::where('status_id', RefStatus::getVerifikasi2Id())->count();
                    $rejectedIsuCount = Isu::where('status_id', RefStatus::getDitolakId())->count();

                    $view->with(compact('pendingIsuCount', 'draftIsuCount', 'verifikasi1IsuCount', 'verifikasi2IsuCount', 'rejectedIsuCount'));
                }
                elseif ($user->isEditor()) {
                    // Editor hanya melihat isu yang dibuat olehnya
                    $pendingIsuCount = Isu::where('created_by', $userId)
                                        ->whereIn('status_id', [
                                            RefStatus::getDraftId(),
                                            RefStatus::getDitolakId()
                                        ])->count();

                    $draftIsuCount = Isu::where('created_by', $userId)
                                     ->where('status_id', RefStatus::getDraftId())
                                     ->count();

                    $rejectedIsuCount = Isu::where('created_by', $userId)
                                        ->where('status_id', RefStatus::getDitolakId())
                                        ->count();

                    $view->with(compact('pendingIsuCount', 'draftIsuCount', 'rejectedIsuCount'));
                }
                elseif ($user->hasRole('verifikator1')) {
                    // Verifikator 1 melihat isu yang perlu diverifikasi 1
                    $pendingIsuCount = Isu::where('status_id', RefStatus::getVerifikasi1Id())->count();
                    $verifikasi1IsuCount = $pendingIsuCount;
                    $rejectedIsuCount = Isu::where('status_id', RefStatus::getDitolakId())
                                        ->whereHas('logIsus', function($query) use ($userId) {
                                            $query->where('user_id', $userId)
                                                  ->where('status_id', RefStatus::getDitolakId());
                                        })->count();

                    $view->with(compact('pendingIsuCount', 'verifikasi1IsuCount', 'rejectedIsuCount'));
                }
                elseif ($user->hasRole('verifikator2')) {
                    // Verifikator 2 melihat isu yang perlu diverifikasi 2
                    $pendingIsuCount = Isu::where('status_id', RefStatus::getVerifikasi2Id())->count();
                    $verifikasi2IsuCount = $pendingIsuCount;
                    $rejectedIsuCount = Isu::where('status_id', RefStatus::getDitolakId())
                                        ->whereHas('logIsus', function($query) use ($userId) {
                                            $query->where('user_id', $userId)
                                                  ->where('status_id', RefStatus::getDitolakId());
                                        })->count();

                    $view->with(compact('pendingIsuCount', 'verifikasi2IsuCount', 'rejectedIsuCount'));
                }
            }
        });
    }
}
