# Task: Implement Master Ruangan & Node Perangkat Management (Normalized DB + Admin CRUD + Dynamic Dashboard)

## Status: COMPLETED

### Phase 1: Database Architecture & Models
- [x] Create migration `2026_10_05_000005_create_rooms_and_nodes_tables.php` for normalized `rooms` and `nodes` tables
- [x] Create Model `app/Models/Room.php` (Room hasMany Nodes)
- [x] Create Model `app/Models/Node.php` (Node belongsTo Room, hasMany SensorLogs)
- [x] Update `app/Models/SensorLog.php` (SensorLog belongsTo Node)

### Phase 2: RBAC Seeder & Default Data
- [x] Update `SetupRbacSeeder.php` to include `rooms.manage` permission and system navigation item `Master Ruangan & Node`
- [x] Seed default 3 Rooms (`Ruang Tamu`, `Kamar`, `Dapur`) and 3 Nodes in `SetupRbacSeeder`
- [x] Re-run `php artisan db:seed --class=SetupRbacSeeder`

### Phase 3: Admin Livewire Component & UI (Master Ruangan & Node)
- [x] Create Livewire component `app/Livewire/RoomManagement.php` (CRUD for Rooms & Nodes with validation, RBAC checks)
- [x] Create Livewire view `resources/views/livewire/room-management.blade.php` (Professional Tailwind UI with modals/forms for managing rooms & assigning nodes)
- [x] Register route `/admin/rooms` in `routes/web.php`

### Phase 4: Dynamic Integration in User & Admin Views
- [x] Update `UserDashboard.php` & `user-dashboard.blade.php` to fetch room/node names dynamically from DB
- [x] Update `UserHistory.php` & `user-history.blade.php` to display dynamic room/node names
- [x] Update `TelemetryDashboard.php` & `telemetry-dashboard.blade.php` to display dynamic room/node names

### Phase 5: Verification & Tests
- [x] Run `php artisan test` to verify all automated feature tests pass (10/10 tests passed)
- [x] Perform git commit
