<?php
// layout-designer.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>

<div class="flex flex-col h-[calc(100vh-120px)] bg-gray-50 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
    <!-- Header Controls -->
    <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <i class="ri-layout-grid-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 leading-tight">Layout Designer</h2>
                <p class="text-xs text-gray-500">ลากห้องพักไปวางบนผังเพื่อกำหนดตำแหน่งการแสดงผล</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-lg">
                <select id="designerBuilding" class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer px-2" onchange="handleBuildingChange(this.value)">
                    <option value="">เลือกอาคาร...</option>
                </select>
                <div class="w-px h-4 bg-gray-300"></div>
                <select id="designerFloor" class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer px-2" onchange="loadFloorLayout()">
                    <option value="">เลือกชั้น...</option>
                </select>
            </div>

            <button onclick="saveLayout()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-all shadow-sm active:scale-95 disabled:opacity-50" id="saveBtn" disabled>
                <i class="ri-save-3-line"></i>
                บันทึกตำแหน่ง
            </button>
        </div>
    </div>

    <!-- Workspace -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar: Room List & Tools -->
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
            <!-- Tabs or Sections -->
            <div class="flex-1 overflow-y-auto">
                <!-- Rooms Section -->
                <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                        <i class="ri-door-open-line"></i>
                        ห้องพัก (Rooms)
                    </h3>
                </div>
                <div id="unplacedRooms" class="p-4 space-y-3 min-h-[200px]">
                    <div class="py-8 text-gray-400 text-center text-sm px-4 italic">กรุณาเลือกอาคารและชั้น...</div>
                </div>

                <!-- Elements Section -->
                <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                        <i class="ri-stack-line"></i>
                        องค์ประกอบอื่นๆ (Elements)
                    </h3>
                </div>
                <div class="p-3 grid grid-cols-3 gap-2">
                    <div class="element-tool flex flex-col items-center justify-center gap-1 p-2.5 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary transition-all active:cursor-grabbing" draggable="true" ondragstart="handleElementDragStart(event, 'corridor')" title="ทางเดิน">
                        <i class="ri-split-cells-horizontal text-lg text-gray-500"></i>
                        <span class="text-[9px] font-bold text-gray-600 leading-tight text-center">ทางเดิน</span>
                    </div>
                    <div class="element-tool flex flex-col items-center justify-center gap-1 p-2.5 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary transition-all active:cursor-grabbing" draggable="true" ondragstart="handleElementDragStart(event, 'stairs')" title="บันได">
                        <i class="ri-stairs-fill text-lg text-gray-500"></i>
                        <span class="text-[9px] font-bold text-gray-600 leading-tight text-center">บันได</span>
                    </div>
                    <div class="element-tool flex flex-col items-center justify-center gap-1 p-2.5 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary transition-all active:cursor-grabbing" draggable="true" ondragstart="handleElementDragStart(event, 'label')" title="ข้อความ">
                        <i class="ri-text text-lg text-gray-500"></i>
                        <span class="text-[9px] font-bold text-gray-600 leading-tight text-center">ข้อความ</span>
                    </div>
                    <div class="element-tool flex flex-col items-center justify-center gap-1 p-2.5 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary transition-all active:cursor-grabbing" draggable="true" ondragstart="handleElementDragStart(event, 'storage')" title="ห้องเก็บของ">
                        <i class="ri-archive-2-line text-lg text-gray-500"></i>
                        <span class="text-[9px] font-bold text-gray-600 leading-tight text-center">เก็บของ</span>
                    </div>
                    <div class="element-tool flex flex-col items-center justify-center gap-1 p-2.5 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary transition-all active:cursor-grabbing" draggable="true" ondragstart="handleElementDragStart(event, 'fire-escape')" title="บันไดหนีไฟ">
                        <i class="ri-alarm-warning-line text-lg text-red-400"></i>
                        <span class="text-[9px] font-bold text-gray-600 leading-tight text-center">หนีไฟ</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-blue-50 border-t border-blue-100">
                <p class="text-[10px] text-blue-600 font-medium leading-relaxed">
                    <i class="ri-information-line text-xs"></i>
                    <b>คำแนะนำ:</b> ลากห้องหรือองค์ประกอบไปวางในตาราง คลิกขวาที่องค์ประกอบเพื่อลบหรือแก้ไข
                </p>
            </div>
        </div>

        <!-- Design Area -->
        <div class="flex-1 overflow-auto p-8 relative flex flex-col items-center">
            <!-- Info Labels -->
            <div class="w-full max-w-[1000px] flex justify-between mb-2 px-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">FLOOR PLAN CANVAS</span>
                <span class="text-[10px] font-bold text-gray-400" id="canvasSizeLabel">GRID: 20 x 12 (50px each)</span>
            </div>

            <!-- Main Canvas -->
            <div id="layoutCanvas" class="relative bg-white border border-gray-200 rounded-lg shadow-sm transition-all overflow-hidden"
                style="width: 1000px; height: 600px; background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 50px 50px;"
                ondragover="handleDragOver(event)" ondrop="handleDrop(event)" ondragleave="handleDragLeave(event)">
                <!-- Target Slots -->
                <!-- Placed Rooms & Elements will be here -->
            </div>

        </div>
    </div>
