<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\Room;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ruangan & Perangkat — SIASAP')]
class RoomManagement extends Component
{
    public string $activeTab = 'rooms'; // 'rooms' or 'nodes'

    // Room Form Fields
    public bool $showRoomModal = false;

    public ?int $editingRoomId = null;

    public string $roomName = '';

    public string $roomCode = '';

    public string $roomDescription = '';

    // Node Form Fields
    public bool $showNodeModal = false;

    public ?int $editingNodeId = null;

    public ?int $nodeRoomId = null;

    public string $nodeName = '';

    public string $nodeCode = '';

    public string $nodeDescription = '';

    public bool $nodeIsActive = true;

    public function mount()
    {
        $this->authorizeManage();
    }

    private function authorizeManage(): void
    {
        if (! auth()->user() || ! auth()->user()->hasPermission('rooms.manage')) {
            abort(403, 'Anda tidak memiliki hak akses ke modul ruangan dan perangkat ini.');
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = in_array($tab, ['rooms', 'nodes'], true) ? $tab : 'rooms';
    }

    // --- Room Actions ---
    public function openCreateRoomModal()
    {
        $this->resetRoomFields();
        $this->showRoomModal = true;
    }

    public function editRoom(int $id)
    {
        $room = Room::findOrFail($id);
        $this->editingRoomId = $room->id;
        $this->roomName = $room->name;
        $this->roomCode = $room->code ?? '';
        $this->roomDescription = $room->description ?? '';
        $this->showRoomModal = true;
    }

    public function saveRoom()
    {
        $this->authorizeManage();

        $this->validate([
            'roomName' => 'required|string|max:255',
            'roomCode' => 'nullable|string|max:50|unique:rooms,code,'.$this->editingRoomId,
            'roomDescription' => 'nullable|string|max:500',
        ], [
            'roomName.required' => 'Nama ruangan wajib diisi.',
            'roomCode.unique' => 'Kode ruangan sudah digunakan.',
        ]);

        Room::updateOrCreate(
            ['id' => $this->editingRoomId],
            [
                'name' => $this->roomName,
                'code' => $this->roomCode ? strtoupper($this->roomCode) : null,
                'description' => $this->roomDescription,
            ]
        );

        session()->flash('message', $this->editingRoomId ? 'Data ruangan berhasil diperbarui.' : 'Ruangan baru berhasil ditambahkan.');
        $this->closeRoomModal();
    }

    public function deleteRoom(int $id)
    {
        $this->authorizeManage();

        $room = Room::findOrFail($id);
        $room->delete();
        session()->flash('message', 'Data ruangan berhasil dihapus.');
    }

    public function closeRoomModal()
    {
        $this->showRoomModal = false;
        $this->resetRoomFields();
    }

    private function resetRoomFields()
    {
        $this->editingRoomId = null;
        $this->roomName = '';
        $this->roomCode = '';
        $this->roomDescription = '';
        $this->resetValidation();
    }

    // --- Node Actions ---
    public function openCreateNodeModal()
    {
        $this->resetNodeFields();
        $firstRoom = Room::first();
        $this->nodeRoomId = $firstRoom?->id;
        $this->showNodeModal = true;
    }

    public function editNode(int $id)
    {
        $node = Node::findOrFail($id);
        $this->editingNodeId = $node->id;
        $this->nodeRoomId = $node->room_id;
        $this->nodeName = $node->name;
        $this->nodeCode = $node->code;
        $this->nodeDescription = $node->description ?? '';
        $this->nodeIsActive = $node->is_active;
        $this->showNodeModal = true;
    }

    public function saveNode()
    {
        $this->authorizeManage();

        $this->validate([
            'nodeRoomId' => 'nullable|exists:rooms,id',
            'nodeName' => 'required|string|max:255',
            'nodeCode' => 'required|string|max:50|unique:nodes,code,'.$this->editingNodeId,
            'nodeDescription' => 'nullable|string|max:500',
            'nodeIsActive' => 'boolean',
        ], [
            'nodeName.required' => 'Nama node sensor wajib diisi.',
            'nodeCode.required' => 'Kode unik node wajib diisi.',
            'nodeCode.unique' => 'Kode node sudah digunakan.',
        ]);

        Node::updateOrCreate(
            ['id' => $this->editingNodeId],
            [
                'room_id' => $this->nodeRoomId,
                'name' => $this->nodeName,
                'code' => strtoupper($this->nodeCode),
                'description' => $this->nodeDescription,
                'is_active' => $this->nodeIsActive,
            ]
        );

        session()->flash('message', $this->editingNodeId ? 'Data node sensor berhasil diperbarui.' : 'Node sensor baru berhasil ditambahkan.');
        $this->closeNodeModal();
    }

    public function deleteNode(int $id)
    {
        $this->authorizeManage();

        $node = Node::findOrFail($id);
        $node->delete();
        session()->flash('message', 'Data node sensor berhasil dihapus.');
    }

    public function closeNodeModal()
    {
        $this->showNodeModal = false;
        $this->resetNodeFields();
    }

    private function resetNodeFields()
    {
        $this->editingNodeId = null;
        $this->nodeRoomId = null;
        $this->nodeName = '';
        $this->nodeCode = '';
        $this->nodeDescription = '';
        $this->nodeIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $rooms = Room::withCount(['nodes', 'nodes as active_nodes_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
        $nodes = Node::with('room:id,name')->orderBy('code')->get();

        return view('livewire.room-management', [
            'rooms' => $rooms,
            'nodes' => $nodes,
        ]);
    }
}
