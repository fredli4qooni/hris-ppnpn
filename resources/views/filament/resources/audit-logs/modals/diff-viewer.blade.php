@php
    $oldValues = is_array($record->old_values) ? $record->old_values : [];
    $newValues = is_array($record->new_values) ? $record->new_values : [];

    $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
    $modifiedKeys = [];

    foreach ($allKeys as $key) {
        $valOld = $oldValues[$key] ?? null;
        $valNew = $newValues[$key] ?? null;
        if ($valOld !== $valNew) {
            $modifiedKeys[] = $key;
        }
    }
@endphp

<div class="space-y-6">
    {{-- Header Banner / Meta Information --}}
    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg uppercase tracking-wider
                    @if(str_contains($record->action, 'FAILED') || str_contains($record->action, 'REJECTED') || str_contains($record->action, 'INACTIVATED'))
                        bg-red-100 text-red-800 border border-red-200
                    @elseif(str_contains($record->action, 'DISMISSED') || str_contains($record->action, 'REOPENED') || str_contains($record->action, 'REVIEW'))
                        bg-amber-100 text-amber-800 border border-amber-200
                    @elseif(str_contains($record->action, 'SUCCESS') || str_contains($record->action, 'APPROVED') || str_contains($record->action, 'CLOSED') || str_contains($record->action, 'EXTENDED'))
                        bg-emerald-100 text-emerald-800 border border-emerald-200
                    @else
                        bg-sky-100 text-sky-800 border border-sky-200
                    @endif">
                    {{ $record->action }}
                </span>
                <span class="text-xs text-slate-500 font-mono">ID: #{{ $record->id }}</span>
            </div>

            <div class="text-xs text-slate-600 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $record->created_at ? $record->created_at->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}</span>
                <span class="text-slate-400">({{ $record->created_at ? $record->created_at->diffForHumans() : '-' }})</span>
            </div>
        </div>

        {{-- Meta Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            <div>
                <span class="text-slate-400 font-semibold block uppercase tracking-wider">Aktor / Pengguna</span>
                <div class="font-bold text-slate-800 mt-0.5 flex items-center gap-1.5">
                    <span>{{ $record->user?->name ?? 'Sistem Otomatis (Scheduler)' }}</span>
                </div>
                <div class="text-slate-500 text-[11px]">{{ $record->user?->email ?? 'Daemon Cron Worker' }}</div>
            </div>

            <div>
                <span class="text-slate-400 font-semibold block uppercase tracking-wider">Entitas / Objek Target</span>
                <div class="font-bold text-slate-800 mt-0.5">
                    {{ $record->entity_type ? class_basename($record->entity_type) : '-' }}
                    @if($record->entity_id)
                        <span class="font-mono text-slate-500 font-normal">#{{ $record->entity_id }}</span>
                    @endif
                </div>
                <div class="text-slate-500 text-[11px] truncate" title="{{ $record->entity_type }}">{{ $record->entity_type ?? '-' }}</div>
            </div>

            <div>
                <span class="text-slate-400 font-semibold block uppercase tracking-wider">Jaringan & Klien</span>
                <div class="font-bold font-mono text-slate-800 mt-0.5">
                    {{ $record->ip_address ?? '127.0.0.1 (CLI)' }}
                </div>
                <div class="text-slate-500 text-[11px] truncate" title="{{ $record->user_agent }}">
                    {{ $record->user_agent ? \Illuminate\Support\Str::limit($record->user_agent, 40) : 'Internal Console / Service' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Mutated Keys Highlight Badge Bar --}}
    @if(count($modifiedKeys) > 0)
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-bold text-amber-900">
                    Bidang yang Mengalami Perubahan Nilai ({{ count($modifiedKeys) }} atribut terdeteksi beda):
                </span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($modifiedKeys as $mKey)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-amber-200/80 text-amber-900 border border-amber-300">
                        {{ $mKey }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Side-by-side JSON Diff Viewer --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Before / Old Values --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm flex flex-col">
            <div class="bg-slate-100 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span class="font-bold text-xs uppercase tracking-wider text-slate-700">Nilai Sebelum (Old Values)</span>
                </div>
                <span class="text-[11px] font-mono text-slate-500">{{ count($oldValues) }} properti</span>
            </div>

            <div class="p-4 flex-1">
                @if(empty($oldValues))
                    <div class="h-full flex flex-col items-center justify-center py-8 text-center text-slate-400">
                        <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs italic">Tidak ada nilai data sebelum.<br/>(Peristiwa Baru / Create Event)</p>
                    </div>
                @else
                    {{-- Key Value preview --}}
                    <div class="space-y-1.5 mb-3">
                        @foreach($oldValues as $k => $v)
                            <div class="flex items-start justify-between text-xs py-1 px-2 rounded {{ in_array($k, $modifiedKeys, true) ? 'bg-amber-50/70 text-amber-950 font-medium' : 'text-slate-600' }}">
                                <span class="font-mono text-[11px] text-slate-500 font-semibold">{{ $k }}:</span>
                                <span class="font-mono text-right truncate max-w-[200px]" title="{{ is_array($v) ? json_encode($v) : (string) $v }}">
                                    {{ is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : ($v ?? 'null')) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Raw JSON block --}}
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Payload JSON Mentah:</span>
                        <pre class="bg-slate-900 text-amber-300 font-mono text-[11px] p-3 rounded-lg overflow-x-auto leading-relaxed border border-slate-800 max-h-64"><code>{{ json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                @endif
            </div>
        </div>

        {{-- After / New Values --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm flex flex-col">
            <div class="bg-slate-100 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="font-bold text-xs uppercase tracking-wider text-slate-700">Nilai Sesudah (New Values)</span>
                </div>
                <span class="text-[11px] font-mono text-slate-500">{{ count($newValues) }} properti</span>
            </div>

            <div class="p-4 flex-1">
                @if(empty($newValues))
                    <div class="h-full flex flex-col items-center justify-center py-8 text-center text-slate-400">
                        <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <p class="text-xs italic">Tidak ada nilai data sesudah.<br/>(Peristiwa Penghapusan / Terminal Event)</p>
                    </div>
                @else
                    {{-- Key Value preview --}}
                    <div class="space-y-1.5 mb-3">
                        @foreach($newValues as $k => $v)
                            <div class="flex items-start justify-between text-xs py-1 px-2 rounded {{ in_array($k, $modifiedKeys, true) ? 'bg-emerald-50/70 text-emerald-950 font-medium' : 'text-slate-600' }}">
                                <span class="font-mono text-[11px] text-slate-500 font-semibold">{{ $k }}:</span>
                                <span class="font-mono text-right truncate max-w-[200px]" title="{{ is_array($v) ? json_encode($v) : (string) $v }}">
                                    {{ is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : ($v ?? 'null')) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Raw JSON block --}}
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Payload JSON Mentah:</span>
                        <pre class="bg-slate-900 text-emerald-400 font-mono text-[11px] p-3 rounded-lg overflow-x-auto leading-relaxed border border-slate-800 max-h-64"><code>{{ json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
