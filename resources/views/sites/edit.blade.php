<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier — {{ $site->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow p-8">

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('sites.update', $site) }}">
                    @csrf
                    @method('PATCH')

                    {{-- Nom --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nom du site <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $site->name) }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Pays --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Pays <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="country" value="{{ old('country', $site->country) }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Coordonnées GPS --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="number" step="any" name="latitude"
                                value="{{ old('latitude', $site->latitude) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="number" step="any" name="longitude"
                                value="{{ old('longitude', $site->longitude) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Capacité et surface --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Capacité solaire (kW)
                            </label>
                            <input type="number" step="any" name="capacity_kw"
                                value="{{ old('capacity_kw', $site->capacity_kw) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Surface agricole (m²)
                            </label>
                            <input type="number" step="any" name="area_m2"
                                value="{{ old('area_m2', $site->area_m2) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Statut --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                        <select name="status"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active" {{ $site->status === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ $site->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            <option value="maintenance" {{ $site->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="4"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $site->description) }}</textarea>
                    </div>

                    {{-- Boutons --}}
                    <div class="flex gap-4">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-medium">
                            Enregistrer
                        </button>
                        <a href="{{ route('sites.show', $site) }}"
                            class="flex-1 text-center border border-gray-300 text-gray-600 py-2 rounded-lg hover:bg-gray-50">
                            Annuler
                        </a>

                        @if(Auth::user()->isAdmin() || $site->user_id === Auth::id())
                            <form method="POST" action="{{ route('sites.destroy', $site) }}"
                                onsubmit="return confirm('Supprimer ce site et toutes ses données ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                                    Supprimer
                                </button>
                            </form>
                        @endif
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>