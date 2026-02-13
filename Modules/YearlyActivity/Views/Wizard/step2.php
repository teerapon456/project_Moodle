<?php
// Modules/YearlyActivity/Views/Wizard/step2.php
?>
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Activity Details</h2>
        <p class="text-gray-500 text-sm">Provide more context about this activity.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
        <textarea name="description" rows="6" required
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
            placeholder="Detailed description of the activity..."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
        <textarea name="scope" rows="4"
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
            placeholder="Define the boundaries of this activity..."><?= htmlspecialchars($data['scope'] ?? '') ?></textarea>
        <p class="text-xs text-gray-400 mt-1">Optional capabilities or limits.</p>
    </div>
</div>