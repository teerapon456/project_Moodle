<?php
// Modules/YearlyActivity/Views/settings.php

require_once __DIR__ . '/../Controllers/CalendarSyncController.php';
// Check current sync status
$syncController = new CalendarSyncController();
$isSyncConnected = $syncController->checkConnectionStatus(); // Assumes this method exists or we check token
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <i class="ri-settings-3-line text-indigo-600"></i>
            Module Settings
        </h1>
        <p class="text-gray-500 mt-1">Configure your yearly activity preferences and integrations.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Sidebar Navigation (future proofing) -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <nav class="space-y-1">
                    <a href="#" class="block px-4 py-3 rounded-xl bg-indigo-50 text-indigo-700 font-medium flex items-center gap-3">
                        <i class="ri-refresh-line"></i> Check Status
                    </a>
                    <a href="#" class="block px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50 font-medium flex items-center gap-3">
                        <i class="ri-palette-line"></i> Appearance
                        <span class="ml-auto text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Coming Soon</span>
                    </a>
                    <a href="#" class="block px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50 font-medium flex items-center gap-3">
                        <i class="ri-notification-badge-line"></i> Notifications
                        <span class="ml-auto text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Coming Soon</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="md:col-span-2 space-y-6">

            <!-- Calendar Integration Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="ri-microsoft-fill text-blue-600"></i> Calendar Integration
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Sync your activities with Microsoft Outlook Calendar.</p>
                </div>

                <div class="p-8">
                    <?php if ($isSyncConnected): ?>
                        <!-- Connected State -->
                        <div class="flex items-center gap-4 mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="ri-checkbox-circle-fill text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold">Connected to Microsoft Graph</h4>
                                <p class="text-sm opacity-80">Your calendar is currently syncing automatically.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <a href="?action=calendar_sync" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition flex items-center gap-2">
                                <i class="ri-refresh-line"></i> Sync Now
                            </a>
                            <a href="?action=calendar_disconnect" class="px-6 py-2.5 bg-white border border-gray-200 text-red-600 rounded-lg font-medium hover:bg-red-50 transition flex items-center gap-2" onclick="return confirm('Are you sure you want to disconnect?');">
                                <i class="ri-shut-down-line"></i> Disconnect
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- Disconnected State -->
                        <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 text-gray-600 rounded-xl border border-gray-200">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                <i class="ri-error-warning-fill text-xl text-gray-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold">Not Connected</h4>
                                <p class="text-sm opacity-80">Connect your Microsoft account to enable calendar syncing.</p>
                            </div>
                        </div>

                        <!-- Since the actual connection flow involves AuthController redirection which might be separate, 
                             we'll show a generic connect button or instructions. 
                             Assuming there is a route or logic for initial OAuth connection similar to 'login with microsoft' but for this module scope.
                             For now, we link to the standard auth endpoint or a module specific one if it exists. 
                             Based on index.php, there isn't a direct 'connect' action, so this might need the Microsoft Auth flow link. 
                             Let's assume there is a global route /auth/login or similar, but for specific calendar scope it requires 'CalendarSyncController->connect()'.
                             Let's check if CalendarSyncController has a connect method or purely relies on existing token.
                             If it relies on user login token, then 'Sync Now' might just work if logged in with MS.
                             If it needs explicit authorization, we might need a 'connect' action. 
                             Let's add a `ics_all` export as a fallback sync method manually.
                        -->

                        <div class="space-y-4">
                            <p class="text-sm text-gray-500">
                                Note: To enable 2-way sync, your system administrator needs to configure the Microsoft Graph capabilities.
                                Alternatively, you can export your activities to an ICS file.
                            </p>

                            <div class="flex flex-wrap gap-4">
                                <a href="?action=calendar_connect&provider=outlook" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2">
                                    <i class="ri-microsoft-fill"></i> Connect Microsoft Account
                                </a>
                                <a href="?action=ics_all" class="px-6 py-2.5 bg-white border border-gray-200 text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition flex items-center gap-2">
                                    <i class="ri-download-line"></i> Download .ICS File
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- General Settings -->
            <?php
            require_once __DIR__ . '/../Controllers/SettingsController.php';
            $settingsCtrl = new SettingsController();
            $mySettings = $settingsCtrl->getSettings();
            $emailNotif = ($mySettings['email_notifications'] ?? '0') === '1';
            $compactView = ($mySettings['compact_view'] ?? '0') === '1';
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="ri-toggle-line text-purple-600"></i> General Preferences
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Email Notifications -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-800">Email Notifications</h4>
                                <p class="text-xs text-gray-500">Receive email updates for activity changes.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer setting-toggle" data-key="email_notifications" <?php echo $emailNotif ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Compact View -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-800">Compact View</h4>
                                <p class="text-xs text-gray-500">Show more items per page on the dashboard.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer setting-toggle" data-key="compact_view" <?php echo $compactView ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Default Start Page -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-800">Default Start Page</h4>
                                <p class="text-xs text-gray-500">Choose which page to show when opening this module.</p>
                            </div>
                            <select class="setting-select form-select block w-40 rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-sm" data-key="start_page">
                                <option value="dashboard" <?php echo ($mySettings['start_page'] ?? 'dashboard') === 'dashboard' ? 'selected' : ''; ?>>Dashboard</option>
                                <option value="my_calendars" <?php echo ($mySettings['start_page'] ?? '') === 'my_calendars' ? 'selected' : ''; ?>>My Calendars</option>
                            </select>
                        </div>

                        <!-- Default Calendar Tab -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-800">Default Calendar Tab</h4>
                                <p class="text-xs text-gray-500">Select the default view when opening a calendar.</p>
                            </div>
                            <select class="setting-select form-select block w-40 rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-sm" data-key="default_tab">
                                <option value="list" <?php echo ($mySettings['default_tab'] ?? 'list') === 'list' ? 'selected' : ''; ?>>List View</option>
                                <option value="timeline" <?php echo ($mySettings['default_tab'] ?? '') === 'timeline' ? 'selected' : ''; ?>>Timeline</option>
                                <option value="rasci" <?php echo ($mySettings['default_tab'] ?? '') === 'rasci' ? 'selected' : ''; ?>>RASCI Matrix</option>
                                <option value="risks" <?php echo ($mySettings['default_tab'] ?? '') === 'risks' ? 'selected' : ''; ?>>Risks</option>
                            </select>
                        </div>

                        <!-- Dashboard Items -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-800">Dashboard Items</h4>
                                <p class="text-xs text-gray-500">Number of upcoming activities to show.</p>
                            </div>
                            <select class="setting-select form-select block w-40 rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-sm" data-key="dashboard_limit">
                                <option value="5" <?php echo ($mySettings['dashboard_limit'] ?? '5') === '5' ? 'selected' : ''; ?>>5 Items</option>
                                <option value="10" <?php echo ($mySettings['dashboard_limit'] ?? '') === '10' ? 'selected' : ''; ?>>10 Items</option>
                                <option value="20" <?php echo ($mySettings['dashboard_limit'] ?? '') === '20' ? 'selected' : ''; ?>>20 Items</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                /**
                 * Unified function to save settings via AJAX
                 */
                function saveSetting(key, value) {
                    fetch('?action=save_setting', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `key=${key}&value=${value}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Saved setting:', key);
                            } else {
                                alert('Failed to save setting: ' + key);
                            }
                        })
                        .catch(err => {
                            console.error('Error saving setting:', err);
                        });
                }

                // Attach listener to Toggles (Checkboxes)
                document.querySelectorAll('.setting-toggle').forEach(toggle => {
                    toggle.addEventListener('change', function() {
                        const key = this.dataset.key;
                        const value = this.checked ? '1' : '0';
                        saveSetting(key, value);
                    });
                });

                // Attach listener to Select (Dropdowns)
                document.querySelectorAll('.setting-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const key = this.dataset.key;
                        const value = this.value;
                        saveSetting(key, value);
                    });
                });
            </script>

        </div>
    </div>
</div>