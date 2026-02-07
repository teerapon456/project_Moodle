<?php
// Modules/YearlyActivity/Views/Wizard/step3.php
?>
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Timeframe</h2>
        <p class="text-gray-500 text-sm">When will this activity take place?</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="date" name="start_date" value="<?= !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : '' ?>" required
                    class="w-full pl-4 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="date" name="end_date" value="<?= !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : '' ?>" required
                    class="w-full pl-4 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>
        </div>
    </div>

    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex gap-3 text-blue-700 text-sm">
        <i class="ri-information-line text-lg"></i>
        <p>The duration between these dates will determine the timeline view in the calendar.</p>
    </div>
</div>