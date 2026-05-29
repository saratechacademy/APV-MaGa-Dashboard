<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $site->name }}
                <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium
                    {{ $site->status === 'active' ? 'bg-green-100 text-green-700' :
                      ($site->status === 'maintenance' ? 'bg-amber-100 text-amber-700' :
                       'bg-gray-100 text-gray-500') }}">
                    {{ ucfirst($site->status) }}
                </span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('sites.index') }}"
                    class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    ← Retour
                </a>
                @if(Auth::user()->isAdmin() || $site->user_id === Auth::id())
                    <a href="{{ route('sites.edit', $site) }}"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        Modifier
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Infos site --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6">
                    <p class="text-sm text-gray-500 mb-1">Pays</p>
                    <p class="text-xl font-semibold text-gray-800">{{ $site->country }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <p class="text-sm text-gray-500 mb-1">Capacité solaire</p>
                    <p class="text-xl font-semibold text-blue-600">
                        {{ $site->capacity_kw ? $site->capacity_kw . ' kW' : '—' }}
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <p class="text-sm text-gray-500 mb-1">Surface agricole</p>
                    <p class="text-xl font-semibold text-green-600">
                        {{ $site->area_m2 ? $site->area_m2 . ' m²' : '—' }}
                    </p>
                </div>
            </div>

            {{-- Dernières lectures --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                {{-- Solaire --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-800">Solaire</h3>
                        <span class="text-xs text-gray-400">Dernière lecture</span>
                    </div>
                    @if($site->latestSolar)
                        <p class="text-3xl font-bold text-amber-500">
                            {{ $site->latestSolar->power_kw }} kW
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $site->latestSolar->recorded_at->diffForHumans() }}
                        </p>
                        @if($site->latestSolar->panel_temp_c)
                            <p class="text-sm text-gray-600 mt-2">
                                Temp. panneau : {{ $site->latestSolar->panel_temp_c }}°C
                            </p>
                        @endif
                    @else
                        <p class="text-gray-400">Aucune donnée</p>
                    @endif
                </div>

                {{-- Eau --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-800">Eau</h3>
                        <span class="text-xs text-gray-400">Dernière lecture</span>
                    </div>
                    @if($site->latestWater)
                        <p class="text-3xl font-bold text-blue-500">
                            {{ $site->latestWater->borehole_level_m }} m
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $site->latestWater->recorded_at->diffForHumans() }}
                        </p>
                        @if($site->latestWater->tank_fill_pct)
                            <p class="text-sm text-gray-600 mt-2">
                                Cuve : {{ $site->latestWater->tank_fill_pct }}%
                            </p>
                        @endif
                    @else
                        <p class="text-gray-400">Aucune donnée</p>
                    @endif
                </div>

                {{-- Météo --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-800">Météo</h3>
                        <span class="text-xs text-gray-400">Dernière lecture</span>
                    </div>
                    @if($site->latestWeather)
                        <p class="text-3xl font-bold text-teal-500">
                            {{ $site->latestWeather->temp_c }}°C
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $site->latestWeather->recorded_at->diffForHumans() }}
                        </p>
                        @if($site->latestWeather->humidity_pct)
                            <p class="text-sm text-gray-600 mt-2">
                                Humidité : {{ $site->latestWeather->humidity_pct }}%
                            </p>
                        @endif
                    @else
                        <p class="text-gray-400">Aucune donnée</p>
                    @endif
                </div>

            </div>

            {{-- Liens vers les données --}}
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Saisir des données</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <a href="#" class="text-center bg-amber-50 text-amber-700 border border-amber-200 px-4 py-3 rounded-lg hover:bg-amber-100 text-sm font-medium">
                        Solaire
                    </a>
                    <a href="#" class="text-center bg-blue-50 text-blue-700 border border-blue-200 px-4 py-3 rounded-lg hover:bg-blue-100 text-sm font-medium">
                        Eau
                    </a>
                    <a href="#" class="text-center bg-teal-50 text-teal-700 border border-teal-200 px-4 py-3 rounded-lg hover:bg-teal-100 text-sm font-medium">
                        Irrigation
                    </a>
                    <a href="#" class="text-center bg-purple-50 text-purple-700 border border-purple-200 px-4 py-3 rounded-lg hover:bg-purple-100 text-sm font-medium">
                        Météo
                    </a>
                    <a href="#" class="text-center bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-lg hover:bg-green-100 text-sm font-medium">
                        Agriculture
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>