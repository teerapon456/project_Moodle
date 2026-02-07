<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติการจองรถ - INTEQC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }

        .content {
            padding: 30px;
        }

        .booking-details {
            background: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }

        .booking-details p {
            margin: 8px 0;
        }

        .buttons {
            margin-top: 30px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-approve {
            background: #27ae60;
            color: white;
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
        }

        .btn-approve:hover {
            background: #229954;
        }

        .btn-reject:hover {
            background: #c0392b;
        }

        .spin {
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        #rejectForm {
            display: none;
            margin-top: 20px;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }

        .message {
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>🚗 อนุมัติการจองรถ</h2>
        </div>
        <div class="content" id="content">
            <p>กำลังโหลด...</p>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        let token = urlParams.get('token');
        const action = urlParams.get('action');

        // Clean token if it contains garbage (e.g. :1 from browser search)
        if (token && token.includes(':')) {
            token = token.split(':')[0];
        }

        if (!token) {
            showError('ไม่พบ token การอนุมัติ');
        } else {
            loadBookingDetails();
        }

        async function loadBookingDetails() {
            try {
                const response = await fetch(`../api.php?controller=bookings&action=get_token_details&token=${encodeURIComponent(token)}`);
                const data = await response.json();

                if (!response.ok || !data.success) {
                    showError(data.message || 'ไม่สามารถโหลดข้อมูลได้');
                    return;
                }

                // ถ้าดำเนินการแล้ว (อนุมัติ/ปฏิเสธ/ขั้นต่อไป) ให้แสดงข้อความเลย
                if (data.already_processed) {
                    displayBookingDetails(data);
                    return;
                }

                // ยัง pending_supervisor ปกติ
                displayBookingDetails(data);

                // ถ้าแนบ action=approve/reject มาในลิงก์ → แสดงผลลัพธ์ทันที และส่งไปประมวลผลหลังบ้าน
                if (action === 'approve') {
                    showImmediateSuccess('approved');
                    // ส่งไปประมวลผลหลังบ้าน (background)
                    processApprovalBackground('approve_token');
                } else if (action === 'reject') {
                    showImmediateSuccess('rejected');
                    // ส่งไปประมวลผลหลังบ้าน (background)  
                    processApprovalBackground('reject_token');
                }
            } catch (error) {
                console.error(error);
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        }

        function displayBookingDetails(booking) {
            const content = document.getElementById('content');

            // ถ้าอนุมัติ / ปฏิเสธ / ดำเนินการต่อแล้ว
            if (booking.already_processed) {
                let statusClass = 'success';
                let statusIcon = 'ℹ️';
                let statusColor = '#3498db';
                let statusHeader = 'การจองนี้ได้รับการดำเนินการแล้ว';

                if (booking.status === 'approved') {
                    statusClass = 'success';
                    statusIcon = '✅';
                    statusColor = '#27ae60';
                    statusHeader = 'อนุมัติเรียบร้อยแล้ว';
                } else if (booking.status === 'rejected') {
                    statusClass = 'error';
                    statusIcon = '✗';
                    statusColor = '#dc3545';
                    statusHeader = 'ถูกปฏิเสธแล้ว';
                }

                content.innerHTML = `
                    <div class="message ${statusClass}" style="${booking.status === 'rejected' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : ''}">
                        <h3>${statusIcon} ${statusHeader}</h3>
                        <p>สถานะ: ${booking.status_message}</p>
                    </div>
                    
                    <div class="booking-details">
                        <p><strong>ผู้ขอจอง:</strong> ${booking.user_name}</p>
                        <p><strong>ปลายทาง:</strong> ${booking.destination}</p>
                        <p><strong>วัตถุประสงค์:</strong> ${booking.purpose}</p>
                        <p><strong>เวลาเริ่มต้น:</strong> ${formatDateTime(booking.start_time)}</p>
                        <p><strong>เวลาสิ้นสุด:</strong> ${formatDateTime(booking.end_time)}</p>
                        <p><strong>คนขับรถ:</strong> ${booking.driver}</p>
                        <p><strong>ผู้โดยสาร:</strong> ${booking.passenger || '-'}   </p>
                        <p><strong>สถานะปัจจุบัน:</strong> 
                            <span style="color: ${statusColor}; font-weight: bold;">
                                ${booking.status_message}
                            </span>
                        </p>
                    </div>
                    
                    <p style="text-align: center; margin-top: 20px; color: #777;">
                        คุณสามารถปิดหน้านี้ได้
                    </p>
                `;
                return;
            }

            // ยัง pending_supervisor ปกติ → แสดงปุ่มอนุมัติ/ปฏิเสธ
            content.innerHTML = `
                <div class="booking-details">
                    <p><strong>ผู้ขอจอง:</strong> ${booking.user_name}</p>
                    <p><strong>ปลายทาง:</strong> ${booking.destination}</p>
                    <p><strong>วัตถุประสงค์:</strong> ${booking.purpose}</p>
                    <p><strong>เวลาเริ่มต้น:</strong> ${formatDateTime(booking.start_time)}</p>
                    <p><strong>เวลาสิ้นสุด:</strong> ${formatDateTime(booking.end_time)}</p>
                    <p><strong>คนขับรถ:</strong> ${booking.driver}</p>
                    <p><strong>ผู้โดยสาร:</strong> ${booking.passenger || '-'}</p>
                </div>
                
                <div class="buttons" id="actionButtons">
                    <button class="btn btn-approve" onclick="handleApprove()">✓ อนุมัติ</button>
                    <button class="btn btn-reject" onclick="showRejectForm()">✗ ปฏิเสธ</button>
                </div>

                <div id="rejectForm">
                    <label>เหตุผลในการปฏิเสธ:</label>
                    <select id="rejectionReason" onchange="handleReasonChange()" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- เลือกเหตุผล --</option>
                        <option value="รถไม่ว่างในช่วงเวลาที่จอง">รถไม่ว่างในช่วงเวลาที่จอง</option>
                        <option value="ไม่มีคนขับรถในวันที่จอง">ไม่มีคนขับรถในวันที่จอง</option>
                        <option value="ผู้จองไม่มีสิทธิ์ใช้รถ">ผู้จองไม่มีสิทธิ์ใช้รถ</option>
                        <option value="การจองขัดต่อนโยบายบริษัท">การจองขัดต่อนโยบายบริษัท</option>
                        <option value="รถอยู่ในระหว่างซ่อมแซม">รถอยู่ในระหว่างซ่อมแซม</option>
                        <option value="อื่นๆ (กรุณาระบุ)">อื่นๆ (กรุณาระบุ)</option>
                    </select>
                    <textarea id="customReason" placeholder="กรุณาระบุเหตุผลเพิ่มเติม..." style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; display: none; resize: vertical;"></textarea>
                    <div class="buttons">
                        <button class="btn btn-reject" onclick="handleReject()">ยืนยันการปฏิเสธ</button>
                        <button class="btn" onclick="hideRejectForm()" style="background:#95a5a6;color:white;">ยกเลิก</button>
                    </div>
                </div>

                <div id="message"></div>
            `;
        }

        function showRejectForm() {
            const actionButtons = document.getElementById('actionButtons');
            const rejectForm = document.getElementById('rejectForm');
            if (actionButtons) actionButtons.style.display = 'none';
            if (rejectForm) rejectForm.style.display = 'block';
        }

        function hideRejectForm() {
            const actionButtons = document.getElementById('actionButtons');
            const rejectForm = document.getElementById('rejectForm');
            if (actionButtons) actionButtons.style.display = 'block';
            if (rejectForm) rejectForm.style.display = 'none';
        }

        function handleReasonChange() {
            const reasonSelect = document.getElementById('rejectionReason');
            const customReason = document.getElementById('customReason');

            if (reasonSelect.value === 'อื่นๆ (กรุณาระบุ)') {
                customReason.style.display = 'block';
                customReason.required = true;
            } else {
                customReason.style.display = 'none';
                customReason.required = false;
                customReason.value = '';
            }
        }

        // ✅ กดปุ่มอนุมัติ → ยิงเลย ไม่ใช้ confirm()
        async function handleApprove() {
            // ปิดปุ่มทันที กันกดรัว
            disableActions();


            let timeoutId2;
            try {
                const controller = new AbortController();
                timeoutId2 = setTimeout(() => controller.abort(), 10000);

                const response = await fetch(`../api.php?controller=bookings&action=approve_token`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token
                    }),
                    signal: controller.signal
                });

                clearTimeout(timeoutId2);

                const data = await response.json().catch(() => ({}));

                if (data.success) {
                    showImmediateSuccess('approved');
                    // Trigger Async Email Notification
                    fetch(`../api.php?controller=bookings&action=sendEmailNotification`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: data.booking_id,
                            type: 'supervisor_approval'
                        })
                    }).catch(err => console.error('Email trigger failed', err));

                } else if (response.status === 404) {
                    await refreshBookingStatusAsProcessed();
                } else {
                    showError(data.message || 'เกิดข้อผิดพลาดในการอนุมัติ');
                    enableActions();
                }
            } catch (error) {
                clearTimeout(timeoutId2);
                console.error(error);
                if (error.name === 'AbortError') {
                    showError('การดำเนินการนานเกินไป กรุณาลองใหม่');
                } else {
                    showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
                enableActions();
            }
        }

        async function handleReject() {
            const reasonSelect = document.getElementById('rejectionReason').value.trim();
            const customReason = document.getElementById('customReason').value.trim();

            let finalReason = reasonSelect;
            if (reasonSelect === 'อื่นๆ (กรุณาระบุ)') {
                finalReason = customReason;
            }

            if (!finalReason) {
                const msgEl = document.getElementById('message');
                if (msgEl) {
                    msgEl.innerHTML = '<div class="message error">กรุณาเลือกหรือระบุเหตุผลในการปฏิเสธ</div>';
                }
                return;
            }

            disableActions();

            let timeoutId2;
            try {
                const controller = new AbortController();
                timeoutId2 = setTimeout(() => controller.abort(), 10000);

                const response = await fetch(`../api.php?controller=bookings&action=reject_token`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token,
                        reason: finalReason
                    }),
                    signal: controller.signal
                });

                clearTimeout(timeoutId2);

                const data = await response.json().catch(() => ({}));

                if (data.success) {
                    showImmediateSuccess('rejected', finalReason);
                    // Trigger Async Email Notification
                    fetch(`../api.php?controller=bookings&action=sendEmailNotification`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: data.booking_id,
                            type: 'rejection'
                        })
                    }).catch(err => console.error('Email trigger failed', err));

                } else if (response.status === 404) {
                    await refreshBookingStatusAsProcessed();
                } else {
                    showError(data.message || 'เกิดข้อผิดพลาดในการปฏิเสธ');
                    enableActions();
                }
            } catch (error) {
                clearTimeout(timeoutId2);
                console.error(error);
                if (error.name === 'AbortError') {
                    showError('การดำเนินการนานเกินไป กรุณาลองใหม่');
                } else {
                    showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
                enableActions();
            }
        }

        async function refreshBookingStatusAsProcessed() {
            const res = await fetch(`../api.php?controller=bookings&action=get_token_details&token=${encodeURIComponent(token)}`);
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.already_processed) {
                displayBookingDetails(data);
            } else if (!res.ok && data.message) {
                showError(data.message);
            } else {
                showError('คำขอนี้ถูกดำเนินการไปแล้วหรือ token หมดอายุ');
            }
        }

        function disableActions() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';

                if (btn.classList.contains('btn-approve') || btn.classList.contains('btn-reject')) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="ri-loader-4-line spin"></i> กำลังดำเนินการ...';
                    btn.dataset.originalText = originalText;
                }
            });
        }

        function enableActions() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';

                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                    delete btn.dataset.originalText;
                }
            });
        }

        function showMessage(message, type) {
            const content = document.getElementById('content');
            content.innerHTML = `<div class="message ${type}">${message}</div>`;
        }

        function showError(message) {
            showMessage(message, 'error');
        }

        function showImmediateSuccess(action, reason = '') {
            const content = document.getElementById('content');

            if (action === 'approved') {
                content.innerHTML = `
                    <div class="message success">
                        <h3>✅ อนุมัติสำเร็จ!</h3>
                        <p>การจองรถได้รับการอนุมัติเรียบร้อยแล้ว</p>
                    </div>
                    
                    <div class="booking-details">
                        <p><strong>สถานะ:</strong> 
                            <span style="color: #27ae60; font-weight: bold;">
                                อนุมัติแล้ว ✓
                            </span>
                        </p>
                    </div>
                    
                    <p style="text-align: center; margin-top: 20px; color: #777;">
                        คุณสามารถปิดหน้านี้ได้
                    </p>
                `;
            } else if (action === 'rejected') {
                content.innerHTML = `
                    <div class="message error" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                        <h3>✗ ปฏิเสธแล้ว</h3>
                        <p>การจองรถได้รับการปฏิเสธเรียบร้อยแล้ว</p>
                        ${reason ? `<p><strong>เหตุผล:</strong> ${reason}</p>` : ''}
                    </div>
                    
                    <div class="booking-details">
                        <p><strong>สถานะ:</strong> 
                            <span style="color: #dc3545; font-weight: bold;">
                                ถูกปฏิเสธ ✗
                            </span>
                        </p>
                    </div>
                    
                    <p style="text-align: center; margin-top: 20px; color: #777;">
                        คุณสามารถปิดหน้านี้ได้
                    </p>
                `;
            }
        }

        function processApprovalBackground(action) {
            fetch(`../api.php?controller=bookings&action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.booking_id) {
                        // Trigger Async Email Notification
                        const emailType = (action === 'approve_token') ? 'supervisor_approval' : 'rejection';
                        fetch(`../api.php?controller=bookings&action=sendEmailNotification`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: data.booking_id,
                                type: emailType
                            })
                        }).catch(err => console.error('Email trigger failed', err));
                    }
                })
                .catch(error => {
                    console.error('Background processing failed:', error);
                });
        }

        function formatDateTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>

</body>

</html>