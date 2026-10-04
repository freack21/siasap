# Task Plan: Authentication Flow & Route Protection (Login, Auth Middleware & Logout)

## 1. Authentication Component & Login View
- [ ] Create Livewire Component `Login` (`app/Livewire/Auth/Login.php` & `resources/views/livewire/auth/login.blade.php`) with email/password validation and quick demo login presets (`Admin` & `User`)
- [ ] Add Logout method in App Layout / Navigation Header

## 2. Route Protection & Middleware
- [ ] Update `routes/web.php` to wrap dashboard and admin routes inside `auth` middleware
- [ ] Map `/login` to Livewire `Login` component
- [ ] Add guest redirect handling (redirect unauthenticated users to `/login`)

## 3. Navigation Header & Profile Integration
- [ ] Display real authenticated user data (`auth()->user()`) in Sidebar and Top Navbar
- [ ] Add functional Logout button with redirect back to `/login`

## 4. Verification & Git Commit
- [ ] Run `php artisan test` to verify authentication flow & route protection
- [ ] Execute Git commit
