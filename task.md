# Task Implementation Plan: SIASAP (IoT Sensor & QoS Topology Analytics)

## 1. Setup & Environment
- [x] Install Livewire via Composer (`composer require livewire/livewire`)
- [x] Configure `Plus Jakarta Sans` font & Dark/Light mode theme system in CSS & Blade Layout

## 2. Database & Models
- [x] Migration `create_testing_sessions_table`
- [x] Migration `create_sensor_logs_table`
- [x] Model `TestingSession` with relationships & helper methods
- [x] Model `SensorLog` with AQI ($KU = \max$) calculation logic & attributes

## 3. Backend Logic & Ingestion REST API
- [x] Controller `TelemetryApiController` for ESP32 ingestion endpoint (`POST /api/telemetry`)
- [x] API routes in `routes/api.php` or `routes/web.php`
- [x] Sensor Data Generator (Service / Seeder) for quick simulation & benchmarking demo

## 4. Frontend & Livewire Components
- [x] Master Blade Layout `resources/views/components/layouts/app.blade.php` with Font Plus Jakarta Sans, Theme Switcher (Dark/Light), Navigation Header
- [x] Livewire Component `TelemetryDashboard` (Node Grid, Badges, Real-time Feed with `wire:poll`)
- [x] Livewire Component `QosAnalytics` (Testing Session Controller, QoS Calculator [PDR, Loss, Hop, RSSI], Head-to-Head Comparative Board)
- [x] Web Routes configuration (`/` for Telemetry, `/analytics` for QoS Benchmarking)

## 5. Verification & Git Commit
- [x] Test Database migration & Tinker script test
- [x] Verify Livewire components & API Ingestion
- [x] Commit code to Git repository
