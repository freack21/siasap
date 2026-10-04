# Task Plan: Responsive Login Modal, Custom Scrollbar & Non-Technical Jargon Purge

## 1. CSS Custom Scrollbar
- [ ] Add custom CSS scrollbar rules in `resources/css/app.css` (sleek, 6px width, rounded, theme-adaptive)

## 2. Concise & Responsive Login Page
- [ ] Overhaul `resources/views/livewire/auth/login.blade.php` to be super compact, 100% responsive on all screen sizes, and purge all technical jargon ("IoT", "QoS", "REST API", "Gateway", etc.)

## 3. Strict Non-Technical Language Audit
- [ ] Audit and remove all technical terms from Login, User Dashboard, User History, Layout Footer, and User Navigations
- [ ] Keep technical terms strictly for Admin pages (`/admin/telemetry`, `/admin/qos-analytics`)

## 4. Navigation & Seeder Update
- [ ] Update `SetupRbacSeeder.php` with friendly section titles ("PEMANTAUAN UDARA", "PANEL PENGELOLA", "PENGATURAN")
- [ ] Run `php artisan db:seed --class=SetupRbacSeeder`

## 5. Verification & Git Commit
- [ ] Run `php artisan test`
- [ ] Execute Git commit
