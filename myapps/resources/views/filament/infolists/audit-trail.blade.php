<div class="space-y-4">
    @if($getState() && count($getState()) > 0)
        <div class="relative pl-8 pb-4">
            @foreach($getState() as $audit)
                <div class="relative mb-6">
                    <!-- Timeline dot -->
                    <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-blue-900 border-4 border-white shadow"></div>
                    
                    <!-- Timeline content -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $audit->human_readable_description }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    oleh <strong>{{ $audit->pengguna?->nama ?? 'System' }}</strong>
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                {{ $audit->waktu->diffForHumans() }}
                            </span>
                        </div>
                        
                        @if(count($audit->changes) > 0)
                            <div class="mt-3 space-y-2">
                                @foreach($audit->changes as $change)
                                    <div class="flex items-start gap-2 text-sm">
                                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[100px]">
                                            {{ $change['field'] }}:
                                        </span>
                                        <div class="flex-1">
                                            <span class="text-red-600 dark:text-red-400 line-through">
                                                {{ $change['old'] ?? 'N/A' }}
                                            </span>
                                            <span class="mx-2 text-gray-400">→</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">
                                                {{ $change['new'] ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <div class="mt-2 text-xs text-gray-400">
                            {{ $audit->waktu->format('d/m/Y H:i:s') }}
                        </div>
                    </div>
                </div>
            @endforeach
            
            <!-- Timeline line -->
            <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-gray-300"></div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 font-medium">Tiada rekod audit dijumpai</p>
            <p class="text-sm text-gray-400">Sejarah perubahan akan dipaparkan di sini</p>
        </div>
    @endif
</div>

