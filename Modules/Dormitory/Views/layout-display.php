<?php
// layout-display.php - View only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>

<div class="flex flex-col h-[calc(100vh-120px)] bg-gray-50 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                <i class="ri-map-2-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 leading-tight">ผังห้องพัก (Floor Plan)</h2>
                <p class="text-xs text-gray-500" id="displaySubtitle">แสดงภาพรวมห้องพักในแต่ละชั้น</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-lg">
                <select id="displayBuilding" class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer px-2" onchange="handleBuildingChange(this.value)">
                    <option value="">เลือกอาคาร...</option>
                </select>
                <div class="w-px h-4 bg-gray-300"></div>
                <select id="displayFloor" class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer px-2" onchange="loadFloorLayout()">
                    <option value="">เลือกชั้น...</option>
                </select>
            </div>

            <?php if ($isAdmin): ?>
                <a href="?page=layout-designer" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium transition-all shadow-sm">
                    <i class="ri-edit-line"></i>
                    แก้ไขผัง
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Layout Canvas Area -->
    <div class="flex-1 overflow-auto p-8 flex flex-col items-center bg-gray-100/50">
        <div class="w-full max-w-[1000px] flex justify-between mb-4">
            <div class="flex flex-wrap gap-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full shadow-sm"></div> ว่าง
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full shadow-sm"></div> มีผู้พัก
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-amber-500 rounded-full shadow-sm"></div> ซ่อมบำรุง
                </div>
                <div class="w-px h-3 bg-gray-200 ml-2"></div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-gray-200 rounded shadow-sm"></div> ทางเดิน
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-orange-100 border border-orange-200 rounded shadow-sm"></div> บันได
                </div>
            </div>
        </div>

        <!-- Main Display Canvas -->
        <div id="displayCanvas" class="relative bg-white border border-gray-200 rounded-2xl shadow-xl transition-all overflow-hidden"
            style="width: 1000px; height: 600px; background-image: radial-gradient(#f3f4f6 1px, transparent 1px); background-size: 50px 50px;">
            <!-- Content will be rendered here -->
            <div id="canvasEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                <i class="ri-survey-line text-7xl mb-4 opacity-10"></i>
                <p class="text-sm font-bold uppercase tracking-widest opacity-40">กรุณาเลือกอาคารและชั้นเพื่อดูผัง</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure (Reused from rooms.php) -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[52] opacity-0 invisible transition-all duration-200 p-5" id="roomModal">
    <div class="bg-white rounded-2xl w-full max-w-[600px] max-h-[calc(100vh-40px)] flex flex-col shadow-2xl transform -translate-y-5 transition-transform overflow-hidden">
        <div class="flex items-center justify-between px-8 py-5 border-b border-gray-100 bg-white shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="ri-door-open-line text-xl"></i>
                </div>
                <h3 class="text-xl font-extrabold text-gray-900 tracking-tight" id="roomModalTitle">รายละเอียดห้อง</h3>
            </div>
            <button class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors text-2xl" onclick="closeModal('roomModal')">&times;</button>
        </div>
        <div class="p-8 overflow-y-auto flex-1 bg-white custom-scrollbar" id="roomModalBody">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<style>
    .layout-item-node {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
        z-index: 10;
        transform-origin: center;
    }

    .room-display-node {
        color: #ffffff;
        font-weight: 800;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        cursor: pointer;
        font-size: 0.85rem;
        border: 2px solid white;
    }

    .room-display-node:hover {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 30;
    }

    .element-corridor {
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #9ca3af;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        pointer-events: none;
    }

    .element-stairs {
        background-color: #fff7ed;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(162, 29, 33, 0.03) 5px, rgba(162, 29, 33, 0.03) 10px);
        border: 1px dashed #fdba74;
        color: #c2410c;
        font-size: 10px;
        font-weight: bold;
        pointer-events: none;
    }

    .element-label {
        background-color: transparent;
        color: #4b5563;
        font-size: 11px;
        font-weight: 600;
        pointer-events: none;
        text-align: center;
    }

    .status-available {
        background-color: #10b981;
    }

    .status-occupied {
        background-color: #3b82f6;
    }

    .status-maintenance {
        background-color: #f59e0b;
    }

    .status-reserved {
        background-color: #8b5cf6;
    }

    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }

    .fixed.opacity-0.invisible[id$="Modal"].active>div {
        transform: translateY(0);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }
</style>

