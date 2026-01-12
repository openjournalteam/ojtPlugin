### 3.1.1.3 : 12 Jan 2026
- Add Feature report to service panel for plugin registered
- Fixed Fatal Handler when error is null

### 3.1.1.2 : 10 Jan 2026
- Fixed when fatal error plugin not removed

### 3.1.1.1 : 07 Jan 2026
- Align registered plugin modules with global requirements in the Control Panel

### 3.1.1.0 : 19 Dec 2025
- Added new compatibility for plugin with svelte

### 3.1.0.5 : 01 Dec 2025
- Fixed compatibility issue caused by the removal of `UserUserGroupStatus` scope in OJS 3.5.0-2.
- Fixed issue support not redirecting properly for OJS 3.5.x.x

### 3.1.0.4 : 20 Nov 2025
- Changed flow installation process to direct to temp/.staging folder

### 3.1.0.3 : 12 Nov 2025
- Fixed issue with site plugins not being moved to the global plugins directory
- Fixed issue with delete plugin for site-wide plugins
- Fixed issue with HTTP validation

### 3.1.0.2 : 13 Oct 2025
- Added a new Hook for global plugins can register itself to the OJT Control Panel page.

### 3.1.0.1 : 03 Oct 2025
- Add getUsage method for usage tracking

### 3.1.0.0 : 06 Sep 2025
- Compatibility with OJS 3.5.x.x
- Improved Plugin Installed UI with action column and documentation link (based on plugin metadata)

### 3.0.2.1 : 22 April 2025
- Improved enabled/disabled on installed plugins
- Allow only admin can disable/enable plugins on certain plugin

### 3.0.2.0 : 13 Mar 2024
- Fix showing notice log when there's an error

### 3.0.1.1 : 21 Nov 2023
- Fix error when installing plugin because ojs root folder is restricted to write

### 3.0.1.0 : 21 Oct 2023
- Integration with OJT Support platform

### 3.0.0.4 : 05 Sep 2023
- Fix showing log by PluginRegistry on Modules Plugin 

### 3.0.0.0 : 17 Juni 2023

- Support for OJS 3.4.x.x
- Removed support for OJS 3.3.x.x and below

### 2.0.6.0 : 15 Juli 2023

- Fix logging error by OJTPlugin make error reporting not working
- Replace download plugin implementation using GuzzleHttp because in some server, file_get_contents is not working

### 2.0.5.0 : 15 Juni 2023

- Change color theme in Setting Page
- New API Subscription Service
- Fix bug side bar navigation not active on the right plugin

### 2.0.4.0 : 17 Mei 2023

- Add modules dependencies

### 2.0.3.0 : 14 April 2023

- Add control panel query string tab
- Add toggleMainMenu

### 2.0.2.0 : 6 Februari 2023

- Add user preferences
- Now the license that has been input will appear when installing the plugin

### 2.0.1.0 : 13 Januari 2023

- Support for OJS 3.1

### 2.0.0.0 : 12 Januari 2023

- New User Interface.
- Now support themes
- Uninstall directly from Plugin Installed
- Improve Plugin Installed
- Improve Plugin Gallery
- Give Feedback or Report Bug directly from OJTPlugin

### 1.2.2.0 : 7 Juli 2022

- Add new API for updating modules

### 1.2.1.0 : 16 Juni 2022

- New Feature: Update OJTPlugin directly from OJTPanel

### 1.2.0.2 : 24 Maret 2022

- Support OJS 3.1

### 1.2.0.1 : 1 Maret 2022

- Fix license is gone when module updated

### 1.2 : 17 Februari 2022

- Remove when plugin folder notfound on database

### 1.1 : 12 Januari 2022

- Add about Us
- Fix menu display
- Fix menu on mobile
- Add reset setting for selected plugin

### 1.0.0 : 30 Mei 2021

- First Release
