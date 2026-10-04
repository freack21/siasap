<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Masuk Akun — SIASAP')]
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
     * Authenticate user credentials
     */
    public function authenticate()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            if ($user->hasRole('admin') || $user->hasRole('researcher')) {
                return redirect()->intended(route('telemetry'));
            }

            return redirect()->intended(route('user.dashboard'));
        }

        $this->addError('email', 'Email atau kata sandi yang Anda masukkan tidak cocok.');
    }

    /**
     * Quick login helper for demo purposes
     */
    public function quickLogin(string $type)
    {
        $email = $type === 'admin' ? 'admin@siasap.test' : 'user@siasap.test';
        $user = User::where('email', $email)->first();

        if ($user) {
            Auth::login($user, true);
            session()->regenerate();

            if ($user->hasRole('admin')) {
                return redirect()->intended(route('telemetry'));
            }

            return redirect()->intended(route('user.dashboard'));
        }
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
