define(['jquery', 'theme_academi/slick'], function($) {
    'use strict';
    var RTL = ($('body').hasClass('dir-rtl')) ? true : false;
    return {
        init: function() {
            this.initCourseCatalogPagination();
        },
        /**
         * Client-side pagination for front page course catalog (.coursebox list).
         * Reads per-page from #frontpage-main-content data-course-catalog-per-page.
         */
        initCourseCatalogPagination: function() {
            var wrap = document.getElementById('frontpage-main-content');
            if (!wrap) {
                return;
            }
            var perPage = parseInt(wrap.getAttribute('data-course-catalog-per-page'), 10) || 6;
            var container = wrap.querySelector('.courses.frontpage-course-list-all') ||
                wrap.querySelector('.courses.frontpage-course-list-enrolled');
            if (!container) {
                return;
            }
            var boxes = container.querySelectorAll('.coursebox');
            var total = boxes.length;
            if (total <= perPage) {
                return;
            }
            var self = this;
            var currentPage = 1;
            var totalPages = Math.ceil(total / perPage);

            function showPage(page) {
                page = Math.max(1, Math.min(page, totalPages));
                currentPage = page;
                var start = (page - 1) * perPage;
                var end = start + perPage;
                boxes.forEach(function(box, i) {
                    box.style.display = (i >= start && i < end) ? '' : 'none';
                });
                self.renderCatalogPaginationBar(container, currentPage, totalPages, total, perPage);
            }

            this.renderCatalogPaginationBar = function(containerEl, page, totalPages, total, perPage) {
                var bar = containerEl.parentNode.querySelector('.course-catalog-pagination');
                if (bar) {
                    bar.remove();
                }
                bar = document.createElement('div');
                bar.className = 'course-catalog-pagination data-table-pagination';
                var start = (page - 1) * perPage + 1;
                var end = Math.min(page * perPage, total);
                bar.innerHTML =
                    '<div class="pagination-info">' +
                    start + '–' + end + ' / ' + total +
                    '</div>' +
                    '<div class="pagination-controls"></div>';
                var controls = bar.querySelector('.pagination-controls');
                if (page > 1) {
                    var prev = document.createElement('button');
                    prev.type = 'button';
                    prev.className = 'prev-page';
                    prev.setAttribute('aria-label', 'Previous');
                    prev.innerHTML = '&laquo;';
                    prev.addEventListener('click', function() {
                        showPage(page - 1);
                    });
                    controls.appendChild(prev);
                }
                for (var p = 1; p <= totalPages; p++) {
                    var span = document.createElement(p === page ? 'span' : 'button');
                    if (span.tagName === 'button') {
                        span.type = 'button';
                        span.addEventListener('click', (function(pp) {
                            return function() {
                                showPage(pp);
                            };
                        })(p));
                    }
                    span.textContent = p;
                    if (p === page) {
                        span.className = 'active';
                    }
                    controls.appendChild(span);
                }
                if (page < totalPages) {
                    var next = document.createElement('button');
                    next.type = 'button';
                    next.className = 'next-page';
                    next.setAttribute('aria-label', 'Next');
                    next.innerHTML = '&raquo;';
                    next.addEventListener('click', function() {
                        showPage(page + 1);
                    });
                    controls.appendChild(next);
                }
                containerEl.parentNode.appendChild(bar);
            };

            showPage(1);
        },
        // Available course block slider.
        availablecourses: function() {
            $(".course-slider").slick({
                arrows: true,
                swipe: true,
                infinite: false,
                slidesToShow: 4,
                slidesToScroll: 4,
                rtl: RTL,
                responsive: [
                    {
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                        }
                    },
                    {
                        breakpoint: 767,
                        settings: {

                            slidesToShow: 2,
                            slidesToScroll: 2,
                        }
                    },
                    {
                        breakpoint: 575,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1,
                        }
                    }
                ],

            });

            var prow = $(".course-slider").attr("data-crow");
            prow = parseInt(prow);
            if (prow < 2) {
                $("#available-courses .pagenav").hide();
            }
        },
        // Promoted course block slider.
        promotedcourse: function() {
            $(".promatedcourse-slider").slick({
                arrows: false,
                dots: true,
                swipe: true,
                infinite: false,
                slidesToShow: 4,
                slidesToScroll: 4,
                rtl: RTL,
                responsive: [
                    {
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                        }
                    },
                    {
                        breakpoint: 767,
                        settings: {

                            slidesToShow: 2,
                            slidesToScroll: 2,
                        }
                    },
                    {
                        breakpoint: 575,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1,
                        }
                    }
                ],

            });

            var prow = $(".promatedcourse-slider").attr("data-crow");
            prow = parseInt(prow);
            if (prow < 2) {
                $("#promoted-courses .pagenav").hide();
            }
        },
    };
});