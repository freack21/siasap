<div class="space-y-8">

    <!-- Header Banner & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="space-y-1 z-10">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Master Ruangan & Node Sensor Perangkat
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400 border border-indigo-500/20">
                    Master Data
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kelola pemetaan lokasi ruangan (Ruang Tamu, Kamar, Dapur) dan penempatan perangkat sensor node IoT.
            </p>
        </div>

        <div class="flex items-center gap-3 z-10">
            @if ($activeTab === 'rooms')
                <button wire:click="openCreateRoomModal" 
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span>Tambah Ruangan Baru</span>
                </button>
            @else
                <button wire:click="openCreateNodeModal" 
                        class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20 transition-all flex items-center gap-2">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span>Tambah Node Sensor</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
            <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500 shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Main Content Container with Tabs -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Tab Navigation Bar -->
        <div class="p-4 bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button wire:click="setTab('rooms')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'rooms' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-building-office-2 class="w-4 h-4" />
                    <span>Data Master Ruangan ({{ count($rooms) }})</span>
                </button>

                <button wire:click="setTab('nodes')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'nodes' ? 'bg-sky-600 text-white shadow-md shadow-sky-500/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-cpu-chip class="w-4 h-4" />
                    <span>Node Sensor Perangkat ({{ count($nodes) }})</span>
                </button>
            </div>
        </div>

        <!-- Tab 1: Master Ruangan -->
        @if ($activeTab === 'rooms')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                            <th class="py-3.5 px-6">Nama Ruangan</th>
                            <th class="py-3.5 px-4">Kode Ruangan</th>
                            <th class="py-3.5 px-6">Deskripsi Lokasi</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Node Sensor</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                        @forelse ($rooms as $room)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                            <x-heroicon-o-building-office-2 class="w-4 h-4" />
                                        </div>
                                        <span>{{ $room->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-slate-600 dark:text-slate-300">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        {{ $room->code ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    {{ $room->description ?? 'Tidak ada deskripsi' }}
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-3 py-1 rounded-full font-extrabold text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $room->nodes_count }} Node
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <button wire:click="editRoom({{ $room->id }})" 
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-all">
                                        Edit
                                    </button>
                                    <button wire:click="deleteRoom({{ $room->id }})" 
                                            wire:confirm="Apakah Anda yakin ingin menghapus ruangan ini?"
                                            class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-lg text-xs font-semibold transition-all">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Belum ada data ruangan terdaftar. Silakan tambahkan ruangan baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Tab 2: Master Node Sensor -->
        @if ($activeTab === 'nodes')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                            <th class="py-3.5 px-6">Nama Node Sensor</th>
                            <th class="py-3.5 px-4">Kode Unique</th>
                            <th class="py-3.5 px-4">Lokasi Ruangan</th>
                            <th class="py-3.5 px-4 text-center">Status Keaktifan</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                        @forelse ($nodes as $node)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center font-extrabold">
                                            N{{ $node->id }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $node->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-normal">{{ $node->description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-slate-600 dark:text-slate-300">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                        {{ $node->code }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-semibold text-slate-700 dark:text-slate-200">
                                    @if ($node->room)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-500/20">
                                            <x-heroicon-o-building-office-2 class="w-3.5 h-3.5" />
                                            {{ $node->room->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">Belum ditempatkan</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if ($node->is_active)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <button wire:click="editNode({{ $node->id }})" 
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-all">
                                        Edit
                                    </button>
                                    <button wire:click="deleteNode({{ $node->id }})" 
                                            wire:confirm="Apakah Anda yakin ingin menghapus node sensor ini?"
                                            class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-lg text-xs font-semibold transition-all">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Belum ada data node sensor terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    <!-- MODAL 1: Create / Edit Room -->
    @if ($showRoomModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl w-full max-w-lg overflow-hidden space-y-6 p-6">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ $editingRoomId ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}
                    </h3>
                    <button wire:click="closeRoomModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <form wire:submit.prevent="saveRoom" class="space-y-4 text-xs">
                    <!-- Room Name -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Nama Ruangan <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="roomName" placeholder="Contoh: Ruang Tamu, Kamar Utama, Dapur" 
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('roomName') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Room Code -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Kode Ruangan (Opsional)</label>
                        <input type="text" wire:model="roomCode" placeholder="Contoh: ROOM-TAMU" 
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('roomCode') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Deskripsi Lokasi</label>
                        <textarea wire:model="roomDescription" rows="3" placeholder="Keterangan singkat lokasi ruangan..." 
                                  class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        @error('roomDescription') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="closeRoomModal" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-md shadow-indigo-500/20">
                            Simpan Ruangan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

    <!-- MODAL 2: Create / Edit Node -->
    @if ($showNodeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl w-full max-w-lg overflow-hidden space-y-6 p-6">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ $editingNodeId ? 'Edit Data Node Sensor' : 'Tambah Node Sensor Baru' }}
                    </h3>
                    <button wire:click="closeNodeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <form wire:submit.prevent="saveNode" class="space-y-4 text-xs">
                    <!-- Node Name -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Nama Node Sensor <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="nodeName" placeholder="Contoh: Node Sensor 1 - Ruang Tamu" 
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500">
                        @error('nodeName') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Unique Code -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Kode Unik Perangkat <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="nodeCode" placeholder="Contoh: NODE-01" 
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500">
                        @error('nodeCode') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Select Room -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Penempatan Ruangan</label>
                        <select wire:model="nodeRoomId" 
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="">-- Pilih Ruangan --</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code ?? 'No Code' }})</option>
                            @endforeach
                        </select>
                        @error('nodeRoomId') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 dark:text-slate-300">Deskripsi Sensor</label>
                        <textarea wire:model="nodeDescription" rows="2" placeholder="Catatan spesifikasi sensor..." 
                                  class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                        @error('nodeDescription') <span class="text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Is Active Checkbox -->
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="nodeIsActive" wire:model="nodeIsActive" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <label for="nodeIsActive" class="font-bold text-slate-700 dark:text-slate-300">Node Sensor Aktif Menerima Transmisi Data</label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="closeNodeModal" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold shadow-md shadow-sky-500/20">
                            Simpan Node Sensor
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

</div>
