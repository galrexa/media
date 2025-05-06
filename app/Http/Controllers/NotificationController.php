<?php
// app/Http/Controllers/NotificationController.php
namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar notifikasi pengguna saat ini.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userId = Auth::id();
        $notifikasi = Notifikasi::where('user_id', $userId)
                             ->orderBy('created_at', 'desc')
                             ->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    /**
     * Menandai notifikasi sebagai sudah dibaca.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead(Notification $notifikasi)
    {
        // Pastikan notifikasi milik user saat ini
        if ($notifikasi->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk notifikasi ini.');
        }

        $notifikasi->markAsRead();

        // Jika ada link, redirect ke link tersebut
        if ($notifikasi->link) {
            return redirect($notifikasi->link);
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Menandai semua notifikasi sebagai sudah dibaca.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();

        Notifikasi::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Menampilkan semua notifikasi dalam format JSON, untuk AJAX.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotifications()
    {
        $userId = Auth::id();
        $notifications = Notifikasi::where('user_id', $userId)
                                  ->where('is_read', false)
                                  ->orderBy('created_at', 'desc')
                                  ->take(5)
                                  ->get();

        $count = Notifikasi::where('user_id', $userId)
                           ->where('is_read', false)
                           ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'notifications' => $notifications
        ]);
    }

    /**
     * Menghapus notifikasi tertentu.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Notification $notifikasi)
    {
        // Pastikan notifikasi milik user saat ini
        if ($notifikasi->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus notifikasi ini.');
        }

        $notifikasi->delete();

        return redirect()->back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Menghapus semua notifikasi yang sudah dibaca.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyRead()
    {
        $userId = Auth::id();

        Notifikasi::where('user_id', $userId)
                 ->where('is_read', true)
                 ->delete();

        return redirect()->back()->with('success', 'Semua notifikasi yang sudah dibaca berhasil dihapus.');
    }

    /**
     * Menghapus semua notifikasi pengguna.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAll()
    {
        $userId = Auth::id();

        Notifikasi::where('user_id', $userId)->delete();

        return redirect()->back()->with('success', 'Semua notifikasi berhasil dihapus.');
    }

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
                $userId = Auth::id();

                // Ambil notifikasi terbaru untuk ditampilkan di dropdown (batasi 5 teratas)
                $notifications = Notifikasi::where('user_id', $userId)
                                    ->orderBy('created_at', 'desc')
                                    ->take(5)
                                    ->get();

                // Hitung jumlah notifikasi yang belum dibaca
                $unreadNotificationsCount = Notifikasi::where('user_id', $userId)
                                    ->where('is_read', false)
                                    ->count();

                // Pass data ke view
                $view->with([
                    'notifications' => $notifications,
                    'unreadNotificationsCount' => $unreadNotificationsCount,
                ]);

                Log::info('NotificationComposer loaded data successfully');

            } catch (\Exception $e) {
                Log::error('Error in NotificationComposer: ' . $e->getMessage());
                $view->with([
                    'notifications' => collect(),
                    'unreadNotificationsCount' => 0,
                ]);
            }
        }
    }
}
