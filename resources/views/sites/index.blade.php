<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mes Sites
            </h2>
            @if(Auth::user()->isAgent() || Auth::user()->isAdmin())
                <a href="{{ route('sites.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                    + Nouveau site
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($sites->isEmpty())
                <div class="bg-white rounded-xl shadow p-12 text-center">
                    <p class="text-gray-400 text-lg mb-4">Aucun site pour le moment.</p>
                    @if(Auth::user()->isAgent() || Auth::user()->isAdmin())
                        <a href="{{ route('sites.create') }}"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            Créer votre premier site
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($sites as $site)
                    <div class="bg-white rounded-xl shadow hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $site->name }}</h3>
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $site->status === 'active' ? 'bg-green-100 text-green-700' :
                                      ($site->status === 'maintenance' ? 'bg-amber-100 text-amber-700' :
                                       'bg-gray-100 text-gray-500') }}">
                                    {{ ucfirst($site->status) }}
                                </span>
                            </div>

                            <p class="text-gray-500 text-sm mb-4">
                                {{ $site->country }}
                                @if($site->capacity_kw)
                                    · {{ $site->capacity_kw }} kW
                                @endif
                                @if($site->area_m2)
                                    · {{ $site->area_m2 }} m²
                                @endif
                            </p>

                            @if($site->description)
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    {{ $site->description }}
                                </p>
                            @endif

                            @if(Auth::user()->isAdmin())
                                <p class="text-xs text-gray-400 mb-3">
                                    Propriétaire : {{ $site->user->name }}
                                </p>
                            @endif

                            <div class="flex gap-2 pt-3 border-t border-gray-100">
                                <a href="{{ route('sites.show', $site) }}"
                                    class="flex-1 text-center bg-blue-600 text-white text-sm py-2 rounded-lg hover:bg-blue-700">
                                    Voir le dashboard
                                </a>
                                @if(Auth::user()->isAdmin() || $site->user_id === Auth::id())
                                    <a href="{{ route('sites.edit', $site) }}"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm">
                                        Modifier
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>