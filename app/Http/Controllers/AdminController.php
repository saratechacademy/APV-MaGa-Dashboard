<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\SiteChart;
use App\Models\SiteChartParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUserMail;
use App\Mail\AccountApprovedMail;
use App\Mail\SiteAccessMail;

class AdminController extends Controller
{
    // ── Dashboard ──────────────────────────────────────────────
    public function dashboard()
    {
        $pendingUsers = User::where('status', 'pending')->latest()->get();
        $totalSites   = Site::count();
        $totalUsers   = User::count();
        $activeUsers  = User::where('status', 'active')->count();
        return view('admin.dashboard', compact('pendingUsers','totalSites','totalUsers','activeUsers'));
    }

    // ── Users ──────────────────────────────────────────────────
    public function users()
    {
        $users = User::latest()->get();
        return view('admin.users', compact('users'));
    }

    public function approveUser(User $user)
    {
        $user->update(['status' => 'active']);

        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (\Throwable $e) {
            \Log::warning("Failed to send approval email to {$user->email}: " . $e->getMessage());
        }

        return back()->with('success', "User {$user->name} approved.");
    }

    public function suspendUser(User $user)
    {
        $user->update(['status' => 'suspended']);
        return back()->with('success', "User {$user->name} suspended.");
    }

    public function activateUser(User $user)
    {
        $user->update(['status' => 'active']);
        return back()->with('success', "User {$user->name} activated.");
    }

    public function destroyUser(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    // ── Sites ──────────────────────────────────────────────────
    public function sites()
    {
        $sites  = Site::with('user')->latest()->get();
        $agents = User::where('role', '!=', 'observateur')->where('status', 'active')->get();
        return view('admin.sites', compact('sites', 'agents'));
    }

   public function createSite()
{
    $agents = User::where('status', 'active')->get();
    return view('admin.sites-create', compact('agents'));
}

    public function storeSite(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'country'     => 'required|string|max:100',
            'user_id'     => 'required|exists:users,id',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'capacity_kw' => 'nullable|numeric',
            'area_m2'     => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $site = Site::create([
            'user_id'     => $request->user_id,
            'name'        => $request->name,
            'country'     => $request->country,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'capacity_kw' => $request->capacity_kw,
            'area_m2'     => $request->area_m2,
            'description' => $request->description,
            'status'      => 'active',
        ]);

        if ($request->boolean('create_default_categories')) {
            foreach (SiteCategory::defaults() as $i => $cat) {
                $category = $site->categories()->create([
                    'name'       => $cat['name'],
                    'slug'       => $cat['slug'],
                    'icon'       => $cat['icon'],
                    'color'      => $cat['color'],
                    'is_active'  => true,
                    'sort_order' => $i,
                ]);
                foreach (SiteParameter::defaultsFor($cat['slug']) as $j => $param) {
                    $category->parameters()->create(array_merge($param, ['sort_order' => $j, 'is_active' => true]));
                }
            }
        }

        return redirect()->route('admin.sites')->with('success', "Site \"{$site->name}\" created successfully.");
    }

    public function destroySite(Site $site)
    {
        $site->delete();
        return back()->with('success', 'Site deleted.');
    }

    // ── Categories ─────────────────────────────────────────────
    public function categories(Site $site)
    {
        $categories = $site->categories()->withCount('parameters')->orderBy('sort_order')->get();
        return view('admin.categories', compact('site', 'categories'));
    }

    public function storeCategory(Request $request, Site $site)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $slug = \Str::slug($request->name, '_');
        $category = $site->categories()->create([
            'name'        => $request->name,
            'slug'        => $slug,
            'icon'        => $request->icon,
            'color'       => $request->color,
            'description' => $request->description,
            'is_active'   => true,
            'sort_order'  => $site->categories()->count(),
        ]);

        foreach (SiteParameter::defaultsFor($slug) as $i => $param) {
            $category->parameters()->create(array_merge($param, ['sort_order' => $i, 'is_active' => true]));
        }

        return back()->with('success', "Category \"{$request->name}\" added.");
    }

