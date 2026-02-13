<?php
// Modules/YearlyActivity/Views/Wizard/step4.php
?>
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Location</h2>
        <p class="text-gray-500 text-sm">Where will it happen?</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Location / Venue</label>
        <div class="relative">
            <i class="ri-map-pin-line absolute left-4 top-3.5 text-gray-400"></i>
            <input type="text" name="location" value="<?= htmlspecialchars($data['location'] ?? '') ?>"
                class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"
                placeholder="e.g. Conference Room A, Zoom Meeting, Bangkok HQ">
        </div>
    </div>

    <!-- Placeholder for Map Integration in future -->
    <div class="h-40 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 flex flex-col items-center justify-center text-gray-400">
        <i class="ri-map-2-line text-3xl mb-2"></i>
        <span class="text-sm">Map preview unavailable</span>
    </div>
</div>