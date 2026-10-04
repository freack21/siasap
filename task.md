# Task Plan: Single-Door Professional Login & Strict RBAC Enforcement

## 1. Professional Single-Door Login Overhaul
- [ ] Update `app/Livewire/Auth/Login.php` to remove demo presets and implement smart single-door RBAC redirect
- [ ] Overhaul `resources/views/livewire/auth/login.blade.php`: Remove all demo buttons & password hints, create a ultra-clean professional login interface

## 2. Strict RBAC System Enforcement
- [ ] Update Layout Sidebar Navigation (`components/layouts/app.blade.php` & `layouts/app.blade.php`) to filter menu items strictly by logged-in user permissions (`$user->hasPermission(...)`)
- [ ] Add permission authorization checks (`abort(403)`) on all Admin Livewire components (`TelemetryDashboard`, `QosAnalytics`, `RbacManagement`, `NavigationManagement`)

## 3. Verification & Testing
- [ ] Run `php artisan test` to verify single-door login, RBAC redirects, and 403 authorization checks
- [ ] Execute Git commit