    public function destroyCategory(Site $site, SiteCategory $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function toggleCategory(Site $site, SiteCategory $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        return back()->with('success', 'Category updated.');
    }

    // ── Parameters ─────────────────────────────────────────────
    public function parameters(Site $site, SiteCategory $category)
    {
        $parameters = $category->parameters()->orderBy('sort_order')->get();
        return view('admin.parameters', compact('site', 'category', 'parameters'));
    }

    public function storeParameter(Request $request, Site $site, SiteCategory $category)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'unit'         => 'nullable|string|max:20',
            'data_type'    => 'required|in:float,integer,boolean,string,switch',
            'control_type' => 'nullable|in:readonly,controllable',
        ]);

        // control_type ne s'applique qu'aux paramètres data_type = switch.
        // Pour tout autre type, on force 'readonly' (valeur par défaut, sans effet).
        $controlType = $request->data_type === 'switch'
            ? ($request->control_type ?? 'readonly')
            : 'readonly';

        // Les switches sont toujours pilotés par l'API (ESP32) : input_type = sensor.
        $inputType = $request->data_type === 'switch'
            ? 'sensor'
            : ($request->input_type ?? 'sensor');

        $category->parameters()->create([
            'name'               => $request->name,
            'slug'               => \Str::slug($request->name, '_'),
            'unit'               => $request->unit,
            'data_type'          => $request->data_type,
            'input_type'         => $inputType,
            'control_type'       => $controlType,
            'group_name'         => $request->group_name ?: null,
            'min_value'          => $request->min_value,
            'max_value'          => $request->max_value,
            'warning_threshold'  => $request->warning_threshold,
            'critical_threshold' => $request->critical_threshold,
            'description'        => $request->description,
            'is_active'          => true,
            'show_on_dashboard'  => $request->boolean('show_on_dashboard', true),
            'sort_order'         => $category->parameters()->count(),
        ]);

        return back()->with('success', "Parameter \"{$request->name}\" added.");
    }

    public function destroyParameter(Site $site, SiteCategory $category, SiteParameter $parameter)
    {
        $parameter->delete();
        return back()->with('success', 'Parameter deleted.');
    }

    public function toggleParameter(Site $site, SiteCategory $category, SiteParameter $parameter)
    {
        $parameter->update(['is_active' => !$parameter->is_active]);
        return back()->with('success', 'Parameter updated.');
    }

    // ── Charts ─────────────────────────────────────────────────
    public function charts(Site $site, SiteCategory $category)
    {
        $charts    = $category->charts()->with('parameters')->orderBy('sort_order')->get();
        $allParams = $category->parameters()->where('is_active', true)->orderBy('sort_order')->get();
        return view('admin.charts', compact('site', 'category', 'charts', 'allParams'));
    }

    public function storeChart(Request $request, Site $site, SiteCategory $category)
    {
        $request->validate(['title' => 'required|string|max:100']);

        $chart = $category->charts()->create([
            'title'       => $request->title,
            'chart_type'  => $request->chart_type  ?? 'line',
            'col_span'    => $request->col_span     ?? 'full',
            'height'      => $request->height       ?? 220,
            'show_legend' => $request->boolean('show_legend', true),
            'dual_axis'   => $request->boolean('dual_axis'),
            'is_active'   => true,
            'sort_order'  => $category->charts()->count(),
        ]);

        $this->syncChartParams($chart, $request->input('params', []));

        return back()->with('success', "Chart \"{$request->title}\" created.");
    }

    public function updateChartParams(Request $request, Site $site, SiteCategory $category, SiteChart $chart)
    {
        $this->syncChartParams($chart, $request->input('params', []));
        return back()->with('success', 'Chart parameters updated.');
    }

    public function destroyChart(Site $site, SiteCategory $category, SiteChart $chart)
    {
        $chart->delete();
        return back()->with('success', 'Chart deleted.');
    }

    public function toggleChart(Site $site, SiteCategory $category, SiteChart $chart)
    {
        $chart->update(['is_active' => !$chart->is_active]);
        return back()->with('success', 'Chart updated.');
    }

    private function syncChartParams(SiteChart $chart, array $params)
    {
        $chart->chartParameters()->delete();
        $order = 0;
        foreach ($params as $paramId => $config) {
            if (!empty($config['selected'])) {
                SiteChartParameter::create([
                    'site_chart_id'     => $chart->id,
                    'site_parameter_id' => $paramId,
                    'color'             => $config['color']  ?? '#1d6ed8',
                    'axis'              => $config['axis']   ?? 'left',
                    'dashed'            => !empty($config['dashed']),
                    'fill'              => !empty($config['fill']),
                    'sort_order'        => $order++,
                ]);
            }
        }
    }

    public function createUser()
{
    return view('admin.users-create');
}

