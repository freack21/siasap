# Task Plan: User View (Public AQI & Health History) vs Admin View (Technical Telemetry & QoS)

## 1. User View Livewire Components & Logic
- [ ] Create `UserDashboard` component (`app/Livewire/UserDashboard.php` & `resources/views/livewire/user-dashboard.blade.php`) for friendly, non-technical Air Quality Index & Health Advice
- [ ] Create `UserHistory` component (`app/Livewire/UserHistory.php` & `resources/views/livewire/user-history.blade.php`) for searching & filtering Air Quality log history without technical jargon

## 2. Navigation & System Seeder Updates
- [ ] Update `SetupRbacSeeder.php` to include User View links (`user.dashboard`, `user.history`) alongside Admin links
- [ ] Update `routes/web.php` to map `/` to `UserDashboard` (default home for public users), `/riwayat` to `UserHistory`, and keep `/admin/telemetry` for technical view!

## 3. UI/UX Enhancements for Public Users
- [ ] Friendly Air Quality Index Badges & Health Advice Cards (Aman/Waspada/Berbahaya)
- [ ] Simple area status grid (Area 1, Area 2, Area 3) with clean visual indicators
- [ ] Interactive History table with search & date filters

## 4. Verification & Git Commit
- [ ] Run `php artisan db:seed --class=SetupRbacSeeder`
- [ ] Run `php artisan test`
- [ ] Execute Git commit
