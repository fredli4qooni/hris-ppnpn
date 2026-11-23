<x-layouts.mobile title="Pusat Notifikasi">
    <div class="space-y-4">
        <!-- Top Navigation -->
        <div class="flex items-center justify-between">
            <a href="{{ route('mobile.home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#0058A8] transition">
                <span>&larr;</span>
                <span>Beranda</span>
            </a>

            @if($unreadCount > 0)
                <form action="{{ route('mobile.notifications.mark-all-read') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-[11px] font-bold text-[#0058A8] hover:text-[#003D75] underline cursor-pointer">
                        Tandai Semua Dibaca
                    </button>
                </form>
            @endif
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0058A8] flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-sm">Pusat Notifikasi Terpadu</h2>
                        <p class="text-[11px] text-gray-500">Pemberitahuan izin, sanksi, dan tugas dinas</p>
                    </div>
                </div>

                @if($unreadCount > 0)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                        {{ $unreadCount }} Baru
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                        Semua Dibaca
                    </span>
                @endif
            </div>

            <!-- Filter Tabs -->
            <div class="flex bg-gray-100 p-1 rounded-xl text-[11px] font-bold mt-4">
                <a 
                    href="{{ route('mobile.notifications.index', ['filter' => 'all']) }}" 
                    class="flex-1 py-1.5 text-center rounded-lg transition {{ $activeFilter === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}"
                >
                    Semua
                </a>
                <a 
                    href="{{ route('mobile.notifications.index', ['filter' => 'unread']) }}" 
                    class="flex-1 py-1.5 text-center rounded-lg transition {{ $activeFilter === 'unread' ? 'bg-white text-[#0058A8] shadow-xs' : 'text-gray-500 hover:text-gray-900' }}"
                >
                    Belum Dibaca ({{ $unreadCount }})
                </a>
                <a 
                    href="{{ route('mobile.notifications.index', ['filter' => 'read']) }}" 
                    class="flex-1 py-1.5 text-center rounded-lg transition {{ $activeFilter === 'read' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}"
                >
                    Riwayat
                </a>
            </div>
        </div>

        <!-- Notification Items List -->
        <div class="space-y-2.5">
            @forelse($notifications as $n)
                @php
                    $isUnread = $n->read_at === null;
                    $color = $n->data['color'] ?? 'primary';
                    $url = $n->data['url'] ?? ($n->data['action_url'] ?? null);
                @endphp
                <div class="bg-white rounded-2xl p-4 border {{ $isUnread ? 'border-blue-200 bg-blue-50/15 shadow-xs' : 'border-gray-100 shadow-xs' }} space-y-2.5 transition">
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-start space-x-2.5">
                            <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs mt-0.5 {{ $color === 'danger' ? 'bg-rose-50 text-rose-600 border border-rose-200' : ($color === 'warning' ? 'bg-amber-50 text-amber-600 border border-amber-200' : ($color === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-blue-50 text-[#0058A8] border border-blue-200')) }}">
                                @if($color === 'danger')
                                    ⚠️
                                @elseif($color === 'warning')
                                    🔔
                                @elseif($color === 'success')
                                    ✓
                                @else
                                    ℹ️
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h4 class="font-bold text-xs text-gray-900">
                                        {{ $n->data['title'] ?? 'Pemberitahuan Sistem' }}
                                    </h4>
                                    @if($isUnread)
                                        <span class="w-2 h-2 rounded-full bg-rose-600 shrink-0" title="Belum dibaca"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-gray-400 block mt-0.5">
                                    {{ $n->created_at->diffForHumans() }} &bull; {{ $n->created_at->translatedFormat('d M Y, H:i') }} WIB
                                </span>
                            </div>
                        </div>

                        <!-- Read Status Badge -->
                        @if($isUnread)
                            <form action="{{ route('mobile.notifications.mark-read', $n->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-[10px] text-gray-400 hover:text-blue-600 p-1 font-semibold cursor-pointer" title="Tandai telah dibaca">
                                    Tandai Baca
                                </button>
                            </form>
                        @endif
                    </div>

                    <p class="text-xs text-gray-700 leading-relaxed pl-10.5">
                        {{ $n->data['body'] ?? '' }}
                    </p>

                    @if($url)
                        <div class="pt-1.5 border-t border-gray-100 flex justify-end">
                            <form action="{{ route('mobile.notifications.mark-read', $n->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="target_url" value="{{ $url }}">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#0058A8] border border-blue-200 text-[11px] font-bold transition cursor-pointer">
                                    <span>Buka Rincian Transaksi</span>
                                    <span>&rarr;</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm text-center space-y-2">
                    <div class="w-12 h-12 mx-auto rounded-full bg-gray-50 text-gray-400 flex items-center justify-center font-bold text-xl">
                        🔔
                    </div>
                    <h4 class="text-sm font-bold text-gray-900">Tidak Ada Notifikasi</h4>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">
                        @if($activeFilter === 'unread')
                            Seluruh notifikasi telah Anda baca. Tidak ada pemberitahuan baru yang tertunda.
                        @else
                            Belum ada riwayat pemberitahuan dalam aplikasi.
                        @endif
                    </p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="pt-2">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.mobile>
