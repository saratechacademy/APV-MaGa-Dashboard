<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\SiteChart;
use App\Models\SiteChartParameter;
use App\Models\SiteParameterGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user->status = 'active';
        $user->save();

        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (\Throwable $e) {
            \Log::warning("Failed to send approval email to {$user->email}: " . $e->getMessage());
        }

        return back()->with('success', "User {$user->name} approved.");
    }

    public function suspendUser(User $user)
    {
        $user->status = 'suspended';
        $user->save();
        return back()->with('success', "User {$user->name} suspended.");
    }

    public function activateUser(User $user)
    {
        $user->status = 'active';
        $user->save();
        return back()->with('success', "User {$user->name} activated.");
    }

    public function destroyUser(User $user)
    {
        // sites.user_id cascades on delete: deleting a site owner would silently
        // wipe out every category, parameter and historical reading on their
        // sites. Force the admin to reassign or delete those sites explicitly
        // first, rather than losing data behind a generic "Delete user?" prompt.
        $ownedSites = $user->sites()->pluck('name');
        if ($ownedSites->isNotEmpty()) {
            return back()->with('error',
                "Can't delete {$user->name}: still owns " . $ownedSites->count() .
                ' site(s) (' . $ownedSites->implode(', ') . '). Reassign or delete those sites first.'
            );
        }

        // manual_readings.user_id cascades on delete too — an agent assigned to
        // someone else's site (via the site_user pivot, so not caught by the
        // "owns sites" check above) would otherwise silently take their
        // historical field measurements down with them.
        $readingCount = \App\Models\ManualReading::where('user_id', $user->id)->count();
        if ($readingCount > 0) {
            return back()->with('error',
                "Can't delete {$user->name}: they have {$readingCount} manual reading(s) on record. " .
                'Deleting the account would permanently erase that history.'
            );
        }

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

   public function editSite(Site $site)
{
    $agents = User::where('status', 'active')->get();
    return view('admin.sites-edit', compact('site', 'agents'));
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

        $site = DB::transaction(function () use ($request) {
            $site = new Site([
                'user_id'     => $request->user_id,
                'name'        => $request->name,
                'country'     => $request->country,
                'latitude'    => $request->latitude,
                'longitude'   => $request->longitude,
                'capacity_kw' => $request->capacity_kw,
                'area_m2'     => $request->area_m2,
                'description' => $request->description,
            ]);
            $site->status = 'active';
            $site->save();

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
                        $group = $this->resolveGroup($category, null, $param['group_name'] ?? null);
                        $category->parameters()->create(array_merge($param, [
                            'sort_order'              => $j,
                            'is_active'                => true,
                            'site_parameter_group_id'  => $group?->id,
                        ]));
                    }
                }
            }

            return $site;
        });

        return redirect()->route('admin.sites')->with('success', "Site \"{$site->name}\" created successfully.");
    }

    public function updateSite(Request $request, Site $site)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'country'     => 'required|string|max:100',
            'user_id'     => 'required|exists:users,id',
            'status'      => 'required|in:active,inactive,maintenance',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'capacity_kw' => 'nullable|numeric',
            'area_m2'     => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $site->fill([
            'user_id'     => $request->user_id,
            'name'        => $request->name,
            'country'     => $request->country,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'capacity_kw' => $request->capacity_kw,
            'area_m2'     => $request->area_m2,
            'description' => $request->description,
        ]);
        $site->status = $request->status;
        $site->save();

        return redirect()->route('admin.sites')->with('success', "Site \"{$site->name}\" updated successfully.");
    }

    public function destroySite(Site $site)
    {
        $site->delete();
        return back()->with('success', 'Site deleted.');
    }

    public function duplicateSite(Site $site)
    {
        $newSite = null;

        DB::transaction(function () use ($site, &$newSite) {
            $newSite = $site->replicate();
            $newSite->name = $site->name . ' (Copy)';
            $newSite->save();

            $categories = $site->categories()->with(['parameters', 'parameterGroups', 'charts.chartParameters'])->get();

            foreach ($categories as $category) {
                $newCategory = $category->replicate();
                $newCategory->site_id = $newSite->id;
                $newCategory->save();

                $groupIdMap = [];
                foreach ($category->parameterGroups as $group) {
                    $newGroup = $group->replicate();
                    $newGroup->site_category_id = $newCategory->id;
                    $newGroup->save();
                    $groupIdMap[$group->id] = $newGroup->id;
                }

                $paramIdMap = [];
                foreach ($category->parameters as $parameter) {
                    $newParameter = $parameter->replicate();
                    $newParameter->site_category_id = $newCategory->id;
                    $newParameter->site_parameter_group_id = $groupIdMap[$parameter->site_parameter_group_id] ?? null;
                    $newParameter->save();
                    $paramIdMap[$parameter->id] = $newParameter->id;
                }

                foreach ($category->charts as $chart) {
                    $newChart = $chart->replicate();
                    $newChart->site_category_id = $newCategory->id;
                    $newChart->save();

                    foreach ($chart->chartParameters as $chartParam) {
                        if (!isset($paramIdMap[$chartParam->site_parameter_id])) {
                            continue;
                        }
                        $newChartParam = $chartParam->replicate();
                        $newChartParam->site_chart_id = $newChart->id;
                        $newChartParam->site_parameter_id = $paramIdMap[$chartParam->site_parameter_id];
                        $newChartParam->save();
                    }
                }
            }
        });

        return redirect()->route('admin.sites')
                         ->with('success', "Site \"{$site->name}\" duplicated as \"{$newSite->name}\".");
    }

    // ── Categories ─────────────────────────────────────────────
    public function categories(Site $site)
    {
        $categories = $site->categories()->withCount('parameters')->orderBy('sort_order')->get();
        return view('admin.categories', compact('site', 'categories'));
    }

    public function storeCategory(Request $request, Site $site)
    {
        $request->validate([
            'name'                      => 'required|string|max:100',
            'offline_threshold_minutes' => 'nullable|integer|min:1',
        ]);

        $slug = \Str::slug($request->name, '_');
        $category = $site->categories()->create([
            'name'                      => $request->name,
            'slug'                      => $slug,
            'icon'                      => $request->icon,
            'color'                     => $request->color,
            'description'               => $request->description,
            'offline_threshold_minutes' => $request->offline_threshold_minutes ?: 5,
            'is_active'                 => true,
            'sort_order'                => $site->categories()->count(),
        ]);

        foreach (SiteParameter::defaultsFor($slug) as $i => $param) {
            $group = $this->resolveGroup($category, null, $param['group_name'] ?? null);
            $category->parameters()->create(array_merge($param, [
                'sort_order'              => $i,
                'is_active'                => true,
                'site_parameter_group_id'  => $group?->id,
            ]));
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
        $parameters = $category->parameters()->with('group')->orderBy('sort_order')->get();
        $groups     = $category->parameterGroups()->withCount('parameters')->get();
        return view('admin.parameters', compact('site', 'category', 'parameters', 'groups'));
    }

    /**
     * Resolve a group reference to a SiteParameterGroup within the given category.
     * Accepts either an existing group id (from the dropdown) or a free-text name
     * (from "quick add defaults" / default category population) — a group is
     * created on the fly the first time a given name is used in that category.
     */
    private function resolveGroup(SiteCategory $category, $groupId, ?string $groupName): ?SiteParameterGroup
    {
        if ($groupId) {
            return $category->parameterGroups()->find($groupId);
        }

        $groupName = trim((string) $groupName);
        if ($groupName === '') {
            return null;
        }

        $count = $category->parameterGroups()->count();

        return $category->parameterGroups()->firstOrCreate(
            ['name' => $groupName],
            ['sort_order' => $count, 'color' => SiteParameterGroup::nextPaletteColor($count)]
        );
    }

    public function storeParameter(Request $request, Site $site, SiteCategory $category)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                function ($attribute, $value, $fail) use ($category) {
                    $slug = \Str::slug($value, '_');
                    if ($category->parameters()->where('slug', $slug)->exists()) {
                        $fail("A parameter with a similar name already exists in this category (\"{$value}\").");
                    }
                },
            ],
            'unit'                     => 'nullable|string|max:20',
            'data_type'                => 'required|in:float,integer,boolean,string,switch',
            'control_type'             => 'nullable|in:readonly,controllable',
            'site_parameter_group_id'  => 'nullable|exists:site_parameter_groups,id',
            'min_value'                => 'nullable|numeric',
            'max_value'                => 'nullable|numeric|gte:min_value',
            'warning_threshold'        => 'nullable|numeric',
            'critical_threshold'       => 'nullable|numeric',
            'threshold_direction'      => 'nullable|in:below,above',
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

        $group = $this->resolveGroup($category, $request->site_parameter_group_id, $request->group_name);

        $category->parameters()->create([
            'name'                     => $request->name,
            'slug'                     => \Str::slug($request->name, '_'),
            'unit'                     => $request->unit,
            'data_type'                => $request->data_type,
            'input_type'               => $inputType,
            'control_type'             => $controlType,
            'group_name'               => $group?->name,
            'site_parameter_group_id'  => $group?->id,
            'min_value'                => $request->min_value,
            'max_value'                => $request->max_value,
            'warning_threshold'        => $request->warning_threshold,
            'critical_threshold'       => $request->critical_threshold,
            'threshold_direction'      => $request->threshold_direction ?? 'below',
            'description'              => $request->description,
            'is_active'                => true,
            'show_on_dashboard'        => $request->boolean('show_on_dashboard', true),
            'sort_order'               => $category->parameters()->count(),
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

    public function updateChart(Request $request, Site $site, SiteCategory $category, SiteChart $chart)
    {
        $request->validate([
            'title'      => 'required|string|max:100',
            'chart_type' => 'required|in:line,bar,area',
            'col_span'   => 'required|in:full,half,third',
            'height'     => 'required|integer|min:100|max:600',
        ]);

        $chart->update([
            'title'       => $request->title,
            'chart_type'  => $request->chart_type,
            'col_span'    => $request->col_span,
            'height'      => $request->height,
            'show_legend' => $request->boolean('show_legend'),
            'dual_axis'   => $request->boolean('dual_axis'),
        ]);

        return back()->with('success', "Chart \"{$chart->title}\" updated.");
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

public function editUser(User $user)
{
    return view('admin.users-edit', compact('user'));
}

public function updateUser(Request $request, User $user)
{
    $request->validate([
        'name'         => 'required|string|max:255',
        'email'        => ['required', 'email', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
        'password'     => 'nullable|min:8|confirmed',
        'role'         => 'required|in:admin,agent,observateur',
        'organisation' => 'nullable|string|max:255',
        'country'      => 'nullable|string|max:100',
        'status'       => 'required|in:active,pending,suspended',
    ]);

    $user->fill([
        'name'         => $request->name,
        'email'        => $request->email,
        'organisation' => $request->organisation,
        'country'      => $request->country,
        ...($request->filled('password') ? ['password' => bcrypt($request->password)] : []),
    ]);
    $user->role   = $request->role;
    $user->status = $request->status;
    $user->save();

    return redirect()->route('admin.users')
                     ->with('success', "User {$user->name} updated successfully.");
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

    $user = new User([
        'name'         => $request->name,
        'email'        => $request->email,
        'password'     => bcrypt($request->password),
        'organisation' => $request->organisation,
        'country'      => $request->country,
    ]);
    $user->role   = $request->role;
    $user->status = $request->status;
    $user->save();

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
        'data_type'                => 'required|in:float,integer,boolean,string,switch',
        'control_type'             => 'nullable|in:readonly,controllable',
        'site_parameter_group_id'  => 'nullable|exists:site_parameter_groups,id',
        'min_value'                => 'nullable|numeric',
        'max_value'                => 'nullable|numeric|gte:min_value',
        'warning_threshold'        => 'nullable|numeric',
        'critical_threshold'       => 'nullable|numeric',
        'threshold_direction'      => 'nullable|in:below,above',
    ]);

    // control_type ne s'applique qu'aux paramètres data_type = switch.
    $controlType = $request->data_type === 'switch'
        ? ($request->control_type ?? 'readonly')
        : 'readonly';

    // Les switches sont toujours pilotés par l'API (ESP32) : input_type = sensor.
    // The disabled <select> for input_type isn't submitted by the browser
    // while data_type=switch, so this also needs the same fallback storeParameter
    // has — otherwise switching data_type away from "switch" without the field
    // present crashes on the NOT NULL column instead of defaulting sensibly.
    $inputType = $request->data_type === 'switch'
        ? 'sensor'
        : ($request->input_type ?? 'sensor');

    $group = $this->resolveGroup($category, $request->site_parameter_group_id, null);

    $parameter->update([
        'name'                     => $request->name,
        'unit'                     => $request->unit,
        'data_type'                => $request->data_type,
        'input_type'               => $inputType,
        'control_type'             => $controlType,
        'group_name'               => $group?->name,
        'site_parameter_group_id'  => $group?->id,
        'min_value'                => $request->min_value,
        'max_value'                => $request->max_value,
        'warning_threshold'        => $request->warning_threshold,
        'critical_threshold'       => $request->critical_threshold,
        'threshold_direction'      => $request->threshold_direction ?? 'below',
        'show_on_dashboard'        => $request->boolean('show_on_dashboard'),
    ]);
    return back()->with('success', "Parameter {$parameter->name} updated.");
}

    // ── Parameter Groups ───────────────────────────────────────
    public function storeParameterGroup(Request $request, Site $site, SiteCategory $category)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
        ]);

        $count = $category->parameterGroups()->count();

        $category->parameterGroups()->create([
            'name'       => $request->name,
            'color'      => $request->color ?: SiteParameterGroup::nextPaletteColor($count),
            'sort_order' => $count,
        ]);

        return back()->with('success', "Group \"{$request->name}\" added.");
    }

    public function updateParameterGroup(Request $request, Site $site, SiteCategory $category, SiteParameterGroup $group)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
        ]);

        $group->update([
            'name'  => $request->name,
            'color' => $request->color ?: null,
        ]);

        // group_name est dénormalisé sur chaque paramètre (export CSV, onglet
        // Manual Input...) : on le garde synchronisé avec le nom du groupe.
        $group->parameters()->update(['group_name' => $group->name]);

        return back()->with('success', "Group \"{$group->name}\" updated.");
    }

    public function destroyParameterGroup(Site $site, SiteCategory $category, SiteParameterGroup $group)
    {
        $group->parameters()->update(['group_name' => null, 'site_parameter_group_id' => null]);
        $group->delete();

        return back()->with('success', 'Group deleted. Its parameters are now ungrouped.');
    }

    public function reorderParameterGroups(Request $request, Site $site, SiteCategory $category)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:site_parameter_groups,id',
        ]);

        DB::transaction(function () use ($request, $category) {
            foreach ($request->order as $index => $groupId) {
                $category->parameterGroups()->where('id', $groupId)->update(['sort_order' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }
}