<?php
// app/Http/ViewComposers/NotificationComposer.php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class NotificationComposer
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
                $user = Auth::user();

                // Dapatkan semua notifikasi untuk user yang sedang login
                $notifications = $user->notifications()->latest()->take(10)->get();

                // Hitung jumlah notifikasi yang belum dibaca
                $unreadNotificationCount = $user->unreadNotifications()->count();

                // Cek apakah badge notifikasi sudah disembunyikan via session
                $notificationBadgeHidden = Session::get('notification_badge_hidden', false);

                // Jika badge tersembunyi, set count untuk tampilan ke 0
                $unreadNotificationDisplayCount = $notificationBadgeHidden ? 0 : $unreadNotificationCount;

                // Log untuk debugging
                Log::info("User {$user->id} has {$unreadNotificationCount} unread notifications");
                Log::info("Notification badge hidden: " . ($notificationBadgeHidden ? 'true' : 'false'));

                // Pass data ke view
                $view->with([
                    'notifications' => $notifications,
                    'unreadNotificationCount' => $unreadNotificationDisplayCount,
                    'totalNotificationCount' => $notifications->count(),
                    'notificationBadgeHidden' => $notificationBadgeHidden,
                ]);

            } catch (\Exception $e) {
                // Tangani error secara graceful
                Log::error('Error in NotificationComposer: ' . $e->getMessage());
                Log::error($e->getTraceAsString());

                // Set nilai default untuk mencegah error view
                $view->with([
                    'notifications' => collect([]),
                    'unreadNotificationCount' => 0,
                    'totalNotificationCount' => 0,
                    'notificationBadgeHidden' => false,
                ]);
            }
        }
    }
}
