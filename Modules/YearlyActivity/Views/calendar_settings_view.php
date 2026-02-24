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
        <form action="?action=add_member" method="POST" id="addMemberForm" class="bg-gray-50 p-4 rounded-xl mb-6 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="calendar_id" value="<?= $calendar['id'] ?>">
            <input type="hidden" name="user_id" id="memberUserId" value="">
            <input type="hidden" name="email" id="memberEmail" value="">

            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search User <span class="text-red-500">*</span></label>

                <div id="selectedMember" class="hidden flex items-center gap-3 px-3 py-2 bg-white border border-gray-200 rounded-lg">
                    <i class="ri-user-line text-primary"></i>
                    <span id="memberDisplayName" class="text-sm font-medium"></span>
                    <button type="button" class="ml-auto text-gray-400 hover:text-red-500" onclick="clearMember()">&times;</button>
                </div>

                <div class="relative" id="memberSearchContainer">
                    <input type="text" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none" id="memberSearch" placeholder="Search by name or email..." autocomplete="off" oninput="searchMember(this.value)">
                    <div id="memberResults" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
                </div>
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" id="memberRole" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <button type="button" onclick="submitAddMember()" class="w-full px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-medium whitespace-nowrap flex justify-center items-center">
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

<script>
    <?php
    $memberEmails = [];
    if (!empty($calendar['owner_email'])) {
        $memberEmails[] = $calendar['owner_email'];
    }
    foreach ($members as $m) {
        $memberEmails[] = $m['email'];
    }
    ?>
    const existingMemberEmails = <?= json_encode($memberEmails) ?>;

    let selectedMember = null;
    let searchTimeout;

    async function searchMember(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('memberResults');
        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`?action=search_employee&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && Array.isArray(data.users) && data.users.length > 0) {
                    resultsDiv.innerHTML = data.users.map(emp => {
                        const isAdded = existingMemberEmails.includes(emp.email);
                        const disabledClass = isAdded ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'hover:bg-gray-50 cursor-pointer';
                        const clickHandler = isAdded ? '' : `onclick='selectMember(${JSON.stringify(emp)})'`;
                        const statusBadge = isAdded ? '<span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-600 font-bold ml-2">Added</span>' : '';

                        return `
                        <div class="flex items-center gap-3 p-3 border-b border-gray-100 last:border-b-0 ${disabledClass}" ${clickHandler}>
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-red-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${emp.name || emp.email} ${statusBadge}</div>
                                <div class="text-xs text-gray-500">${emp.email}</div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                        </div>
                        `;
                    }).join('');
                    resultsDiv.classList.remove('hidden');
                } else {
                    resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">No member found</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function selectMember(emp) {
        selectedMember = emp;
        document.getElementById('memberUserId').value = emp.id || '';
        document.getElementById('memberEmail').value = emp.email || '';
        document.getElementById('memberDisplayName').textContent = `${emp.name || emp.email} (${emp.email})`;

        document.getElementById('selectedMember').classList.remove('hidden');
        document.getElementById('selectedMember').classList.add('flex');
        document.getElementById('memberSearchContainer').classList.add('hidden');
        document.getElementById('memberResults').classList.add('hidden');
    }

    function clearMember() {
        selectedMember = null;
        document.getElementById('memberUserId').value = '';
        document.getElementById('memberEmail').value = '';
        document.getElementById('memberSearch').value = '';

        document.getElementById('selectedMember').classList.add('hidden');
        document.getElementById('selectedMember').classList.remove('flex');
        document.getElementById('memberSearchContainer').classList.remove('hidden');
    }

    function submitAddMember() {
        if (!document.getElementById('memberEmail').value) {
            Swal.fire('Error', 'Please select a user to add as a member.', 'error');
            return;
        }
        document.getElementById('addMemberForm').submit();
    }

    // Close search results when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#memberSearchContainer')) {
            document.getElementById('memberResults')?.classList.add('hidden');
        }
    });
</script>