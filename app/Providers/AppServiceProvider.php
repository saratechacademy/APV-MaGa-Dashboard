<?php
namespace App\Providers;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Share $sites with ALL views (sidebar + dashboard)
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->isAdmin()) {
                    $sites = Site::where('status', 'active')->orderBy('name')->get();
                } else {
                    // Sites via table pivot
                    $pivotSiteIds = $user->assignedSites()->pluck('sites.id')->toArray();
                    // Sites via user_id direct (legacy)
                    $directSiteIds = Site::where('user_id', $user->id)->pluck('id')->toArray();
                    // Fusionner les deux
                    $allSiteIds = array_unique(array_merge($pivotSiteIds, $directSiteIds));
                    $sites = Site::where('status', 'active')
                        ->whereIn('id', $allSiteIds)
                        ->orderBy('name')
                        ->get();
                }
                $view->with('sites', $sites);
            }
        });
    }
}