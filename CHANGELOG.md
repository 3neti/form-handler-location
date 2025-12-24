# Changelog

All notable changes to `form-handler-location` will be documented in this file.

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
