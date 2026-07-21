<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\RequestHeader;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // === GLOBAL VIEW COMPOSER: Notifikasi Request Pending ===
        // Logic ini berjalan setiap kali 'layouts.app' di-render (Sidebar Badge)
        View::composer('layouts.app', function ($view) {
            $pendingCount = 0;
            $user = Auth::user();

            // Hanya hitung jika User sudah login dan BUKAN role USER (Departemen)
            if ($user && $user->role !== 'USER') { 
                if (strtoupper($user->role) === 'APPROVAL') {
                    $pendingCount = RequestHeader::where('status', 'PENDING_APPROVAL')
                        ->where('approver_id', $user->id)
                        ->count();
                } else {
                    $query = RequestHeader::where('status', 'OPEN');

                    // --- FILTER SCOPE (PENTING) ---
                    // Agar Admin General tidak lihat notif Apparel, dan sebaliknya.
                    $codes = null;
                    if (method_exists($user, 'categoryCodesForScope')) {
                        $codes = $user->categoryCodesForScope();
                    }

                    if (is_array($codes) && count($codes) > 0) {
                        // Cek apakah request ini mengandung item yang masuk scope user
                        $query->whereHas('details.item.category', function ($q) use ($codes) {
                            $q->whereIn('code', $codes);
                        });
                    }

                    $pendingCount = $query->count();
                }
            }

            // Inject variable ke view layout
            $view->with('globalPendingRequestCount', $pendingCount);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}