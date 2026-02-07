<?php
require_once __DIR__ . '/../Controllers/CalendarController.php';

$controller = new CalendarController();
$calendars = $controller->getUserCalendars();
?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">My Calendars</h1>
        <p class="text-gray-500 text-sm mt-1">Manage your yearly activity plans</p>
    </div>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')"
        class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl hover:opacity-95 transition-all flex items-center gap-2 transform active:scale-95">
        <i class="ri-add-line text-lg"></i>
        <span class="font-medium">New Calendar</span>
    </button>
</div>

<!-- Calendar Grid -->
<?php if (empty($calendars)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-calendar-line text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">No Calendars Yet</h3>
        <p class="text-gray-500 mb-6">Create your first yearly activity calendar to get started.</p>
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')"
            class="px-6 py-2 bg-white border-2 border-indigo-100 text-indigo-600 rounded-xl hover:border-indigo-600 hover:bg-indigo-50 transition font-medium">
            Create Calendar
        </button>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($calendars as $cal): ?>
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all relative overflow-hidden">
                <!-- Decorative Circle -->
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-full blur-2xl group-hover:bg-indigo-100 transition"></div>

                <div class="relative">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                            <?= $cal['year'] ?>
                        </div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-lg capitalize">
                            <?= $cal['user_role'] ?>
                        </span>
                        <!-- Status Badge -->
                        <?php
                        $status = $cal['status'] ?? 'active';
                        $statusColor = $status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';
                        ?>
                        <span class="px-2 py-1 <?= $statusColor ?> text-xs rounded-lg uppercase font-bold ml-2">
                            <?= $status ?>
                        </span>
                    </div>

                    <a href="?page=calendar&id=<?= $cal['id'] ?>" class="block group-hover:text-indigo-600 transition">
                        <h3 class="text-lg font-bold text-gray-800 mb-1 truncate"><?= htmlspecialchars($cal['name']) ?></h3>
                    </a>

                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                        <span class="flex items-center gap-1">
                            <i class="ri-user-smile-line"></i>
                            <?= $cal['member_count'] ?? 1 ?> Members
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="ri-task-line"></i>
                            <?= $cal['activity_count'] ?? 0 ?> Activities
                        </span>
                    </div>

                    <a href="?page=calendar&id=<?= $cal['id'] ?>"
                        class="w-full py-2.5 flex items-center justify-center gap-2 bg-gray-50 text-gray-600 rounded-xl hover:bg-indigo-600 hover:text-white transition font-medium">
                        <span>View Calendar</span>
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Modal -->
<div id="create-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl scale-100 transition-transform">
        <form method="POST" action="?page=my_calendars&action=create_calendar">

            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">New Calendar</h3>
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Calendar Name</label>
                    <input type="text" name="name" required placeholder="e.g. Activity Plan 2026"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" required min="2000" max="2100"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                    class="px-5 py-2.5 text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition font-medium shadow-sm">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-medium shadow-md shadow-indigo-200">
                    Create Calendar
                </button>
            </div>
        </form>
    </div>
</div>