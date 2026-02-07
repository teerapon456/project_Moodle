<?php
// Modules/YearlyActivity/Views/Wizard/step6.php
?>
<div class="max-w-6xl mx-auto space-y-6">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">RASCI Matrix</h2>
        <p class="text-gray-500 text-sm">Assign responsibilities: <strong class="text-indigo-600">Click cells</strong> to toggle roles.</p>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap justify-center gap-4 text-sm mb-4">
        <div class="flex items-center gap-2"><span class="w-6 h-6 rounded bg-red-100 text-red-700 font-bold flex items-center justify-center text-xs">R</span> Responsible</div>
        <div class="flex items-center gap-2"><span class="w-6 h-6 rounded bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs">A</span> Accountable</div>
        <div class="flex items-center gap-2"><span class="w-6 h-6 rounded bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs">S</span> Support</div>
        <div class="flex items-center gap-2"><span class="w-6 h-6 rounded bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-xs">C</span> Consulted</div>
        <div class="flex items-center gap-2"><span class="w-6 h-6 rounded bg-gray-100 text-gray-700 font-bold flex items-center justify-center text-xs">I</span> Informed</div>
    </div>

    <div id="loading-indicator" class="text-center py-10 text-gray-400">
        <i class="ri-loader-4-line animate-spin text-3xl mb-2"></i>
        <p>Loading Matrix...</p>
    </div>

    <div id="matrix-container" class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm hidden">
        <!-- Matrix Table -->
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-xs">
                <tr>
                    <th class="p-4 text-left border-b border-gray-200 sticky left-0 bg-gray-50 z-10 w-64 shadow-sm">Team Member</th>
                    <!-- Milestones Headers will be injected here -->
                    <th id="header-end"></th>
                </tr>
            </thead>
            <tbody id="matrix-body" class="bg-white divide-y divide-gray-100">
                <!-- Rows injected here -->
            </tbody>
        </table>
    </div>
</div>

