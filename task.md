# Task Plan: Upgrade SIASAP into Premium Sidebar Dashboard with RBAC & Blade Icons

## 1. Package & Icon Installation
- [ ] Install `blade-ui-kit/blade-heroicons` via Composer for comprehensive Blade Icon provider
- [ ] Configure Plus Jakarta Sans & Tailwind v4 Sidebar layout CSS

## 2. RBAC & System Navigation System
- [ ] Migration for RBAC (`roles`, `permissions`, `role_user`, `permission_role`) and `system_navigations`
- [ ] Models: `Role`, `Permission`, `SystemNavigation`
- [ ] Update `User` model with RBAC relationships & helper methods (`hasRole`, `hasPermission`)
- [ ] Seeder: `SetupRbacSeeder` (Seeds Roles, Permissions, Admin User `admin@siasap.test`, and System Navigation links)

## 3. Premium Responsive Sidebar Layout & UI Overhaul
- [ ] Create Master Layout with Collapsible/Mobile Drawer Sidebar: `resources/views/components/layouts/app.blade.php` & `resources/views/layouts/app.blade.php`
- [ ] Add Sidebar Navigation Component featuring dynamic System Navigation items & icons
- [ ] Add Top Header Bar with Breadcrumb, Theme Switcher (Light default), User Dropdown, Live Status Pill

## 4. Livewire Components Overhaul & New Views
- [ ] Update `TelemetryDashboard` to fit the new Admin Dashboard grid layout
- [ ] Update `QosAnalytics` for QoS Benchmarking view with sidebar alignment
- [ ] Create `UserManagement` or `NavigationManagement` Livewire components if needed

## 5. Verification & Git Commit
- [ ] Run `php artisan db:seed --class=SetupRbacSeeder`
- [ ] Run automated tests (`php artisan test`)
- [ ] Execute Git commit for all updated code
