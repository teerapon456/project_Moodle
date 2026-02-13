<?php
// Modules/YearlyActivity/Views/Wizard/step5.php
// Uses Real API now
?>
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Milestones</h2>
        <p class="text-gray-500 text-sm">Define key checkpoints for this activity.</p>
    </div>

    <div class="flex justify-end mb-4">
        <button type="button" onclick="openMilestoneModal()"
            class="px-4 py-2 bg-red-50 text-primary rounded-lg hover:bg-red-100 transition font-medium flex items-center gap-2">
            <i class="ri-add-line"></i> Add Milestone
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                    <th class="px-6 py-3 font-semibold">Name</th>
                    <th class="px-6 py-3 font-semibold">Due Date</th>
                    <th class="px-6 py-3 font-semibold text-center">Weight %</th>
                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="milestone-list">
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                        Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Milestone Modal -->
<div id="milestone-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Add Milestone</h3>
            <button type="button" onclick="closeMilestoneModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Milestone Name</label>
                <input type="text" id="ms-name" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="ms-desc" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-primary"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date & Time</label>
                    <input type="datetime-local" id="ms-start-date" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date & Time</label>
                    <input type="datetime-local" id="ms-date" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-primary">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (%)</label>
                    <input type="number" id="ms-weight" min="0" max="100" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-primary">
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 rounded-b-2xl">
            <button type="button" onclick="closeMilestoneModal()" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-600">Cancel</button>
            <button type="button" onclick="saveMilestone()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Save</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const activityId = <?= json_encode($id) ?>;
    let currentMilestoneId = null;

    function loadMilestones() {
        if (!activityId) return;

        // Use the API we enabled in index.php
        fetch('?action=get_milestones&activity_id=' + activityId)
            .then(r => r.json())
            .then(data => renderMilestones(data))
            .catch(e => {
                console.error(e);
                document.getElementById('milestone-list').innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
            });
    }

    function renderMilestones(data) {
        const tbody = document.getElementById('milestone-list');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No milestones yet. Click "Add Milestone" to create one.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(ms => `
            <tr class="border-b border-gray-50 last:border-b-0 hover:bg-gray-50/50 transition">
                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${ms.name}</td>
                <td class="px-6 py-4 text-sm text-gray-500 text-center whitespace-nowrap">
                    ${ms.start_date ? ms.start_date.replace(' ', 'T').slice(0, 16).replace('T', ' ') : ''} ${ms.start_date ? ' to ' : ''} ${ms.due_date ? ms.due_date.replace(' ', 'T').slice(0, 16).replace('T', ' ') : '-'}
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">${ms.weight_percent}%</td>
                <td class="px-6 py-4 text-right flex justify-end gap-2">
                    <button type="button" onclick='editMilestone(${JSON.stringify(ms).replace(/'/g, "&#39;")})'
                        class="text-blue-500 hover:bg-red-50 p-1.5 rounded transition"><i class="ri-edit-line"></i></button>
                    <button type="button" onclick="deleteMilestone(${ms.id})"
                        class="text-red-500 hover:bg-red-50 p-1.5 rounded transition"><i class="ri-delete-bin-line"></i></button>
                </td>
            </tr>
        `).join('');
    }

    function openMilestoneModal(isEdit = false) {
        document.querySelector('#milestone-modal h3').textContent = isEdit ? 'Edit Milestone' : 'Add Milestone';
        document.getElementById('milestone-modal').classList.remove('hidden');
        document.getElementById('milestone-modal').classList.add('flex');
    }

    function closeMilestoneModal() {
        document.getElementById('milestone-modal').classList.add('hidden');
        document.getElementById('milestone-modal').classList.remove('flex');
        // Reset form
        document.getElementById('ms-name').value = '';
        document.getElementById('ms-desc').value = '';
        document.getElementById('ms-date').value = '';
        document.getElementById('ms-weight').value = '';
        currentMilestoneId = null;
    }

    function editMilestone(ms) {
        currentMilestoneId = ms.id;
        document.getElementById('ms-name').value = ms.name;
        document.getElementById('ms-desc').value = ms.description || '';
        document.getElementById('ms-start-date').value = ms.start_date || '';
        document.getElementById('ms-date').value = ms.due_date;
        document.getElementById('ms-weight').value = ms.weight_percent;
        openMilestoneModal(true);
    }

    function deleteMilestone(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('?action=remove_milestone', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            loadMilestones();
                            Swal.fire(
                                'Deleted!',
                                'Your milestone has been deleted.',
                                'success'
                            )
                        } else {
                            Swal.fire('Error', res.message || 'Error deleting', 'error');
                        }
                    });
            }
        })
    }

    function saveMilestone() {
        const payload = {
            action: currentMilestoneId ? 'update_milestone' : 'add_milestone',
            activity_id: activityId,
            name: document.getElementById('ms-name').value,
            description: document.getElementById('ms-desc').value,
            start_date: document.getElementById('ms-start-date').value,
            due_date: document.getElementById('ms-date').value,
            weight_percent: document.getElementById('ms-weight').value
        };

        if (currentMilestoneId) {
            payload.id = currentMilestoneId;
        }

        const formData = new FormData();
        for (const k in payload) {
            formData.append(k, payload[k]);
        }

        fetch('?action=' + payload.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeMilestoneModal();
                    loadMilestones();
                    Swal.fire('Saved!', 'Milestone saved successfully', 'success');
                } else {
                    Swal.fire('Error', res.message || 'Error saving', 'error');
                }
            });
    }

    // Init
    loadMilestones();
</script>