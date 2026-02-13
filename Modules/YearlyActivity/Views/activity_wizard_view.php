<?php
// Modules/YearlyActivity/Views/activity_wizard.php
// Variables: $step, $id, $calendarId, $data

$steps = [
    1 => 'General Info',
    2 => 'Details',
    3 => 'Dates',
    4 => 'Location',
    5 => 'Milestones',
    6 => 'RASCI',
    7 => 'Resources',
    8 => 'Risks'
];

$progress = ($step / count($steps)) * 100;
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Wizard Header -->
<div class="mb-4 sm:mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0 mb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                <?= $id ? 'Edit Activity' : 'Create New Activity' ?>
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                Step <?= $step ?> of <?= count($steps) ?>: <span class="text-primary font-semibold"><?= $steps[$step] ?></span>
            </p>
        </div>
        <a href="?page=calendar&id=<?= $calendarId ?>" class="px-4 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition text-sm">
            Cancel & Exit
        </a>
    </div>

    <!-- Progress Bar -->
    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-primary to-primary-light transition-all duration-500" style="width: <?= $progress ?>%"></div>
    </div>
</div>

<!-- Step Content -->
<div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 overflow-hidden wizard-step">
    <form action="?action=save_wizard" method="POST" id="wizard-form">
        <input type="hidden" name="step" value="<?= $step ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="calendar_id" value="<?= $calendarId ?>">
        <input type="hidden" name="action" value="save_wizard">

        <div class="p-4 sm:p-8">
            <?php
            $stepFile = __DIR__ . "/Wizard/step{$step}.php";
            if (file_exists($stepFile)) {
                include $stepFile;
            } else {
                echo "<p class='text-red-500'>Wizard Step $step file not found.</p>";
            }
            ?>
        </div>

        <div class="p-4 sm:p-6 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3">
            <?php if ($step > 1): ?>
                <a href="?page=activity_wizard&step=<?= $step - 1 ?>&id=<?= $id ?>&calendar_id=<?= $calendarId ?>"
                    class="px-6 py-2.5 text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition font-medium shadow-sm">
                    <i class="ri-arrow-left-line mr-1"></i> Back
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <div class="flex gap-3">
                <!-- Save Logic -->
                <?php if ($id): ?>
                    <!-- Editing: Allow Save & Exit at any step -->
                    <button type="submit" name="action_type" value="save_exit"
                        class="px-6 py-2.5 bg-white text-primary border border-red-200 rounded-xl hover:bg-red-50 transition font-medium">
                        <i class="ri-save-line mr-1"></i> Save Changes
                    </button>
                <?php elseif ($step == 1): ?>
                    <!-- Creating (Step 1): Allow Save as Draft -->
                    <button type="submit" name="action_type" value="save_draft"
                        class="px-6 py-2.5 bg-white text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition font-medium">
                        <i class="ri-draft-line mr-1"></i> Save as Draft
                    </button>
                <?php endif; ?>

                <button type="submit" name="action_type" value="next"
                    class="px-8 py-2.5 bg-primary text-white rounded-xl hover:bg-primary-dark transition font-medium shadow-md shadow-red-200 flex items-center gap-2">
                    <?= $step < count($steps) ? 'Next Step' : 'Finish Activity' ?>
                    <i class="ri-arrow-right-line"></i>
                </button>
            </div>
        </div>
    </form>
</div>