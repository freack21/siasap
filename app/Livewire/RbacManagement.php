<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manajemen Pengguna & RBAC — SIASAP')]
class RbacManagement extends Component
{
    public function render()
    {
        $users = User::with('roles')->get();
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('group');

        return view('livewire.rbac-management', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