public function storeUser(Request $request)
{
    $request->validate([
        'name'         => 'required|string|max:255',
        'email'        => 'required|email|unique:users,email',
        'password'     => 'required|min:8|confirmed',
        'role'         => 'required|in:admin,agent,observateur',
        'organisation' => 'nullable|string|max:255',
        'country'      => 'nullable|string|max:100',
        'status'       => 'required|in:active,pending,suspended',
    ]);

    $user = User::create([
        'name'         => $request->name,
        'email'        => $request->email,
        'password'     => bcrypt($request->password),
        'role'         => $request->role,
        'organisation' => $request->organisation,
        'country'      => $request->country,
        'status'       => $request->status,
    ]);

    try {
        Mail::to($user->email)->send(new WelcomeUserMail($user));
    } catch (\Throwable $e) {
        \Log::warning("Failed to send welcome email to {$user->email}: " . $e->getMessage());
    }

    return redirect()->route('admin.users')
                     ->with('success', "User {$request->name} created successfully.");
}
public function siteUsers(Site $site)
{
    $assignedIds    = $site->users()->pluck('users.id');
    $assignedUsers  = $site->users()->get();
    $availableUsers = User::where('status', 'active')
                          ->whereNotIn('id', $assignedIds)
                          ->where('role', '!=', 'admin')
                          ->get();
    return view('admin.site-users', compact('site', 'assignedUsers', 'availableUsers'));
}

public function addUserToSite(Request $request, Site $site)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'role'    => 'required|in:agent,observateur',
    ]);
    $site->users()->syncWithoutDetaching([
        $request->user_id => ['role' => $request->role]
    ]);

    $user = User::find($request->user_id);
    if ($user) {
        try {
            Mail::to($user->email)->send(new SiteAccessMail($user, $site, $request->role));
        } catch (\Throwable $e) {
            \Log::warning("Failed to send site access email to {$user->email}: " . $e->getMessage());
        }
    }

    return back()->with('success', 'User added to site successfully.');
}

public function removeUserFromSite(Site $site, User $user)
{
    $site->users()->detach($user->id);
    return back()->with('success', 'User removed from site.');
}
public function updateCategory(Request $request, Site $site, SiteCategory $category)
{
    $request->validate([
        'offline_threshold_minutes' => 'nullable|integer|min:1',
    ]);

    $category->update([
        'name'                      => $request->name,
        'icon'                      => $request->icon,
        'color'                     => $request->color,
        'description'               => $request->description,
        'offline_threshold_minutes' => $request->offline_threshold_minutes ?: 5,
    ]);
    return back()->with('success', "Category {$category->name} updated.");
}

public function updateParameter(Request $request, Site $site, SiteCategory $category, SiteParameter $parameter)
{
    $request->validate([
        'data_type'    => 'required|in:float,integer,boolean,string,switch',
        'control_type' => 'nullable|in:readonly,controllable',
    ]);

    // control_type ne s'applique qu'aux paramètres data_type = switch.
    $controlType = $request->data_type === 'switch'
        ? ($request->control_type ?? 'readonly')
        : 'readonly';

    // Les switches sont toujours pilotés par l'API (ESP32) : input_type = sensor.
    $inputType = $request->data_type === 'switch'
        ? 'sensor'
        : $request->input_type;

    $parameter->update([
        'name'              => $request->name,
        'unit'              => $request->unit,
        'data_type'         => $request->data_type,
        'input_type'        => $inputType,
        'control_type'      => $controlType,
        'group_name'        => $request->group_name,
        'warning_threshold' => $request->warning_threshold,
        'show_on_dashboard' => $request->boolean('show_on_dashboard'),
    ]);
    return back()->with('success', "Parameter {$parameter->name} updated.");
}
}