</div>

<style>
    .room-card-unplaced {
        padding: 0.85rem;
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        cursor: grab;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .room-card-unplaced:active {
        cursor: grabbing;
    }

    .room-card-unplaced:hover {
        border-color: #A21D21;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .layout-item {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        user-select: none;
        z-index: 10;
        transform-origin: center;
    }

    .layout-item:hover {
        z-index: 20;
        box-shadow: 0 0 0 2px rgba(162, 29, 33, 0.3);
    }

    .resize-handle {
        position: absolute;
        width: 12px;
        height: 12px;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 30;
    }

    .resize-handle.rh-se {
        bottom: 0;
        right: 0;
        cursor: nwse-resize;
    }

    .resize-handle.rh-sw {
        bottom: 0;
        left: 0;
        cursor: nesw-resize;
    }

    .resize-handle.rh-ne {
        top: 0;
        right: 0;
        cursor: nesw-resize;
    }

    .resize-handle.rh-nw {
        top: 0;
        left: 0;
        cursor: nwse-resize;
    }

    .resize-handle::after {
        content: '';
        position: absolute;
        width: 6px;
        height: 6px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 1px;
    }

    .resize-handle.rh-se::after {
        bottom: 2px;
        right: 2px;
    }

    .resize-handle.rh-sw::after {
        bottom: 2px;
        left: 2px;
    }

    .resize-handle.rh-ne::after {
        top: 2px;
        right: 2px;
    }

    .resize-handle.rh-nw::after {
        top: 2px;
        left: 2px;
    }

    .layout-item:hover .resize-handle {
        opacity: 1;
    }

    .layout-item.resizing {
        z-index: 50;
        opacity: 0.85;
    }

    .room-card-placed {
        color: #ffffff;
        font-weight: 700;
        border-radius: 0.4rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        font-size: 0.75rem;
    }

    .element-corridor {
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #9ca3af;
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .element-stairs {
        background-color: #fff7ed;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(162, 29, 33, 0.05) 5px, rgba(162, 29, 33, 0.05) 10px);
        border: 1px dashed #fdba74;
        color: #c2410c;
        font-size: 10px;
        font-weight: bold;
    }

    .element-label {
        background-color: transparent;
        color: #374151;
        font-size: 11px;
        font-weight: 500;
        border: 1px solid transparent;
    }

    .element-label:hover {
        background-color: rgba(255, 255, 255, 0.8);
        border-color: #e5e7eb;
    }

    .element-storage {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        font-size: 10px;
        font-weight: 600;
    }

    .element-fire-escape {
        background-color: #fef2f2;
        background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, rgba(239, 68, 68, 0.06) 5px, rgba(239, 68, 68, 0.06) 10px);
        border: 1px dashed #fca5a5;
        color: #dc2626;
        font-size: 10px;
        font-weight: bold;
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

    #layoutCanvas.drag-over {
        background-color: rgba(0, 0, 0, 0.01);
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
    let allBuildingRooms = [];
    let layoutElements = [];
    let buildings = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await loadBuildings();
    });

    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            buildings = result.buildings || [];
            const select = document.getElementById('designerBuilding');
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
        const floorSelect = document.getElementById('designerFloor');
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

    // =============== COLLISION DETECTION ===============
    function getAllOccupiedCells(excludeType, excludeId) {
        const cells = new Set();
        allBuildingRooms.forEach(room => {
            if (room.layout_x === 0 && room.layout_y === 0) return;
            if (excludeType === 'room' && room.id == excludeId) return;
            for (let dx = 0; dx < (room.layout_w || 1); dx++)
                for (let dy = 0; dy < (room.layout_h || 1); dy++)
                    cells.add(`${room.layout_x + dx},${room.layout_y + dy}`);
        });
        layoutElements.forEach((el, idx) => {
            if (excludeType === 'element' && idx == excludeId) return;
            for (let dx = 0; dx < (el.w || 1); dx++)
                for (let dy = 0; dy < (el.h || 1); dy++)
                    cells.add(`${el.x + dx},${el.y + dy}`);
        });
        return cells;
    }

    function hasCollision(gx, gy, gw, gh, excludeType, excludeId) {
        const occupied = getAllOccupiedCells(excludeType, excludeId);
        for (let dx = 0; dx < gw; dx++)
            for (let dy = 0; dy < gh; dy++) {
                if (gx + dx > 20 || gy + dy > 12) return true;
                if (occupied.has(`${gx + dx},${gy + dy}`)) return true;
            }
        return false;
    }

    // =============== RESIZE LOGIC ===============
    function addResizeHandle(parentDiv, getPos, excludeType, excludeId, onResize) {
        const corners = ['se', 'sw', 'ne', 'nw'];
        corners.forEach(corner => {
            const handle = document.createElement('div');
            handle.className = `resize-handle rh-${corner}`;
            handle.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                e.preventDefault();
                parentDiv.classList.add('resizing');
                parentDiv.draggable = false;
                const startX = e.clientX,
                    startY = e.clientY;
                const startW = parentDiv.offsetWidth,
                    startH = parentDiv.offsetHeight;
                const startL = parentDiv.offsetLeft,
                    startT = parentDiv.offsetTop;
                const pos = getPos();

                function onMouseMove(ev) {
                    const dx = ev.clientX - startX,
                        dy = ev.clientY - startY;
                    let newW, newH, newL, newT, newGx, newGy;

                    if (corner === 'se') {
                        newW = Math.max(50, Math.round((startW + dx) / 50) * 50);
                        newH = Math.max(50, Math.round((startH + dy) / 50) * 50);
                        newGx = pos.x;
                        newGy = pos.y;
                    } else if (corner === 'sw') {
                        newW = Math.max(50, Math.round((startW - dx) / 50) * 50);
                        newH = Math.max(50, Math.round((startH + dy) / 50) * 50);
                        newGx = pos.x - (newW / 50 - (startW / 50));
                        newGy = pos.y;
                    } else if (corner === 'ne') {
                        newW = Math.max(50, Math.round((startW + dx) / 50) * 50);
                        newH = Math.max(50, Math.round((startH - dy) / 50) * 50);
                        newGx = pos.x;
                        newGy = pos.y - (newH / 50 - (startH / 50));
                    } else { // nw
                        newW = Math.max(50, Math.round((startW - dx) / 50) * 50);
                        newH = Math.max(50, Math.round((startH - dy) / 50) * 50);
                        newGx = pos.x - (newW / 50 - (startW / 50));
                        newGy = pos.y - (newH / 50 - (startH / 50));
                    }

                    if (newGx < 1 || newGy < 1) return;
                    if (!hasCollision(newGx, newGy, newW / 50, newH / 50, excludeType, excludeId)) {
                        parentDiv.style.width = newW + 'px';
                        parentDiv.style.height = newH + 'px';
                        parentDiv.style.left = ((newGx - 1) * 50) + 'px';
                        parentDiv.style.top = ((newGy - 1) * 50) + 'px';
                        parentDiv._gx = newGx;
                        parentDiv._gy = newGy;
                    }
                }

                function onMouseUp() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    parentDiv.classList.remove('resizing');
                    parentDiv.draggable = true;
                    const fw = Math.max(1, Math.round(parentDiv.offsetWidth / 50));
                    const fh = Math.max(1, Math.round(parentDiv.offsetHeight / 50));
                    const fx = parentDiv._gx || pos.x;
                    const fy = parentDiv._gy || pos.y;
                    onResize(fx, fy, fw, fh);
                }
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
            parentDiv.appendChild(handle);
        });
    }

    // =============== DROP INDICATOR ===============
    let dropIndicator = null;
    let dragItemW = 1,
        dragItemH = 1,
        dragExcludeType = null,
        dragExcludeId = null;

    function showDropIndicator(canvas, gx, gy, w, h, blocked) {
        if (!dropIndicator) {
            dropIndicator = document.createElement('div');
            dropIndicator.style.cssText = 'position:absolute;border-radius:4px;pointer-events:none;z-index:5;transition:left 0.08s,top 0.08s;';
        }
        dropIndicator.style.left = ((gx - 1) * 50) + 'px';
        dropIndicator.style.top = ((gy - 1) * 50) + 'px';
        dropIndicator.style.width = (w * 50) + 'px';
        dropIndicator.style.height = (h * 50) + 'px';
        dropIndicator.style.border = blocked ? '2px dashed #ef4444' : '2px dashed #3b82f6';
        dropIndicator.style.background = blocked ? 'rgba(239,68,68,0.12)' : 'rgba(59,130,246,0.08)';
        if (!dropIndicator.parentNode) canvas.appendChild(dropIndicator);
    }

    function hideDropIndicator() {
        if (dropIndicator && dropIndicator.parentNode) dropIndicator.parentNode.removeChild(dropIndicator);
    }

    async function loadFloorLayout() {
        const bId = document.getElementById('designerBuilding').value;
        const floor = document.getElementById('designerFloor').value;
        if (!bId || !floor) {
            resetUI();
            return;
        }

        try {
            const result = await apiCall('rooms', 'list', {
                building_id: bId,
                floor: floor
            });
            allBuildingRooms = result.rooms || [];
            layoutElements = result.elements || [];
            renderWorkspace();
            document.getElementById('saveBtn').disabled = false;
        } catch (e) {
            console.error(e);
        }
    }

    function resetUI() {
        document.getElementById('unplacedRooms').innerHTML = '<div class="py-12 text-gray-400 text-center text-sm px-4">กรุณาเลือกอาคารและชั้น...</div>';
        document.getElementById('layoutCanvas').innerHTML = '';
        document.getElementById('saveBtn').disabled = true;
    }

    function renderWorkspace() {
        const unplacedDiv = document.getElementById('unplacedRooms');
        const canvas = document.getElementById('layoutCanvas');
        unplacedDiv.innerHTML = '';
        unplacedDiv.className = 'p-3 grid grid-cols-3 gap-2 overflow-y-auto custom-scrollbar';
        canvas.innerHTML = '';

        // 1. Render Rooms
        allBuildingRooms.forEach(room => {
            if (room.layout_x === 0 && room.layout_y === 0) {
                const card = document.createElement('div');
                card.className = 'flex flex-col items-center justify-center p-2 bg-white border border-gray-200 rounded-lg shadow-sm cursor-grab hover:border-primary hover:shadow transition-all active:cursor-grabbing';
                card.draggable = true;
                const occ = (room.occupants || []).length;
                const cap = room.capacity || '?';
                card.innerHTML = `
                    <span class="text-sm font-bold text-gray-900 leading-tight">${room.room_number}</span>
                    <span class="text-[9px] text-gray-400">${occ}/${cap}</span>
                `;
                card.ondragstart = (e) => {
                    e.dataTransfer.setData('type', 'room');
                    e.dataTransfer.setData('id', room.id);
                    dragItemW = 1;
                    dragItemH = 1;
                    dragExcludeType = null;
                    dragExcludeId = null;
                };
                unplacedDiv.appendChild(card);
            } else {
                renderRoomOnCanvas(room);
            }
        });

        // 2. Render Elements
        layoutElements.forEach((el, index) => {
            renderElementOnCanvas(el, index);
        });

        if (unplacedDiv.innerHTML === '') {
            unplacedDiv.innerHTML = '<div class="text-center py-6 text-emerald-500 bg-emerald-50 rounded-xl border border-emerald-100"><i class="ri-checkbox-circle-line text-2xl"></i><p class="text-[10px] font-bold mt-1 uppercase">ALL PLACED</p></div>';
        }
    }

    function renderRoomOnCanvas(room) {
        const canvas = document.getElementById('layoutCanvas');
        const card = document.createElement('div');
        const x = (room.layout_x - 1) * 50;
        const y = (room.layout_y - 1) * 50;
        const w = (room.layout_w || 1) * 50;
        const h = (room.layout_h || 1) * 50;

        card.className = `layout-item room-card-placed status-${room.status}`;
        card.style.left = `${x}px`;
        card.style.top = `${y}px`;
        card.style.width = `${w}px`;
        card.style.height = `${h}px`;
        card.style.border = '2px solid white';
        card.innerHTML = `<span class="text-xs font-bold leading-tight">${room.room_number}</span><span class="text-[8px] opacity-75">${(room.occupants || []).length}/${room.capacity || '?'}</span>`;
        card.title = `ห้อง ${room.room_number} (${(room.occupants || []).length}/${room.capacity || '?'}) - คลิกขวาเพื่อนำออก`;

        card.oncontextmenu = (e) => {
            e.preventDefault();
            removeFromCanvas('room', room.id);
        };

        // Drag to move on canvas
        card.draggable = true;
        card.ondragstart = (e) => {
            e.dataTransfer.setData('type', 'room');
            e.dataTransfer.setData('id', room.id);
            dragItemW = room.layout_w || 1;
            dragItemH = room.layout_h || 1;
            dragExcludeType = 'room';
            dragExcludeId = room.id;
            setTimeout(() => card.style.opacity = '0', 0);
        };

        // Resize handle
        addResizeHandle(card, () => ({
            x: room.layout_x,
            y: room.layout_y
        }), 'room', room.id, (fx, fy, fw, fh) => {
            room.layout_x = fx;
            room.layout_y = fy;
            room.layout_w = fw;
            room.layout_h = fh;
            renderWorkspace();
        });

        canvas.appendChild(card);
    }

    function renderElementOnCanvas(el, index) {
        const canvas = document.getElementById('layoutCanvas');
        const div = document.createElement('div');
        const x = (el.x - 1) * 50;
        const y = (el.y - 1) * 50;
        const w = (el.w || 1) * 50;
        const h = (el.h || 1) * 50;

        div.className = `layout-item element-${el.type}`;
        div.style.left = `${x}px`;
        div.style.top = `${y}px`;
        div.style.width = `${w}px`;
        div.style.height = `${h}px`;

        if (el.type === 'label') {
            div.innerHTML = `<span>${el.text || 'Label'}</span>`;
        } else if (el.type === 'corridor') {
            div.innerHTML = `<span>${el.text || 'ทางเดิน'}</span>`;
        } else if (el.type === 'stairs') {
            div.innerHTML = `<i class="ri-stairs-fill text-xl mb-1 opacity-20"></i><span>${el.text || 'บันได'}</span>`;
        } else if (el.type === 'storage') {
            div.innerHTML = `<i class="ri-archive-2-line text-xl mb-1 opacity-30"></i><span>${el.text || 'ห้องเก็บของ'}</span>`;
        } else if (el.type === 'fire-escape') {
            div.innerHTML = `<i class="ri-alarm-warning-line text-xl mb-1 opacity-30"></i><span>${el.text || 'บันไดหนีไฟ'}</span>`;
        }

        div.oncontextmenu = async (e) => {
            e.preventDefault();
            const confirmed = await showConfirm('ต้องการลบองค์ประกอบนี้หรือไม่?', 'ยืนยันการลบ');
            if (confirmed) {
                layoutElements.splice(index, 1);
                renderWorkspace();
            }
        };

        div.onclick = async () => {
            const newText = await showPrompt('แก้ไขข้อความ:', 'แก้ไของค์ประกอบ', el.text || '');
            if (newText !== null) {
                el.text = newText;
                renderWorkspace();
            }
        };

        // Drag to move
        div.draggable = true;
        div.ondragstart = (e) => {
            e.dataTransfer.setData('type', 'element');
            e.dataTransfer.setData('index', index);
            dragItemW = el.w || 1;
            dragItemH = el.h || 1;
            dragExcludeType = 'element';
            dragExcludeId = index;
            setTimeout(() => div.style.opacity = '0', 0);
        };

        // Resize handle
        addResizeHandle(div, () => ({
            x: el.x,
            y: el.y
        }), 'element', index, (fx, fy, fw, fh) => {
            el.x = fx;
            el.y = fy;
            el.w = fw;
            el.h = fh;
            renderWorkspace();
        });

        canvas.appendChild(div);
    }

    function handleElementDragStart(e, type) {
        e.dataTransfer.setData('type', 'palette-element');
        e.dataTransfer.setData('elementType', type);
        dragItemW = 1;
        dragItemH = 1;
        dragExcludeType = null;
        dragExcludeId = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        const rect = e.currentTarget.getBoundingClientRect();
        const gx = Math.floor((e.clientX - rect.left) / 50) + 1;
        const gy = Math.floor((e.clientY - rect.top) / 50) + 1;
        if (gx >= 1 && gx <= 20 && gy >= 1 && gy <= 12) {
            const blocked = hasCollision(gx, gy, dragItemW, dragItemH, dragExcludeType, dragExcludeId);
            showDropIndicator(e.currentTarget, gx, gy, dragItemW, dragItemH, blocked);
        }
    }

    function handleDragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
        hideDropIndicator();
    }

    function handleDrop(e) {
        e.preventDefault();
        hideDropIndicator();

        const type = e.dataTransfer.getData('type');
        const rect = e.currentTarget.getBoundingClientRect();
        const gx = Math.floor((e.clientX - rect.left) / 50) + 1;
        const gy = Math.floor((e.clientY - rect.top) / 50) + 1;
        if (gx < 1 || gy < 1 || gx > 20 || gy > 12) return;

        if (type === 'room') {
            const id = e.dataTransfer.getData('id');
            const room = allBuildingRooms.find(r => r.id == id);
            if (!room) return;
            const w = room.layout_w || 1,
                h = room.layout_h || 1;
            if (hasCollision(gx, gy, w, h, 'room', id)) {
                showToast('ตำแหน่งนี้ซ้อนทับกับรายการอื่น', 'warning');
                renderWorkspace();
                return;
            }
            room.layout_x = gx;
            room.layout_y = gy;
            renderWorkspace();
        } else if (type === 'element') {
            const index = e.dataTransfer.getData('index');
            const el = layoutElements[index];
            if (!el) return;
            const w = el.w || 1,
                h = el.h || 1;
            if (hasCollision(gx, gy, w, h, 'element', index)) {
                showToast('ตำแหน่งนี้ซ้อนทับกับรายการอื่น', 'warning');
                renderWorkspace();
                return;
            }
            el.x = gx;
            el.y = gy;
            renderWorkspace();
        } else if (type === 'palette-element') {
            const elementType = e.dataTransfer.getData('elementType');
            if (hasCollision(gx, gy, 1, 1, null, null)) {
                showToast('ตำแหน่งนี้ซ้อนทับกับรายการอื่น', 'warning');
                return;
            }
            const defaultText = elementType === 'corridor' ? 'ทางเดิน' : (elementType === 'stairs' ? 'บันได' : (elementType === 'storage' ? 'ห้องเก็บของ' : (elementType === 'fire-escape' ? 'บันไดหนีไฟ' : 'ข้อความใหม่')));
            layoutElements.push({
                type: elementType,
                x: gx,
                y: gy,
                w: 1,
                h: 1,
                text: defaultText
            });
            renderWorkspace();
        }
    }

    function removeFromCanvas(type, id) {
        if (type === 'room') {
            const room = allBuildingRooms.find(r => r.id == id);
            if (room) {
                room.layout_x = 0;
                room.layout_y = 0;
                renderWorkspace();
            }
        }
    }

    async function saveLayout() {
        const bId = document.getElementById('designerBuilding').value;
        const floor = document.getElementById('designerFloor').value;

        const roomsToSave = allBuildingRooms.map(r => ({
            id: r.id,
            x: r.layout_x,
            y: r.layout_y,
            w: r.layout_w || 1,
            h: r.layout_h || 1
        }));

        try {
            const btn = document.getElementById('saveBtn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';
            btn.disabled = true;

            await apiCall('rooms', 'updateLayout', {
                building_id: bId,
                floor: floor,
                layouts: roomsToSave,
                elements: layoutElements
            }, 'POST');

            showToast('บันทึกเลย์เอาต์เรียบร้อยแล้ว', 'success');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        } catch (e) {
            showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
            document.getElementById('saveBtn').disabled = false;
            document.getElementById('saveBtn').innerHTML = '<i class="ri-save-3-line"></i> บันทึกตำแหน่ง';
        }
    }
</script>