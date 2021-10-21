# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/screid123/wp-plugin-template/compare/v1.0.2...HEAD)

## v1.0.2 - 2021-10-21

### 📦 Added
- Added Pimple container for dependency injection, and new `/includes/Services.php` to manage the dependencies/services.
- New `/includes/functions.php` for loading global (namespaced) functions.
- Checks to make sure the Composer dependencies loaded before initializing the plugin.

### 👌 Changed
- Removed `/includes/PluginInfo.php` and static methods in favor of using `$this->plugin` methods (via [plugin awareness](https://github.com/cedaro/wp-plugin#plugin-awareness)).
- Moved `wp_localize_script()` call in the `Admin::load_assets` method to inside check a for the `/assets/admin.js` file.

### 🐛 Fixed
- The `gulp watch` command now watches root PHP files.

## v1.0.1 - 2021-10-07

### 🐛 Fixed
- Release workflow now properly builds and includes assets.

## v1.0.0 - 2021-10-01

