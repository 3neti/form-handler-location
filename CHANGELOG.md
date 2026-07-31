# Changelog

All notable changes to `form-handler-location` will be documented in this file.

## v1.2.0 - 2026-07-31

### Added
- Shared Form Flow screen and action components for location capture
- Configurable default, compact, and immersive UI variants
- Laravel 12/13 and Inertia 2/3 compatibility matrix

### Fixed
- Emit the captured map image through the canonical `map` field introduced in v1.1.1

### Changed
- Require Form Flow 1.8 or newer

## v1.1.0 - 2025-12-24

### Added
- Full automation support: `InstallLocationHandlerCommand` for automatic asset publishing
- Composer post-install/update hooks for zero-config installation
- Just `composer require 3neti/form-handler-location` now installs everything automatically

### Changed
- Service provider now registers install command
- Updated to match Phase 2/3 plugin architecture (selfie, KYC, OTP patterns)

## v1.0.0 - 2025-12-24

### Added
- Initial release of location handler plugin
- Browser geolocation capture (GPS coordinates)
- Reverse geocoding via OpenCage API
- Map snapshot generation (Google Maps / Mapbox)
- Address component extraction
- Accuracy measurement
- Auto-registration with Form Flow Manager
- Vue components with shadcn/ui integration
- Publishable config and views
- Support for Laravel 11 and 12

### Technical Details
- Package: `3neti/form-handler-location`
- Namespace: `LBHurtado\FormHandlerLocation`
- PHP: ^8.2
- Laravel: ^11.0 || ^12.0
- License: MIT
