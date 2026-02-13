<?php
// Modules/YearlyActivity/Views/Wizard/step1.php
?>
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">General Information</h2>
        <p class="text-gray-500 text-sm">Let's start with the basics of your activity.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Activity Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"
            placeholder="e.g. Annual Strategic Planning">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
            <select name="type" required
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-white">
                <option value="">Select Type</option>
                <?php
                $types = ['Event', 'Meeting', 'Deadline', 'Milestone', 'Review', 'Training', 'Workshop'];
                foreach ($types as $t):
                    $selected = ($data['type'] ?? '') === $t ? 'selected' : '';
                ?>
                    <option value="<?= $t ?>" <?= $selected ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Key Person (Overall Responsibility)</label>
            <select name="key_person_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-white">
                <option value="">-- Select Key Person --</option>
                <?php if (!empty($calMembers)): foreach ($calMembers as $mem): ?>
                        <option value="<?= $mem['user_id'] ?>" <?= ($data['key_person_id'] ?? '') == $mem['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mem['fullname']) ?>
                        </option>
                <?php endforeach;
                endif; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">Person primarily responsible for this activity.</p>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Objective <span class="text-red-500">*</span></label>
        <textarea name="objective" rows="4" required
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition resize-none"
            placeholder="What is the main goal of this activity?"><?= htmlspecialchars($data['objective'] ?? '') ?></textarea>
        <p class="text-xs text-gray-400 mt-1 text-right">Briefly describe the purpose.</p>
    </div>
</div>