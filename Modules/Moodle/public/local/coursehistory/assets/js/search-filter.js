/**
 * Search and Filter functionality for Course History Profile page
 */

(function() {
    'use strict';
    
    // Initialize search and filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');
        const yearFilter = document.getElementById('year-filter');
        const sourceFilter = document.getElementById('source-filter');
        const courseTable = document.getElementById('course-table');
        
        if (!courseTable) return;
        
        // Bind event listeners
        if (searchInput) {
            searchInput.addEventListener('input', debounce(applyFilters, 300));
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', applyFilters);
        }
        
        if (yearFilter) {
            yearFilter.addEventListener('change', applyFilters);
        }
        
        if (sourceFilter) {
            sourceFilter.addEventListener('change', applyFilters);
        }
        
        // Add row data attributes to table rows
        addRowDataAttributes(courseTable);
        
        // Initial filter application
        applyFilters();
    });
    
    /**
     * Add data attributes to table rows for filtering
     */
    function addRowDataAttributes(table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            // Skip header row
            if (row.parentElement.tagName === 'THEAD') return;
            
            const cells = row.querySelectorAll('td');
            if (cells.length < 9) return; // Updated for new column structure
            
            // Extract data from cells
            const courseName = cells[1].textContent.toLowerCase().trim();
            const instructorName = cells[2].textContent.toLowerCase().trim();
            const organization = cells[3].textContent.toLowerCase().trim();
            const sourceBadge = cells[4].querySelector('.badge');
            const sourceType = getSourceTypeFromBadge(sourceBadge);
            const statusBadge = cells[6].querySelector('.badge');
            const status = statusBadge ? getStatusFromBadge(statusBadge) : '';
            const dateText = cells[7].textContent;
            const year = extractYearFromDate(dateText);
            
            // Add data attributes
            row.setAttribute('data-course', courseName);
            row.setAttribute('data-instructor', instructorName);
            row.setAttribute('data-organization', organization);
            row.setAttribute('data-source', sourceType);
            row.setAttribute('data-status', status);
            row.setAttribute('data-year', year);
            row.setAttribute('data-date', formatDateForFilter(dateText)); // Convert to YYYY-MM-DD format
            row.classList.add('course-row');
        });
    }
    
    /**
     * Convert Thai date format to YYYY-MM-DD
     */
    function formatDateForFilter(dateText) {
        // Handle format like "31/12/2566 14:30"
        const parts = dateText.trim().split(' ')[0].split('/');
        if (parts.length === 3) {
            const day = parts[0].padStart(2, '0');
            const month = parts[1].padStart(2, '0');
            let year = parseInt(parts[2]);
            
            // Convert Buddhist year (BE) to Gregorian year (CE)
            if (year > 2400) {
                year -= 543;
            }
            
            return `${year}-${month}-${day}`;
        }
        return dateText;
    }
    
    /**
     * Extract source type from badge text
     */
    function getSourceTypeFromBadge(badge) {
        if (!badge) return '';
        const text = badge.textContent.toLowerCase().trim();
        if (text.includes('ภายใน') || text.includes('internal')) return 'internal';
        if (text.includes('ภายนอก') || text.includes('external')) return 'external';
        return '';
    }
    
    /**
     * Extract status from badge text
     */
    function getStatusFromBadge(badge) {
        const text = badge.textContent.toLowerCase().trim();
        if (text.includes('รอตรวจ') || text.includes('pending')) return '0';
        if (text.includes('อนุมัติ') || text.includes('approved')) return '1';
        if (text.includes('ไม่อนุมัติ') || text.includes('rejected')) return '2';
        return '';
    }
    
    /**
     * Extract year from date text
     */
    function extractYearFromDate(dateText) {
        const match = dateText.match(/(\d{4})/);
        return match ? match[1] : '';
    }
    
    /**
     * Apply all filters to the table
     */
    function applyFilters() {
        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');
        const yearFilter = document.getElementById('year-filter');
        const sourceFilter = document.getElementById('source-filter');
        const courseTable = document.getElementById('course-table');
        
        if (!courseTable) return;
        
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const statusValue = statusFilter ? statusFilter.value : 'all';
        const yearValue = yearFilter ? yearFilter.value : 'all';
        const sourceValue = sourceFilter ? sourceFilter.value : 'all';
        
        const rows = courseTable.querySelectorAll('tbody tr.course-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            // Search filter
            if (searchTerm) {
                const course = row.getAttribute('data-course') || '';
                const instructor = row.getAttribute('data-instructor') || '';
                const organization = row.getAttribute('data-organization') || '';
                
                if (!course.includes(searchTerm) && 
                    !instructor.includes(searchTerm) && 
                    !organization.includes(searchTerm)) {
                    show = false;
                }
            }
            
            // Status filter
            if (show && statusValue !== 'all') {
                const rowStatus = row.getAttribute('data-status') || '';
                if (rowStatus !== statusValue) {
                    show = false;
                }
            }
            
            // Year filter
            if (show && yearValue !== 'all') {
                const rowYear = row.getAttribute('data-year') || '';
                if (rowYear !== yearValue) {
                    show = false;
                }
            }
            
            // Source type filter
            if (show && sourceValue !== 'all') {
                const rowSource = row.getAttribute('data-source') || '';
                if (rowSource !== sourceValue) {
                    show = false;
                }
            }
            
            // Show/hide row
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Show no results message if needed
        showNoResultsMessage(courseTable, visibleCount);
    }
    
    /**
     * Show/hide no results message
     */
    function showNoResultsMessage(table, visibleCount) {
        let messageRow = table.querySelector('.no-results-row');
        
        if (visibleCount === 0) {
            if (!messageRow) {
                messageRow = document.createElement('tr');
                messageRow.className = 'no-results-row';
                messageRow.innerHTML = `
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fa fa-search fa-2x mb-2"></i><br>
                        ไม่พบข้อมูลที่ตรงกับเงื่อนไขการค้นหา
                    </td>
                `;
                
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    tbody.appendChild(messageRow);
                }
            }
        } else if (messageRow) {
            messageRow.remove();
        }
    }
    
    /**
     * Debounce function to limit API calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Add clear filters functionality
     */
    function addClearFiltersButton() {
        const filterCard = document.querySelector('.card-body');
        if (!filterCard) return;
        
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-outline-secondary btn-sm';
        clearBtn.innerHTML = '<i class="fa fa-times"></i> ล้างตัวกรอง';
        clearBtn.addEventListener('click', clearAllFilters);
        
        filterCard.appendChild(clearBtn);
    }
    
    /**
     * Clear all filters
     */
    function clearAllFilters() {
        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');
        const yearFilter = document.getElementById('year-filter');
        
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = 'all';
        if (yearFilter) yearFilter.value = 'all';
        
        applyFilters();
    }
    
    // Add clear filters button after page load
    setTimeout(addClearFiltersButton, 100);
    
    // Listen for calendar filter events
    document.addEventListener('calendarFilter', function(e) {
        handleCalendarFilter(e.detail.type, e.detail.value);
    });
    
    /**
     * Handle calendar filter events
     */
    function handleCalendarFilter(type, value) {
        const courseTable = document.getElementById('course-table');
        if (!courseTable) return;
        
        const rows = courseTable.querySelectorAll('tbody tr.course-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            if (type === 'date') {
                const rowDate = row.getAttribute('data-date');
                show = rowDate === value;
            } else if (type === 'date-range') {
                const [startDate, endDate] = value.split('-');
                const rowDate = row.getAttribute('data-date');
                show = rowDate >= startDate && rowDate <= endDate;
            }
            
            // Apply other existing filters
            const searchInput = document.getElementById('search-input');
            const statusFilter = document.getElementById('status-filter');
            const yearFilter = document.getElementById('year-filter');
            const sourceFilter = document.getElementById('source-filter');
            
            // Search filter
            if (show && searchInput && searchInput.value.trim()) {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const course = row.getAttribute('data-course') || '';
                const instructor = row.getAttribute('data-instructor') || '';
                const organization = row.getAttribute('data-organization') || '';
                
                if (!course.includes(searchTerm) && 
                    !instructor.includes(searchTerm) && 
                    !organization.includes(searchTerm)) {
                    show = false;
                }
            }
            
            // Status filter
            if (show && statusFilter && statusFilter.value !== 'all') {
                const rowStatus = row.getAttribute('data-status') || '';
                if (rowStatus !== statusFilter.value) {
                    show = false;
                }
            }
            
            // Year filter
            if (show && yearFilter && yearFilter.value !== 'all') {
                const rowYear = row.getAttribute('data-year') || '';
                if (rowYear !== yearFilter.value) {
                    show = false;
                }
            }
            
            // Source type filter
            if (show && sourceFilter && sourceFilter.value !== 'all') {
                const rowSource = row.getAttribute('data-source') || '';
                if (rowSource !== sourceFilter.value) {
                    show = false;
                }
            }
            
            // Show/hide row
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Show no results message if needed
        showNoResultsMessage(courseTable, visibleCount);
    }
    
})();
