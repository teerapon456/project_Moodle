<?php
// Modules/YearlyActivity/Views/calendar_settings.php
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="?page=calendar&id=<?= $calendar['id'] ?>" class="p-2 text-gray-400 hover:text-primary hover:bg-white rounded-lg transition">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Calendar Settings</h1>
            <p class="text-gray-500 text-sm">Manage configuration and access for "<?= htmlspecialchars($calendar['name']) ?>"</p>
        </div>
    </div>

    <!-- General Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-50">General Information</h2>

        <form action="?action=update_calendar" method="POST" class="space-y-4 max-w-lg">
            <input type="hidden" name="id" value="<?= $calendar['id'] ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Calendar Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($calendar['name']) ?>" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <input type="number" name="year" value="<?= $calendar['year'] ?>" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= htmlspecialchars($calendar['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                    <option value="active" <?= ($calendar['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="archived" <?= ($calendar['status'] ?? 'active') === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="pt-2">
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-medium">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Members Management -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-50">
            <h2 class="text-lg font-bold text-gray-800">Members & Permissions</h2>
            <span class="text-xs bg-red-50 text-primary px-2 py-1 rounded-full"><?= count($members) + 1 // +1 for owner 
                                                                                        ?> users</span>
        </div>

        <!-- Add Member Form -->
        <!-- Add Member Form -->
        <form action="?action=add_member" method="POST" class="bg-gray-50 p-4 rounded-xl mb-6 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="calendar_id" value="<?= $calendar['id'] ?>">
            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">User Email</label>
                <input type="email" name="email" required placeholder="colleague@example.com"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Role</label>
                <select name="role" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="w-full px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-medium whitespace-nowrap flex justify-center items-center">
                    <i class="ri-user-add-line mr-2"></i> Add Member
                </button>
            </div>
        </form>

        <!-- Members List -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                        <th class="py-3 font-semibold">User</th>
                        <th class="py-3 font-semibold">Email</th>
                        <th class="py-3 font-semibold">Role</th>
                        <th class="py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <!-- Owner (Static) -->
                    <tr class="border-b border-gray-50 last:border-b-0">
                        <td class="py-3 font-medium text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-red-100 text-primary flex items-center justify-center text-xs font-bold">
                                <?= strtoupper(substr($calendar['owner_name'], 0, 2)) ?>
                            </span>
                            <?= htmlspecialchars($calendar['owner_name']) ?>
                        </td>
                        <td class="py-3 text-gray-500"><?= htmlspecialchars($calendar['owner_email']) ?></td>
                        <td class="py-3">
                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-bold uppercase">Owner</span>
                        </td>
                        <td class="py-3 text-right text-gray-400">
                            <i class="ri-lock-line"></i>
                        </td>
                    </tr>

                    <!-- Other Members -->
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-400 italic">No other members yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr class="border-b border-gray-50 last:border-b-0 group hover:bg-gray-50 transition">
                                <td class="py-3 font-medium text-gray-900 flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold">
                                        <?= strtoupper(substr($m['fullname'], 0, 2)) ?>
                                    </span>
                                    <?= htmlspecialchars($m['fullname']) ?>
                                </td>
                                <td class="py-3 text-gray-500"><?= htmlspecialchars($m['email']) ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-medium uppercase border border-gray-200">
                                        <?= $m['role'] ?>
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <?php if (($calendar['user_role'] ?? '') === 'owner'): ?>
                                        <form action="?action=remove_member" method="POST" onsubmit="return confirmRemoveMember(event, this);">
                                            <input type="hidden" name="calendar_id" value="<?= $calendar['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 p-2 hover:bg-red-50 rounded transition">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Danger Zone (Owner Only) -->
    <?php if (($calendar['user_role'] ?? '') === 'owner'): ?>
        <div class="bg-red-50 rounded-xl shadow-sm border border-red-100 p-6 mt-8">
            <h2 class="text-lg font-bold text-red-800 mb-2">Danger Zone</h2>
            <p class="text-red-600 text-sm mb-4">Deleting this calendar will permanently remove all associated activities, milestones, and data. This action cannot be undone.</p>

            <div class="flex justify-end">
                <a href="?action=delete_calendar&id=<?= $calendar['id'] ?>"
                    onclick="return confirmDeleteCalendar(event, this.href);"
                    class="px-5 py-2 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition font-medium flex items-center gap-2">
                    <i class="ri-delete-bin-line"></i> Delete Calendar
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function confirmRemoveMember(e, form) {
        e.preventDefault();
        Swal.fire({
            title: 'Remove this user?',
            text: "They will no longer have access to this calendar.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    function confirmDeleteCalendar(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: "This will permanently delete the calendar and all data! This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, DELETE IT'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
        return false;
    }
</script>