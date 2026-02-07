<?php
// Modules/YearlyActivity/Views/Wizard/step7.php
?>
<div class="max-w-5xl mx-auto space-y-6">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold text-gray-800">Resource Planning</h2>
        <p class="text-gray-500 text-sm">Estimate resources and costs for each milestone.</p>
    </div>

    <!-- Summary Banner -->
    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex justify-between items-center px-8">
        <div>
            <span class="text-indigo-600 text-sm font-bold uppercase tracking-wide">Total Estimated Cost</span>
        </div>
        <div>
            <span class="text-2xl font-bold text-indigo-700" id="grand-total">฿0.00</span>
        </div>
    </div>

    <div id="loading-indicator" class="text-center py-10 text-gray-400">
        <i class="ri-loader-4-line animate-spin text-3xl mb-2"></i>
        <p>Loading Data...</p>
    </div>

    <div id="resource-container" class="space-y-6 hidden">
        <!-- Filled by JS -->
    </div>
</div>

<!-- Add Resource Modal -->
<div id="resource-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Add Resource</h3>
            <p class="text-xs text-gray-400" id="modal-ms-name">Milestone: ...</p>
        </div>
        <div class="p-5 space-y-4">
            <input type="hidden" id="res-ms-id">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Resource Name</label>
                <input type="text" id="res-name" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500" placeholder="e.g. Server Hosting, Venue Rental">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Quantity</label>
                    <input type="number" id="res-qty" value="1" min="1" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Unit</label>
                    <input type="text" id="res-unit" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500" placeholder="e.g. Day, Hours, Pcs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Unit Cost (THB)</label>
                <input type="number" id="res-cost" value="0" min="0" step="0.01" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:border-indigo-500">
            </div>
        </div>
        <div class="p-4 bg-gray-50 rounded-b-2xl flex justify-end gap-2">
            <button type="button" onclick="closeResourceModal()" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
            <button type="button" onclick="saveResource()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Resource</button>
        </div>
    </div>
</div>

<script>
    const activityId = <?= json_encode($id) ?>;
    let milestones = [];
    let grandTotal = 0;

    // Init
    fetch('?action=get_milestones&activity_id=' + activityId)
        .then(r => r.json())
        .then(data => {
            milestones = data;
            renderResources();
            document.getElementById('loading-indicator').classList.add('hidden');
            document.getElementById('resource-container').classList.remove('hidden');
        });

    async function renderResources() {
        const container = document.getElementById('resource-container');
        container.innerHTML = '';
        grandTotal = 0;

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

            // Fetch resources
            const resources = await fetch('?action=get_resources&milestone_id=' + ms.id).then(r => r.json());

            let msTotal = 0;
            const rows = resources.map(r => {
                const total = r.quantity * r.unit_cost;
                msTotal += total;
                return `
                <tr class="border-b border-gray-50 last:border-b-0 hover:bg-gray-50 transition">
                    <td class="py-3 pl-4 text-gray-800 font-medium">${r.resource_name}</td>
                    <td class="py-3 text-center text-gray-600">${r.quantity} ${r.unit}</td>
                    <td class="py-3 text-right text-gray-600">฿${parseFloat(r.unit_cost).toLocaleString()}</td>
                    <td class="py-3 text-right font-bold text-gray-800">฿${total.toLocaleString()}</td>
                    <td class="py-3 pr-4 text-right">
                        <button type="button" onclick="removeResource(${r.id})" class="text-gray-400 hover:text-red-500"><i class="ri-delete-bin-line"></i></button>
                    </td>
                </tr>
                `;
            }).join('');

            grandTotal += msTotal;

            card.innerHTML = `
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800">${ms.name}</h3>
                        <p class="text-xs text-gray-500">Subtotal: <span class="text-indigo-600 font-bold">฿${msTotal.toLocaleString()}</span></p>
                    </div>
                    <button type="button" onclick="openResourceModal(${ms.id}, '${ms.name}')" 
                        class="text-indigo-600 hover:bg-indigo-100 p-2 rounded-lg transition text-sm font-medium">
                        <i class="ri-add-line mr-1"></i> Add Resource
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white text-gray-500 font-medium border-b border-gray-100">
                            <tr>
                                <th class="py-2 pl-4 text-left font-medium">Item</th>
                                <th class="py-2 text-center font-medium">Qty</th>
                                <th class="py-2 text-right font-medium">Unit Cost</th>
                                <th class="py-2 text-right font-medium">Total</th>
                                <th class="py-2 pr-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows || '<tr><td colspan="5" class="py-4 text-center text-gray-400 italic">No resources specific to this milestone.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
            container.appendChild(card);
        }

        document.getElementById('grand-total').textContent = '฿' + grandTotal.toLocaleString(undefined, {
            minimumFractionDigits: 2
        });
    }

    // Modal Logic
    let currentMsId = null;

    function openResourceModal(msId, msName) {
        currentMsId = msId;
        document.getElementById('modal-ms-name').textContent = 'Milestone: ' + msName;
        document.getElementById('res-ms-id').value = msId;
        document.getElementById('res-name').value = '';
        document.getElementById('res-qty').value = 1;
        document.getElementById('res-cost').value = 0;
        document.getElementById('res-unit').value = '';

        const modal = document.getElementById('resource-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeResourceModal() {
        const modal = document.getElementById('resource-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function saveResource() {
        const name = document.getElementById('res-name').value;
        if (!name) return alert("Please enter resource name");

        const formData = new FormData();
        formData.append('action', 'add_resource');
        formData.append('milestone_id', currentMsId);
        formData.append('resource_name', name);
        formData.append('quantity', document.getElementById('res-qty').value);
        formData.append('unit', document.getElementById('res-unit').value);
        formData.append('unit_cost', document.getElementById('res-cost').value);

        fetch('?action=add_resource', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeResourceModal();
                    renderResources();
                }
            });
    }

    function removeResource(id) {
        Swal.fire({
            title: 'Remove resource?',
            text: "Are you sure you want to remove this resource?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('?action=remove_resource', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            renderResources();
                            Swal.fire(
                                'Deleted!',
                                'Your file has been deleted.',
                                'success'
                            )
                        }
                    });
            }
        });
    }
</script>