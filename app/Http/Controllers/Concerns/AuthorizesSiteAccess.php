<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Site;
use Illuminate\Support\Facades\Auth;

/**
 * Single source of truth for "can the current user act on this site?".
 * Admins can act on every site; everyone else needs either the modern
 * site_user pivot assignment or the legacy direct site.user_id ownership.
 *
 * Previously this check was copy-pasted per controller and had drifted:
 * some copies only checked user_id, silently locking out agents/observers
 * who were assigned to a site via the pivot table (the "Manage Users" UI).
 */
trait AuthorizesSiteAccess
{
    protected function authorizeSiteAccess(Site $site): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        $inPivot = $site->users()->where('users.id', $user->id)->exists();
        $isDirect = $site->user_id === $user->id;

        if (!$inPivot && !$isDirect) {
            abort(403, 'Access denied.');
        }
    }

    /**
     * IDs of every site the current user may access, for "all sites" views
     * (e.g. bulk export). Admins get null, meaning "no restriction — every
     * site"; everyone else (including observateurs) is scoped to the sites
     * they're actually assigned to, via the pivot table or legacy user_id.
     */
    protected function accessibleSiteIds(): ?array
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return null;
        }

        $pivotIds  = $user->assignedSites()->pluck('sites.id')->toArray();
        $directIds = Site::where('user_id', $user->id)->pluck('id')->toArray();

        return array_values(array_unique(array_merge($pivotIds, $directIds)));
    }
}