<script>
    let rooms = [];
    let layoutElements = [];
    let buildings = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await loadBuildings();
    });

    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            buildings = result.buildings || [];
            const select = document.getElementById('displayBuilding');
            buildings.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = `${b.code} - ${b.name}`;
                select.appendChild(opt);
            });
        } catch (e) {
            console.error(e);
        }
    }

    function handleBuildingChange(buildingId) {
        const floorSelect = document.getElementById('displayFloor');
        floorSelect.innerHTML = '<option value="">เลือกชั้น...</option>';
        if (!buildingId) return;
        const building = buildings.find(b => b.id == buildingId);
        if (building) {
            const floors = parseInt(building.total_floors || 1);
            for (let i = 1; i <= floors; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `ชั้น ${i}`;
                floorSelect.appendChild(opt);
            }
        }
    }

    async function loadFloorLayout() {
        const bId = document.getElementById('displayBuilding').value;
        const floor = document.getElementById('displayFloor').value;
        if (!bId || !floor) return;

        try {
            const result = await apiCall('rooms', 'list', {
                building_id: bId,
                floor: floor
            });
            rooms = (result.rooms || []).filter(r => r.layout_x > 0 && r.layout_y > 0);
            layoutElements = result.elements || [];
            renderDisplay();
        } catch (e) {
            console.error(e);
        }
    }

    function renderDisplay() {
        const canvas = document.getElementById('displayCanvas');
        const emptyState = document.getElementById('canvasEmptyState');

        canvas.querySelectorAll('.layout-item-node').forEach(n => n.remove());

        if (rooms.length === 0 && layoutElements.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');

        // 1. Render Elements first (lower z-index)
        layoutElements.forEach(el => {
            const node = document.createElement('div');
            const x = (el.x - 1) * 50;
            const y = (el.y - 1) * 50;
            const w = (el.w || 1) * 50;
            const h = (el.h || 1) * 50;

            node.className = `layout-item-node element-${el.type}`;
            node.style.left = `${x}px`;
            node.style.top = `${y}px`;
            node.style.width = `${w}px`;
            node.style.height = `${h}px`;

            if (el.type === 'label') {
                node.innerHTML = `<span>${el.text || ''}</span>`;
            } else if (el.type === 'corridor') {
                node.innerHTML = `<span>${el.text || 'ทางเดิน'}</span>`;
            } else if (el.type === 'stairs') {
                node.innerHTML = `<i class="ri-stairs-fill text-xl mb-1 opacity-20"></i><span>${el.text || 'บันได'}</span>`;
            }

            canvas.appendChild(node);
        });

        // 2. Render Rooms
        rooms.forEach(room => {
            const node = document.createElement('div');
            const x = (room.layout_x - 1) * 50;
            const y = (room.layout_y - 1) * 50;
            const w = (room.layout_w || 1) * 50;
            const h = (room.layout_h || 1) * 50;

            node.className = `layout-item-node room-display-node status-${room.status}`;
            node.style.left = `${x}px`;
            node.style.top = `${y}px`;
            node.style.width = `${w}px`;
            node.style.height = `${h}px`;
            node.innerHTML = `<span>${room.room_number}</span>`;

            node.onclick = () => showRoomDetail(room.id);
            canvas.appendChild(node);
        });
    }

    // Modal helpers (simplified version from rooms.php)
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    async function showRoomDetail(roomId) {
        try {
            const result = await apiCall('rooms', 'get', {
                id: roomId
            });
            const room = result.room;

            document.getElementById('roomModalTitle').textContent = `ห้อง ${room.building_code}${room.room_number}`;

            const statusLabels = {
                'available': 'ว่าง',
                'occupied': 'มีผู้พัก',
                'maintenance': 'ซ่อมบำรุง'
            };

            document.getElementById('roomModalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">ประเภทห้อง</div>
                            <div class="font-bold text-gray-900">${getRoomTypeText(room.room_type)}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">สถานะ</div>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold status-${room.status} text-white">
                                ${statusLabels[room.status] || room.status}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                         <div class="p-3 border border-gray-100 rounded-lg">
                            <div class="text-xs text-gray-400 mb-1">ความจุ</div>
                            <div class="font-medium">${room.capacity} คน</div>
                        </div>
                        <div class="p-3 border border-gray-100 rounded-lg">
                            <div class="text-xs text-gray-400 mb-1">ค่าเช่า</div>
                            <div class="font-medium text-primary">${formatCurrency(room.monthly_rent || 0)}</div>
                        </div>
                    </div>

                    ${room.current_occupants && room.current_occupants.length > 0 ? `
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-2">ผู้พักอาศัย (${room.current_occupants.length})</h4>
                            <div class="space-y-2">
                                ${room.current_occupants.map(occ => `
                                    <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs">
                                            <i class="ri-user-line"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">
                                                ${occ.employee_name}
                                                ${parseInt(occ.accompanying_persons || 0) > 0 ? `<span class="ml-1 text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-medium">+ญาติ ${occ.accompanying_persons} คน</span>` : ''}
                                            </div>
                                            <div class="text-[11px] text-gray-500">${occ.department || '-'}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : `
                        <div class="p-8 text-center bg-gray-50 rounded-xl text-gray-400 italic text-sm">
                            ไม่มีผู้พักอาศัยในขณะนี้
                        </div>
                    `}
                </div>
            `;
            openModal('roomModal');
        } catch (e) {
            console.error(e);
        }
    }

    function getRoomTypeText(type) {
        const map = {
            'single': 'ห้องเดี่ยว',
            'double': 'ห้องคู่',
            'family': 'ห้องครอบครัว',
            'executive': 'ห้องผู้บริหาร',
            'suite': 'ห้องชุด'
        };
        return map[type] || type;
    }
</script>