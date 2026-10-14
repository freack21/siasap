<div class="space-y-6">
    <x-ui.page-header title="Ruangan & Perangkat"
                      description="Kelola daftar ruangan yang dipantau beserta node sensor IoT yang terpasang di setiap ruangan.">
        <x-slot:eyebrow>Data Master</x-slot:eyebrow>
        <x-slot:actions>
            @if ($activeTab === 'rooms')
                <button type="button" wire:click="openCreateRoomModal" class="btn btn-primary">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah Ruangan
                </button>
            @else
                <button type="button" wire:click="openCreateNodeModal" class="btn btn-primary">
                    <x-heroicon-o-plus class="size-4" />
                    Tambah Node
                </button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    {{-- Segmented control --}}
    <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1" role="tablist">
        <button type="button" role="tab" wire:click="setTab('rooms')" aria-selected="{{ $activeTab === 'rooms' ? 'true' : 'false' }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-slate-900 text-white' => $activeTab === 'rooms',
                    'text-slate-600 hover:text-slate-900' => $activeTab !== 'rooms',
                ])>
            <x-heroicon-o-building-office-2 class="size-4" />
            Ruangan
            <span class="num text-xs opacity-70">{{ $rooms->count() }}</span>
        </button>
        <button type="button" role="tab" wire:click="setTab('nodes')" aria-selected="{{ $activeTab === 'nodes' ? 'true' : 'false' }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-slate-900 text-white' => $activeTab === 'nodes',
                    'text-slate-600 hover:text-slate-900' => $activeTab !== 'nodes',
                ])>
            <x-heroicon-o-cpu-chip class="size-4" />
            Perangkat Node
            <span class="num text-xs opacity-70">{{ $nodes->count() }}</span>
        </button>
    </div>

    @if ($activeTab === 'rooms')
        <x-ui.card :padded="false">
            @if ($rooms->isEmpty())
                <x-ui.empty-state title="Belum ada ruangan"
                                  description="Tambahkan ruangan pertama agar node sensor dapat ditempatkan dan datanya dipantau."
                                  icon="heroicon-o-building-office-2">
                    <button type="button" wire:click="openCreateRoomModal" class="btn btn-secondary btn-sm">
                        <x-heroicon-o-plus class="size-4" /> Tambah Ruangan
                    </button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Ruangan</th>
                                <th>Kode</th>
                                <th class="hidden md:table-cell">Deskripsi</th>
                                <th class="text-right">Node</th>
                                <th class="w-24 text-right"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rooms as $room)
                                <tr wire:key="room-{{ $room->id }}">
                                    <td class="font-medium text-slate-900">{{ $room->name }}</td>
                                    <td>
                                        @if ($room->code)
                                            <span class="font-mono text-xs text-slate-600">{{ $room->code }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="hidden max-w-md text-slate-500 md:table-cell">
                                        {{ $room->description ?: '—' }}
                                    </td>
                                    <td class="num text-right whitespace-nowrap">
                                        <span class="font-medium text-slate-900">{{ $room->nodes_count }}</span>
                                        <span class="text-xs text-slate-500">({{ $room->active_nodes_count }} aktif)</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <button type="button" wire:click="editRoom({{ $room->id }})" class="btn btn-ghost btn-sm" title="Ubah ruangan" aria-label="Ubah {{ $room->name }}">
                                            <x-heroicon-o-pencil-square class="size-4" />
                                        </button>
                                        <button type="button" wire:click="deleteRoom({{ $room->id }})"
                                                wire:confirm="Hapus ruangan &quot;{{ $room->name }}&quot;? Node di ruangan ini akan menjadi tanpa ruangan."
                                                class="btn btn-ghost btn-sm hover:bg-rose-50 hover:text-rose-600" title="Hapus ruangan" aria-label="Hapus {{ $room->name }}">
                                            <x-heroicon-o-trash class="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    @else
        <x-ui.card :padded="false">
            @if ($nodes->isEmpty())
                <x-ui.empty-state title="Belum ada node sensor"
                                  description="Daftarkan node sensor dengan kode yang sama seperti yang dikirim perangkat IoT."
                                  icon="heroicon-o-cpu-chip">
                    <button type="button" wire:click="openCreateNodeModal" class="btn btn-secondary btn-sm">
                        <x-heroicon-o-plus class="size-4" /> Tambah Node
                    </button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Node</th>
                                <th>Kode</th>
                                <th>Ruangan</th>
                                <th>Status</th>
                                <th class="w-24 text-right"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($nodes as $node)
                                <tr wire:key="node-{{ $node->id }}">
                                    <td>
                                        <p class="font-medium text-slate-900">{{ $node->name }}</p>
                                        @if ($node->description)
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $node->description }}</p>
                                        @endif
                                    </td>
                                    <td><span class="font-mono text-xs text-slate-600">{{ $node->code }}</span></td>
                                    <td>
                                        @if ($node->room)
                                            {{ $node->room->name }}
                                        @else
                                            <span class="text-slate-400">Belum ditempatkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($node->is_active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-accent-50 px-2 py-0.5 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-100">
                                                <span class="size-1.5 rounded-full bg-accent-500"></span> Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200">
                                                <span class="size-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <button type="button" wire:click="editNode({{ $node->id }})" class="btn btn-ghost btn-sm" title="Ubah node" aria-label="Ubah {{ $node->name }}">
                                            <x-heroicon-o-pencil-square class="size-4" />
                                        </button>
                                        <button type="button" wire:click="deleteNode({{ $node->id }})"
                                                wire:confirm="Hapus node &quot;{{ $node->name }}&quot;? Data pembacaan yang sudah tersimpan tetap ada."
                                                class="btn btn-ghost btn-sm hover:bg-rose-50 hover:text-rose-600" title="Hapus node" aria-label="Hapus {{ $node->name }}">
                                            <x-heroicon-o-trash class="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    @endif

    {{-- Modal ruangan --}}
    @if ($showRoomModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
             x-data x-on:keydown.escape.window="$wire.closeRoomModal()"
             role="dialog" aria-modal="true" aria-labelledby="room-modal-title">
            <div class="absolute inset-0" wire:click="closeRoomModal"></div>
            <form wire:submit="saveRoom" class="card relative w-full max-w-md">
                <div class="card-header">
                    <h2 id="room-modal-title" class="card-title">{{ $editingRoomId ? 'Ubah Ruangan' : 'Tambah Ruangan' }}</h2>
                    <button type="button" wire:click="closeRoomModal" class="btn btn-ghost btn-sm" aria-label="Tutup">
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                </div>

                <div class="card-body space-y-4">
                    <div>
                        <label for="roomName" class="label">Nama ruangan</label>
                        <input id="roomName" type="text" wire:model="roomName" class="input" placeholder="Contoh: Ruang Tamu" autofocus>
                        @error('roomName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="roomCode" class="label">Kode ruangan <span class="text-slate-400">(opsional)</span></label>
                        <input id="roomCode" type="text" wire:model="roomCode" class="input font-mono uppercase" placeholder="ROOM-TAMU">
                        @error('roomCode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="roomDescription" class="label">Deskripsi <span class="text-slate-400">(opsional)</span></label>
                        <textarea id="roomDescription" wire:model="roomDescription" rows="3" class="input" placeholder="Keterangan singkat lokasi ruangan"></textarea>
                        @error('roomDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="closeRoomModal" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveRoom">
                        {{ $editingRoomId ? 'Simpan Perubahan' : 'Simpan Ruangan' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal node --}}
    @if ($showNodeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
             x-data x-on:keydown.escape.window="$wire.closeNodeModal()"
             role="dialog" aria-modal="true" aria-labelledby="node-modal-title">
            <div class="absolute inset-0" wire:click="closeNodeModal"></div>
            <form wire:submit="saveNode" class="card relative w-full max-w-md">
                <div class="card-header">
                    <h2 id="node-modal-title" class="card-title">{{ $editingNodeId ? 'Ubah Node Sensor' : 'Tambah Node Sensor' }}</h2>
                    <button type="button" wire:click="closeNodeModal" class="btn btn-ghost btn-sm" aria-label="Tutup">
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                </div>

                <div class="card-body space-y-4">
                    <div>
                        <label for="nodeName" class="label">Nama node</label>
                        <input id="nodeName" type="text" wire:model="nodeName" class="input" placeholder="Contoh: Node Sensor 1 - Ruang Tamu" autofocus>
                        @error('nodeName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nodeCode" class="label">Kode node</label>
                        <input id="nodeCode" type="text" wire:model="nodeCode" class="input font-mono uppercase" placeholder="NODE-01">
                        <p class="mt-1 text-xs text-slate-500">Harus sama dengan kode yang dikirim perangkat.</p>
                        @error('nodeCode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nodeRoomId" class="label">Ruangan</label>
                        <select id="nodeRoomId" wire:model="nodeRoomId" class="input">
                            <option value="">Belum ditempatkan</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                        @error('nodeRoomId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nodeDescription" class="label">Deskripsi <span class="text-slate-400">(opsional)</span></label>
                        <textarea id="nodeDescription" wire:model="nodeDescription" rows="2" class="input" placeholder="Catatan penempatan atau spesifikasi"></textarea>
                        @error('nodeDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2.5 text-sm text-slate-700">
                        <input type="checkbox" wire:model="nodeIsActive" class="size-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500">
                        Node aktif dan menerima data
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="closeNodeModal" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveNode">
                        {{ $editingNodeId ? 'Simpan Perubahan' : 'Simpan Node' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
