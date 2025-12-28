<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🎂 Hari Jadi Bulan Ini
        </x-slot>

        @php
            $birthdayStaff = $this->getBirthdayStaff();
        @endphp

        @if($birthdayStaff->count() > 0)
            <div class="space-y-3">
                @foreach($birthdayStaff as $staf)
                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200">
                        <div class="flex-shrink-0">
                            @if($staf->gambar)
                                <img src="{{ asset('storage/gambar/' . $staf->gambar) }}" 
                                     alt="{{ $staf->nama }}"
                                     class="w-12 h-12 rounded-full object-cover border-2 border-yellow-400">
                            @else
                                <div class="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center text-white font-bold">
                                    {{ substr($staf->nama, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">
                                {{ $staf->nama }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $staf->bahagian->bahagian ?? '-' }} • {{ $staf->jawatan->jawatan ?? '-' }}
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="text-lg font-bold text-yellow-600">
                                🎉 {{ $staf->birthday_date }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Happy Birthday!
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">🎂</div>
                <p>Tiada hari jadi bulan ini</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

