/**
 * Calendar functionality for Course History
 */

(function() {
    'use strict';
    
    class CourseHistoryCalendar {
        constructor(containerId, options = {}) {
            this.container = document.getElementById(containerId);
            if (!this.container) return;
            
            this.options = Object.assign({
                courses: [],
                onDateSelect: null,
                onRangeSelect: null,
                view: 'month', // month, week, schedule
                language: 'th'
            }, options);
            
            this.currentDate = new Date();
            this.selectedDate = null;
            this.selectedRange = { start: null, end: null };
            this.viewMode = this.options.view;
            
            this.init();
        }
        
        init() {
            this.createCalendarHTML();
            this.bindEvents();
            this.render();
        }
        
        createCalendarHTML() {
            this.container.innerHTML = `
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-title"></div>
                        <div class="calendar-nav">
                            <button type="button" class="nav-prev">&lt;</button>
                            <button type="button" class="nav-today">วันนี้</button>
                            <button type="button" class="nav-next">&gt;</button>
                        </div>
                    </div>
                    
                    <div class="quick-filters">
                        <button type="button" class="quick-filter-btn" data-range="today">วันนี้</button>
                        <button type="button" class="quick-filter-btn" data-range="week">สัปดาห์นี้</button>
                        <button type="button" class="quick-filter-btn" data-range="month">เดือนนี้</button>
                        <button type="button" class="quick-filter-btn" data-range="year">ปีนี้</button>
                    </div>
                    
                    <div class="date-picker">
                        <input type="date" class="start-date" placeholder="วันที่เริ่มต้น">
                        <span>ถึง</span>
                        <input type="date" class="end-date" placeholder="วันที่สิ้นสุด">
                        <button type="button" class="apply-range">ใช้ช่วงวันที่</button>
                    </div>
                    
                    <div class="calendar-grid-container">
                        <div class="calendar-grid"></div>
                    </div>
                    
                    <div class="schedule-view" style="display: none;">
                        <h4>ตารางการเรียน</h4>
                        <div class="schedule-timeline"></div>
                    </div>
                </div>
            `;
        }
        
        bindEvents() {
            // Navigation buttons
            this.container.querySelector('.nav-prev').addEventListener('click', () => {
                this.navigate(-1);
            });
            
            this.container.querySelector('.nav-next').addEventListener('click', () => {
                this.navigate(1);
            });
            
            this.container.querySelector('.nav-today').addEventListener('click', () => {
                this.currentDate = new Date();
                this.render();
            });
            
            // Quick filter buttons
            this.container.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.applyQuickFilter(e.target.dataset.range);
                });
            });
            
            // Date range picker
            this.container.querySelector('.apply-range').addEventListener('click', () => {
                this.applyDateRange();
            });
            
            // View toggle (if needed)
            this.addViewToggle();
        }
        
        addViewToggle() {
            const header = this.container.querySelector('.calendar-header');
            const viewToggle = document.createElement('div');
            viewToggle.className = 'view-toggle';
            viewToggle.innerHTML = `
                <button type="button" class="view-btn ${this.viewMode === 'month' ? 'active' : ''}" data-view="month">เดือน</button>
                <button type="button" class="view-btn ${this.viewMode === 'schedule' ? 'active' : ''}" data-view="schedule">ตาราง</button>
            `;
            viewToggle.style.cssText = 'display: flex; gap: 5px;';
            header.appendChild(viewToggle);
            
            viewToggle.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.switchView(e.target.dataset.view);
                });
            });
        }
        
        navigate(direction) {
            if (this.viewMode === 'month') {
                this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            } else if (this.viewMode === 'week') {
                this.currentDate.setDate(this.currentDate.getDate() + (direction * 7));
            }
            this.render();
        }
        
        switchView(view) {
            this.viewMode = view;
            
            // Update button states
            this.container.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            // Show/hide appropriate containers
            const gridContainer = this.container.querySelector('.calendar-grid-container');
            const scheduleView = this.container.querySelector('.schedule-view');
            
            if (view === 'schedule') {
                gridContainer.style.display = 'none';
                scheduleView.style.display = 'block';
                this.renderSchedule();
            } else {
                gridContainer.style.display = 'block';
                scheduleView.style.display = 'none';
                this.render();
            }
        }
        
        render() {
            if (this.viewMode === 'month') {
                this.renderMonth();
            } else if (this.viewMode === 'week') {
                this.renderWeek();
            }
            
            this.updateTitle();
        }
        
        renderMonth() {
            const grid = this.container.querySelector('.calendar-grid');
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            
            // Clear grid
            grid.innerHTML = '';
            
            // Add weekday headers
            const weekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
            weekdays.forEach(day => {
                const weekdayEl = document.createElement('div');
                weekdayEl.className = 'calendar-weekday';
                weekdayEl.textContent = day;
                grid.appendChild(weekdayEl);
            });
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();
            
            // Add empty cells for days before month starts
            for (let i = 0; i < startingDayOfWeek; i++) {
                const dayEl = document.createElement('div');
                dayEl.className = 'calendar-day other-month';
                const prevMonthDay = new Date(year, month, -startingDayOfWeek + i + 1);
                dayEl.textContent = prevMonthDay.getDate();
                dayEl.dataset.date = this.formatDate(prevMonthDay);
                grid.appendChild(dayEl);
            }
            
            // Add days of month
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = document.createElement('div');
                dayEl.className = 'calendar-day';
                const currentDate = new Date(year, month, day);
                
                dayEl.textContent = day;
                dayEl.dataset.date = this.formatDate(currentDate);
                
                // Check if today
                if (this.isSameDay(currentDate, today)) {
                    dayEl.classList.add('today');
                }
                
                // Check if has courses
                const coursesOnDay = this.getCoursesOnDate(currentDate);
                if (coursesOnDay.length > 0) {
                    dayEl.classList.add('has-courses');
                    dayEl.title = `${coursesOnDay.length} หลักสูตร`;
                }
                
                // Add click handler
                dayEl.addEventListener('click', () => {
                    this.selectDate(currentDate);
                });
                
                grid.appendChild(dayEl);
            }
            
            // Add empty cells for days after month ends
            const totalCells = grid.children.length - 7; // Subtract weekday headers
            const remainingCells = totalCells % 7;
            if (remainingCells > 0) {
                for (let i = 1; i <= (7 - remainingCells); i++) {
                    const dayEl = document.createElement('div');
                    dayEl.className = 'calendar-day other-month';
                    dayEl.textContent = i;
                    const nextMonthDay = new Date(year, month + 1, i);
                    dayEl.dataset.date = this.formatDate(nextMonthDay);
                    grid.appendChild(dayEl);
                }
            }
        }
        
        renderWeek() {
            // Similar to month but for week view
            this.renderMonth(); // For now, use month view
        }
        
        renderSchedule() {
            const timeline = this.container.querySelector('.schedule-timeline');
            timeline.innerHTML = '';
            
            if (this.options.courses.length === 0) {
                timeline.innerHTML = '<p class="text-muted">ไม่มีข้อมูลหลักสูตร</p>';
                return;
            }
            
            // Sort courses by date
            const sortedCourses = [...this.options.courses].sort((a, b) => 
                a.timecreated - b.timecreated
            );
            
            // Group courses by date
            const coursesByDate = {};
            sortedCourses.forEach(course => {
                const date = new Date(course.timecreated * 1000);
                const dateKey = this.formatDate(date);
                if (!coursesByDate[dateKey]) {
                    coursesByDate[dateKey] = [];
                }
                coursesByDate[dateKey].push(course);
            });
            
            // Render timeline
            Object.keys(coursesByDate).sort().forEach(dateKey => {
                const courses = coursesByDate[dateKey];
                const date = new Date(dateKey);
                
                courses.forEach(course => {
                    const item = document.createElement('div');
                    item.className = `schedule-item ${course.source_type || 'external'}`;
                    
                    const dateStr = date.toLocaleDateString('th-TH', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });
                    
                    item.innerHTML = `
                        <div class="schedule-date">${dateStr}</div>
                        <div class="schedule-content">
                            <h4>${course.coursename}</h4>
                            <p>${course.instructorname} - ${course.organization}</p>
                            ${course.source_type === 'internal' ? 
                                '<span class="badge badge-info">หลักสูตรภายใน</span>' : 
                                '<span class="badge badge-secondary">หลักสูตรภายนอก</span>'
                            }
                        </div>
                    `;
                    
                    timeline.appendChild(item);
                });
            });
        }
        
        selectDate(date) {
            // Clear previous selection
            this.container.querySelectorAll('.calendar-day.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selection to clicked date
            const dayEl = this.container.querySelector(`[data-date="${this.formatDate(date)}"]`);
            if (dayEl) {
                dayEl.classList.add('selected');
            }
            
            this.selectedDate = date;
            
            // Trigger callback
            if (this.options.onDateSelect) {
                this.options.onDateSelect(date);
            }
        }
        
        applyQuickFilter(range) {
            const today = new Date();
            let startDate, endDate;
            
            // Clear previous selection
            this.container.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Set active button
            this.container.querySelector(`[data-range="${range}"]`).classList.add('active');
            
            switch (range) {
                case 'today':
                    startDate = endDate = today;
                    break;
                case 'week':
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - today.getDay());
                    endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = new Date(today.getFullYear(), 11, 31);
                    break;
            }
            
            this.selectedRange = { start: startDate, end: endDate };
            
            // Update date inputs
            this.container.querySelector('.start-date').value = this.formatDateForInput(startDate);
            this.container.querySelector('.end-date').value = this.formatDateForInput(endDate);
            
            // Trigger callback
            if (this.options.onRangeSelect) {
                this.options.onRangeSelect(startDate, endDate);
            }
        }
        
        applyDateRange() {
            const startInput = this.container.querySelector('.start-date');
            const endInput = this.container.querySelector('.end-date');
            
            if (!startInput.value || !endInput.value) {
                alert('กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด');
                return;
            }
            
            const startDate = new Date(startInput.value);
            const endDate = new Date(endInput.value);
            
            if (startDate > endDate) {
                alert('วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด');
                return;
            }
            
            // Clear quick filter selection
            this.container.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            this.selectedRange = { start: startDate, end: endDate };
            
            // Trigger callback
            if (this.options.onRangeSelect) {
                this.options.onRangeSelect(startDate, endDate);
            }
        }
        
        updateTitle() {
            const titleEl = this.container.querySelector('.calendar-title');
            const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                              'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            
            if (this.viewMode === 'month') {
                titleEl.textContent = `${monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
            } else if (this.viewMode === 'week') {
                // Calculate week range
                const weekStart = new Date(this.currentDate);
                weekStart.setDate(this.currentDate.getDate() - this.currentDate.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);
                
                titleEl.textContent = `${weekStart.getDate()} ${monthNames[weekStart.getMonth()]} - ${weekEnd.getDate()} ${monthNames[weekEnd.getMonth()]} ${weekStart.getFullYear()}`;
            } else if (this.viewMode === 'schedule') {
                titleEl.textContent = 'ตารางการเรียน';
            }
        }
        
        getCoursesOnDate(date) {
            const dateStr = this.formatDate(date);
            return this.options.courses.filter(course => {
                const courseDate = new Date(course.timecreated * 1000);
                return this.formatDate(courseDate) === dateStr;
            });
        }
        
        formatDate(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }
        
        formatDateForInput(date) {
            return this.formatDate(date);
        }
        
        isSameDay(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        }
    }
    
    // Initialize calendar on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Find calendar container and initialize if exists
        const calendarContainer = document.getElementById('course-calendar');
        if (calendarContainer) {
            // Get course data from page or fetch via AJAX
            const courses = window.courseHistoryData || [];
            
            const calendar = new CourseHistoryCalendar('course-calendar', {
                courses: courses,
                onDateSelect: function(date) {
                    console.log('Date selected:', date);
                    // Trigger table filtering
                    filterTableByDate(date);
                },
                onRangeSelect: function(startDate, endDate) {
                    console.log('Range selected:', startDate, 'to', endDate);
                    // Trigger table filtering
                    filterTableByDateRange(startDate, endDate);
                }
            });
        }
    });
    
    // Helper functions for table filtering
    function filterTableByDate(date) {
        const dateStr = formatDateForFilter(date);
        filterTable('date', dateStr);
    }
    
    function filterTableByDateRange(startDate, endDate) {
        const startStr = formatDateForFilter(startDate);
        const endStr = formatDateForFilter(endDate);
        filterTable('date-range', `${startStr}-${endStr}`);
    }
    
    function formatDateForFilter(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    
    function filterTable(type, value) {
        // This would integrate with the existing search-filter.js
        const event = new CustomEvent('calendarFilter', {
            detail: { type: type, value: value }
        });
        document.dispatchEvent(event);
    }
    
    // Export class for global access
    window.CourseHistoryCalendar = CourseHistoryCalendar;
    
})();