<script>
    const activityId = <?= json_encode($id) ?>;
    const calendarId = <?= json_encode($calendarId) ?>;

    let milestones = [];
    let members = [];
    let assignments = []; // Flat list from DB

    // Roles cycle
    const ROLES = ['', 'R', 'A', 'S', 'C', 'I'];
    const ROLE_COLORS = {
        '': 'bg-transparent',
        'R': 'bg-red-100 text-red-700',
        'A': 'bg-orange-100 text-orange-700',
        'S': 'bg-blue-100 text-blue-700',
        'C': 'bg-purple-100 text-purple-700',
        'I': 'bg-gray-100 text-gray-700'
    };

    // Initialize
    Promise.all([
        fetch('?action=get_milestones&activity_id=' + activityId).then(r => r.json()),
        fetch('?action=get_calendar_members&calendar_id=' + calendarId).then(r => r.json()),
        fetch('?action=get_all_rasci&activity_id=' + activityId).then(r => r.json())
    ]).then(([msData, memData, rasciData]) => {
        milestones = msData;
        members = memData;
        assignments = rasciData;
        renderMatrix();
    });

    function renderMatrix() {
        const theadRow = document.querySelector('thead tr');
        const endTh = document.getElementById('header-end');
        const tbody = document.getElementById('matrix-body');

        document.getElementById('loading-indicator').classList.add('hidden');
        document.getElementById('matrix-container').classList.remove('hidden');

        if (milestones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="p-8 text-center text-gray-400">No milestones found. Add them in Step 5.</td></tr>';
            return;
        }

        // 1. Build Headers
        // Clear old headers except first (Member)
        while (theadRow.children.length > 2) { // Keep Member and placeholder
            theadRow.removeChild(theadRow.lastChild);
        }
        // Remove old dynamic, adding new
        // Simpler: clear all except first
        theadRow.innerHTML = '<th class="p-4 text-left border-b border-gray-200 sticky left-0 bg-gray-50 z-10 w-64 shadow-sm border-r">Team Member</th>';

        milestones.forEach(ms => {
            const th = document.createElement('th');
            th.className = 'p-4 border-b border-gray-200 min-w-[120px] text-center border-r last:border-r-0';
            th.innerHTML = `<div class="truncate max-w-[120px]" title="${ms.name}">${ms.name}</div><div class="text-[10px] text-gray-400 font-normal">${ms.due_date || ''}</div>`;
            theadRow.appendChild(th);
        });

        // 2. Build Rows
        tbody.innerHTML = '';
        members.forEach(mem => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';

            // Name Cell
            const userId = mem.user_id || mem.id;
            const tdName = document.createElement('td');
            tdName.className = 'p-3 border-r border-gray-100 sticky left-0 bg-white z-10 font-medium text-gray-800 flex items-center gap-2';
            tdName.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                    ${mem.fullname.substring(0,2).toUpperCase()}
                </div>
                <div class="truncate w-40" title="${mem.fullname}">${mem.fullname}</div>
            `;
            tr.appendChild(tdName);

            // Milestone Cells
            milestones.forEach(ms => {
                const td = document.createElement('td');
                td.className = 'p-1 border-r border-gray-100 last:border-r-0 text-center cursor-pointer select-none transition-colors hover:bg-gray-100';

                // Check existing assignment
                const assignment = assignments.find(a => a.milestone_id == ms.id && a.user_id == userId);
                const currentRole = assignment ? assignment.role : '';
                const currentId = assignment ? assignment.id : null;

                td.innerHTML = renderCellContent(currentRole);
                td.onclick = () => toggleRole(td, ms.id, userId, currentRole, currentId);
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });
    }

    function renderCellContent(role) {
        if (!role) return '<div class="h-8 w-full flex items-center justify-center text-gray-300">-</div>';
        const color = ROLE_COLORS[role] || 'bg-gray-200';
        return `<div class="h-8 w-8 mx-auto rounded flex items-center justify-center font-bold text-sm ${color}">${role}</div>`;
    }

    function toggleRole(td, msId, userId, currentRole, currentId) {
        // Cycle Role
        let idx = ROLES.indexOf(currentRole);
        idx = (idx + 1) % ROLES.length; // Next role
        const newRole = ROLES[idx];

        // Optimistic Update
        td.innerHTML = '<i class="ri-loader-4-line animate-spin text-gray-400"></i>';

        // Disable click while saving
        const originalOnClick = td.onclick;
        td.onclick = null;

        // Perform Save/Delete
        if (newRole === '') {
            // Delete if existing
            if (currentId) {
                const fd = new FormData();
                fd.append('id', currentId);
                fetch('?action=remove_rasci', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            // Update local data
                            assignments = assignments.filter(a => a.id !== currentId);
                            td.innerHTML = renderCellContent('');
                            td.onclick = () => toggleRole(td, msId, userId, '', null);
                        }
                    });
            } else {
                // Nothing to delete
                td.innerHTML = renderCellContent('');
                td.onclick = originalOnClick;
            }
        } else {
            // Add (We simulate update by Add because schema is one user-role per milestone? 
            // Actually existing logic might allow multiple roles per user? 
            // Standard RASCI usually is one role, or multiple. 
            // My addRasci just inserts. I should delete old if exists first to be clean, or treating "Toggle" as replacement.
            // Let's assume replacement for simplicity of Matrix model (one role per cell).

            // If replacing, delete first? Or just allow multiple rows? 
            // If I want matrix behavior "Cell = Role", it implies 1 role.
            // Let's delete previous if exists (replace).

            const saveAction = () => {
                const fd = new FormData();
                fd.append('milestone_id', msId);
                fd.append('user_id', userId);
                fd.append('role', newRole);
                fetch('?action=add_rasci', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(res => {
                        // Update local
                        assignments.push({
                            id: res.id,
                            milestone_id: msId,
                            user_id: userId,
                            role: newRole
                        });
                        td.innerHTML = renderCellContent(newRole);
                        td.onclick = () => toggleRole(td, msId, userId, newRole, res.id);
                    });
            };

            if (currentId) {
                // Remove old first
                const fdDel = new FormData();
                fdDel.append('id', currentId);
                fetch('?action=remove_rasci', {
                        method: 'POST',
                        body: fdDel
                    })
                    .then(() => {
                        assignments = assignments.filter(a => a.id !== currentId);
                        saveAction();
                    });
            } else {
                saveAction();
            }
        }
    }
</script>