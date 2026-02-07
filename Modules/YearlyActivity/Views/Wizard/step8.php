<?php
// Modules/YearlyActivity/Views/Wizard/step8.php
?>
<div class="max-w-5xl mx-auto space-y-6">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Risk Assessment</h2>
        <p class="text-gray-500 text-sm">Identify potential risks and mitigation plans.</p>
    </div>

    <!-- Risk Matrix Legend -->
    <div class="grid grid-cols-3 gap-4 mb-6 text-center text-sm">
        <div class="bg-green-50 text-green-700 p-2 rounded-lg border border-green-100">Low Risk (Score 1-3)</div>
        <div class="bg-yellow-50 text-yellow-700 p-2 rounded-lg border border-yellow-100">Medium Risk (Score 4-9)</div>
        <div class="bg-red-50 text-red-700 p-2 rounded-lg border border-red-100">High Risk (Score 10-25)</div>
    </div>

    <div id="loading-indicator" class="text-center py-10 text-gray-400">
        <i class="ri-loader-4-line animate-spin text-3xl mb-2"></i>
        <p>Loading Data...</p>
    </div>

    <div id="risk-container" class="space-y-6 hidden">
        <!-- Filled by JS -->
    </div>
</div>

<!-- Add Risk Modal -->
<div id="risk-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Log Risk</h3>
            <p class="text-xs text-gray-400" id="modal-ms-name">Milestone: ...</p>
        </div>
        <div class="p-5 space-y-4">
            <input type="hidden" id="risk-ms-id">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Risk Description</label>
                <textarea id="risk-desc" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500" placeholder="What could go wrong?"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Impact (1-5)</label>
                    <select id="risk-impact" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500 bg-white">
                        <option value="1">1 - Negligible</option>
                        <option value="2">2 - Minor</option>
                        <option value="3">3 - Moderate</option>
                        <option value="4">4 - Major</option>
                        <option value="5">5 - Catastrophic</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Probability (1-5)</label>
                    <select id="risk-prob" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500 bg-white">
                        <option value="1">1 - Rare</option>
                        <option value="2">2 - Unlikely</option>
                        <option value="3">3 - Possible</option>
                        <option value="4">4 - Likely</option>
                        <option value="5">5 - Certain</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mitigation Plan</label>
                <textarea id="risk-plan" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500" placeholder="How will we prevent or handle this?"></textarea>
            </div>
        </div>
        <div class="p-4 bg-gray-50 rounded-b-2xl flex justify-end gap-2">
            <button type="button" onclick="closeRiskModal()" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
            <button type="button" onclick="saveRisk()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Risk</button>
        </div>
    </div>
</div>

<script>
    const activityId = <?= json_encode($id) ?>;
    let milestones = [];

    // Init
    fetch('?action=get_milestones&activity_id=' + activityId)
        .then(r => r.json())
        .then(data => {
            milestones = data;
            renderRisks();
            document.getElementById('loading-indicator').classList.add('hidden');
            document.getElementById('risk-container').classList.remove('hidden');
        });

    async function renderRisks() {
        const container = document.getElementById('risk-container');
        container.innerHTML = '';

        if (milestones.length === 0) {
            container.innerHTML = `
                <div class="text-center p-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-400">
                    <i class="ri-flag-line text-3xl mb-2"></i>
                    <p>No milestones found. Please go back to Step 5.</p>
                </div>`;
            return;
        }

        for (const ms of milestones) {
            // Create Card
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden';

            // Fetch risks
            const risks = await fetch('?action=get_risks&milestone_id=' + ms.id).then(r => r.json());

            const rows = risks.map(r => {
                const score = r.impact * r.probability;
                let colorClass = 'bg-green-100 text-green-700';
                if (score >= 10) colorClass = 'bg-red-100 text-red-700';
                else if (score >= 4) colorClass = 'bg-yellow-100 text-yellow-700';

                return `
                <div class="p-4 border-b border-gray-50 last:border-b-0 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start mb-2">
                         <div class="font-bold text-gray-800">${r.risk_description}</div>
                         <div class="flex items-center gap-2">
                             <span class="px-2 py-1 rounded text-xs font-bold ${colorClass}">Score: ${score}</span>
                             <button type="button" onclick="removeRisk(${r.id})" class="text-gray-400 hover:text-red-500"><i class="ri-delete-bin-line"></i></button>
                         </div>
                    </div>
                    <div class="text-sm text-gray-600 mb-1">
                        <span class="font-semibold text-gray-500 text-xs uppercase">Mitigation:</span> ${r.mitigation_plan || '-'}
                    </div>
                </div>
                `;
            }).join('');

            card.innerHTML = `
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800">${ms.name}</h3>
                        <p class="text-xs text-gray-500">Log any risks associated with this milestone.</p>
                    </div>
                    <button type="button" onclick="openRiskModal(${ms.id}, '${ms.name}')" 
                        class="text-indigo-600 hover:bg-indigo-100 p-2 rounded-lg transition text-sm font-medium">
                        <i class="ri-shield-flash-line mr-1"></i> Log Risk
                    </button>
                </div>
                <div class="">
                    ${rows || '<div class="p-4 text-center text-gray-400 italic text-sm">No risks identified.</div>'}
                </div>
            `;
            container.appendChild(card);
        }
    }

    // Modal Logic
    let currentMsId = null;

    function openRiskModal(msId, msName) {
        currentMsId = msId;
        document.getElementById('modal-ms-name').textContent = 'Milestone: ' + msName;
        document.getElementById('risk-ms-id').value = msId;
        document.getElementById('risk-desc').value = '';
        document.getElementById('risk-plan').value = '';
        document.getElementById('risk-impact').value = 3;
        document.getElementById('risk-prob').value = 3;

        const modal = document.getElementById('risk-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRiskModal() {
        const modal = document.getElementById('risk-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function saveRisk() {
        const desc = document.getElementById('risk-desc').value;
        if (!desc) return alert("Please enter description");

        const formData = new FormData();
        formData.append('action', 'add_risk');
        formData.append('milestone_id', currentMsId);
        formData.append('risk_description', desc);
        formData.append('impact', document.getElementById('risk-impact').value);
        formData.append('probability', document.getElementById('risk-prob').value);
        formData.append('mitigation_plan', document.getElementById('risk-plan').value);

        fetch('?action=add_risk', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeRiskModal();
                    renderRisks();
                }
            });
    }

    function removeRisk(id) {
        Swal.fire({
            title: 'Delete this risk?',
            text: "Do you want to delete this risk entry?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('?action=remove_risk', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            renderRisks();
                            Swal.fire(
                                'Deleted!',
                                'Risk has been deleted.',
                                'success'
                            )
                        }
                    });
            }
        });
    }
</script>