/**
 * Autocomplete functionality for Course History form
 */

(function() {
    'use strict';
    
    // Autocomplete class
    class Autocomplete {
        constructor(input, options) {
            this.input = input;
            this.options = Object.assign({
                url: '',
                minLength: 2,
                delay: 300,
                maxResults: 10
            }, options);
            
            this.init();
        }
        
        init() {
            this.createDropdown();
            this.bindEvents();
        }
        
        createDropdown() {
            this.dropdown = document.createElement('div');
            this.dropdown.className = 'autocomplete-dropdown';
            this.dropdown.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                max-height: 200px;
                overflow-y: auto;
                width: 100%;
                box-sizing: border-box;
                z-index: 1000;
                display: none;
            `;
            
            this.input.parentNode.style.position = 'relative';
            this.input.parentNode.appendChild(this.dropdown);
        }
        
        bindEvents() {
            let timeout;
            
            this.input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                const query = e.target.value.trim();
                
                if (query.length < this.options.minLength) {
                    this.hideDropdown();
                    return;
                }
                
                timeout = setTimeout(() => {
                    this.fetchSuggestions(query);
                }, this.options.delay);
            });
            
            this.input.addEventListener('focus', () => {
                if (this.input.value.trim().length >= this.options.minLength) {
                    this.fetchSuggestions(this.input.value.trim());
                }
            });
            
            this.input.addEventListener('blur', () => {
                setTimeout(() => this.hideDropdown(), 200);
            });
            
            this.input.addEventListener('keydown', (e) => {
                this.handleKeydown(e);
            });
            
            document.addEventListener('click', (e) => {
                if (!this.input.contains(e.target) && !this.dropdown.contains(e.target)) {
                    this.hideDropdown();
                }
            });
        }
        
        handleKeydown(e) {
            const items = this.dropdown.querySelectorAll('.autocomplete-item');
            if (items.length === 0) return;
            
            let currentIndex = -1;
            items.forEach((item, index) => {
                if (item.classList.contains('selected')) {
                    currentIndex = index;
                }
            });
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    currentIndex = Math.min(currentIndex + 1, items.length - 1);
                    this.selectItem(items, currentIndex);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    currentIndex = Math.max(currentIndex - 1, 0);
                    this.selectItem(items, currentIndex);
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (currentIndex >= 0) {
                        items[currentIndex].click();
                    }
                    break;
                case 'Escape':
                    this.hideDropdown();
                    break;
            }
        }
        
        selectItem(items, index) {
            items.forEach(item => item.classList.remove('selected'));
            items[index].classList.add('selected');
            items[index].scrollIntoView({ block: 'nearest' });
        }
        
        async fetchSuggestions(query) {
            try {
                const response = await fetch(this.options.url + '?q=' + encodeURIComponent(query));
                const data = await response.json();
                
                if (data.error) {
                    console.error('Autocomplete error:', data.error);
                    return;
                }
                
                this.showSuggestions(data);
            } catch (error) {
                console.error('Autocomplete fetch error:', error);
            }
        }
        
        showSuggestions(suggestions) {
            this.dropdown.innerHTML = '';
            
            if (suggestions.length === 0) {
                this.hideDropdown();
                return;
            }
            
            suggestions.slice(0, this.options.maxResults).forEach((suggestion, index) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = suggestion.label || suggestion.value;
                item.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #f0f0f0;
                `;
                
                if (index === 0) {
                    item.classList.add('selected');
                    item.style.backgroundColor = '#f0f0f0';
                }
                
                item.addEventListener('mouseenter', () => {
                    document.querySelectorAll('.autocomplete-item').forEach(i => {
                        i.classList.remove('selected');
                        i.style.backgroundColor = '';
                    });
                    item.classList.add('selected');
                    item.style.backgroundColor = '#f0f0f0';
                });
                
                item.addEventListener('click', () => {
                    this.input.value = suggestion.value;
                    this.hideDropdown();
                    this.input.dispatchEvent(new Event('change'));
                });
                
                this.dropdown.appendChild(item);
            });
            
            this.showDropdown();
        }
        
        showDropdown() {
            const rect = this.input.getBoundingClientRect();
            this.dropdown.style.top = (this.input.offsetHeight) + 'px';
            this.dropdown.style.left = '0px';
            this.dropdown.style.display = 'block';
        }
        
        hideDropdown() {
            this.dropdown.style.display = 'none';
        }
    }
    
    // Initialize autocomplete on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Course name autocomplete
        const courseInput = document.getElementById('id_coursename');
        if (courseInput) {
            new Autocomplete(courseInput, {
                url: M.cfg.wwwroot + '/local/coursehistory/ajax.php?action=courses',
                minLength: 2,
                delay: 300
            });
        }
        
        // Instructor name autocomplete
        const instructorInput = document.getElementById('id_instructorname');
        if (instructorInput) {
            new Autocomplete(instructorInput, {
                url: M.cfg.wwwroot + '/local/coursehistory/ajax.php?action=instructors',
                minLength: 2,
                delay: 300
            });
        }
        
        // Organization autocomplete
        const organizationInput = document.getElementById('id_organization');
        if (organizationInput) {
            new Autocomplete(organizationInput, {
                url: M.cfg.wwwroot + '/local/coursehistory/ajax.php?action=organizations',
                minLength: 2,
                delay: 300
            });
        }
    });
    
})();
