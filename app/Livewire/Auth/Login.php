<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Masuk — SIASAP')]
#[Layout('components.layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required|string',
    ];

    /**
     * Authenticate user credentials and redirect based on RBAC permissions
     */
    public function authenticate()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            // Smart RBAC redirection based on user permissions/roles
            if ($user->hasPermission('telemetry.view') || $user->hasRole('admin') || $user->hasRole('researcher')) {
                return redirect()->intended(route('admin.overview'));
            }

            return redirect()->intended(route('home'));
        }

        $this->addError('email', 'Alamat email atau kata sandi yang Anda masukkan tidak cocok.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
