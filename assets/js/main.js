/**
 * ZonaTech NG - Main JavaScript
 * OPTIMIZED for blazing fast performance
 */

(function($) {
    'use strict';
    
    // Initialize immediately when DOM is interactive (faster than ready)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ZonaTech.init();
        });
    } else {
        ZonaTech.init();
    }
    
    const ZonaTech = {
        init: function() {
            // Use requestIdleCallback for non-critical tasks
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    this.initScrollProgress();
                    this.initAnimations();
                });
            } else {
                setTimeout(() => {
                    this.initScrollProgress();
                    this.initAnimations();
                }, 1);
            }
            
            // Critical tasks immediately
            this.initDigitalClock();
            this.initTabs();
            this.initMobileMenu();
            this.initNotifications();
        },
        
        // Scroll Progress Bar - OPTIMIZED with throttle
        initScrollProgress: function() {
            const progressBar = document.createElement('div');
            progressBar.className = 'scroll-progress';
            document.body.prepend(progressBar);
            
            let ticking = false;
            const updateProgress = () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                progressBar.style.width = scrollPercent + '%';
                ticking = false;
            };
            
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateProgress);
                    ticking = true;
                }
            }, { passive: true });
        },
        
        // Scroll Animations - OPTIMIZED with IntersectionObserver
        initAnimations: function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            
            if (animatedElements.length === 0) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        observer.unobserve(entry.target); // Stop observing once animated
                    }
                });
            }, { threshold: 0.1, rootMargin: '50px' });
            
            animatedElements.forEach(el => observer.observe(el));
            
            // Add button animations with native JS
            document.querySelectorAll('.btn').forEach(btn => {
                btn.classList.add('btn-animated', 'btn-ripple');
            });
        },
        
        // Digital Clock
        initDigitalClock: function() {
            const clockElement = $('#digital-clock');
            if (!clockElement.length) return;
            
            const updateClock = () => {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12;
                hours = String(hours).padStart(2, '0');
                
                const options = { weekday: 'short', month: 'short', day: 'numeric' };
                const dateStr = now.toLocaleDateString('en-US', options);
                
                clockElement.find('.clock-time').text(`${hours}:${minutes}:${seconds}`);
                clockElement.find('.clock-date').text(dateStr);
                clockElement.find('.clock-ampm').text(ampm);
            };
            
            updateClock();
            setInterval(updateClock, 1000);
        },
        
        // Tab Navigation
        initTabs: function() {
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                const tabGroup = $(this).closest('.tab-container');
                
                tabGroup.find('.tab-btn').removeClass('active');
                $(this).addClass('active');
                
                tabGroup.find('.tab-content').removeClass('active');
                tabGroup.find('#' + tabId).addClass('active');
            });
        },
        
        // Mobile Menu
        initMobileMenu: function() {
            const toggle = $('.mobile-menu-toggle');
            const sidebar = $('.dashboard-sidebar');
            const overlay = $('.sidebar-overlay');
            
            toggle.on('click', function() {
                sidebar.toggleClass('active');
                overlay.toggleClass('active');
            });
            
            overlay.on('click', function() {
                sidebar.removeClass('active');
                overlay.removeClass('active');
            });
        },
        
        // Notifications
        initNotifications: function() {
            window.ZonaTechNotify = {
                show: function(message, type = 'info', duration = 4000) {
                    const container = $('#zonatech-notifications');
                    if (!container.length) {
                        $('body').append('<div id="zonatech-notifications" style="position: fixed; top: 20px; right: 20px; z-index: 99999;"></div>');
                    }
                    
                    const notification = $(`
                        <div class="zonatech-notification notification-enter" style="
                            padding: 1rem 1.25rem; 
                            margin-bottom: 0.75rem; 
                            border-radius: 0.75rem; 
                            max-width: 400px;
                            background: ${this.getBgColor(type)};
                            border: 2px solid ${this.getBorderColor(type)};
                            box-shadow: 0 4px 20px rgba(0,0,0,0.5), 0 0 20px ${this.getGlowColor(type)};
                        ">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas ${this.getIcon(type)}" style="color: ${this.getColor(type)}; font-size: 1.25rem;"></i>
                                <span style="font-size: 0.95rem; font-weight: 600; color: ${this.getColor(type)}; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">${message}</span>
                            </div>
                        </div>
                    `);
                    
                    $('#zonatech-notifications').append(notification);
                    
                    setTimeout(() => {
                        notification.addClass('notification-exit');
                        setTimeout(() => notification.remove(), 300);
                    }, duration);
                },
                
                getIcon: function(type) {
                    const icons = {
                        success: 'fa-check-circle',
                        error: 'fa-exclamation-circle',
                        warning: 'fa-exclamation-triangle',
                        info: 'fa-info-circle'
                    };
                    return icons[type] || icons.info;
                },
                
                getColor: function(type) {
                    const colors = {
                        success: '#4ade80',
                        error: '#f87171',
                        warning: '#fbbf24',
                        info: '#60a5fa'
                    };
                    return colors[type] || colors.info;
                },
                
                getBgColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.15)',
                        error: 'rgba(239, 68, 68, 0.15)',
                        warning: 'rgba(245, 158, 11, 0.15)',
                        info: 'rgba(59, 130, 246, 0.15)'
                    };
                    return colors[type] || colors.info;
                },
                
                getBorderColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.5)',
                        error: 'rgba(239, 68, 68, 0.5)',
                        warning: 'rgba(245, 158, 11, 0.5)',
                        info: 'rgba(59, 130, 246, 0.5)'
                    };
                    return colors[type] || colors.info;
                },
                
                getGlowColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.3)',
                        error: 'rgba(239, 68, 68, 0.3)',
                        warning: 'rgba(245, 158, 11, 0.3)',
                        info: 'rgba(59, 130, 246, 0.3)'
                    };
                    return colors[type] || colors.info;
                }
            };
        },
        
        // AJAX Helper
        ajax: function(action, data, callback) {
            data = data || {};
            data.action = action;
            data.nonce = zonatech_ajax.nonce;
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (callback) callback(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    if (callback) callback({ success: false, data: { message: 'Request failed. Please try again.' }});
                }
            });
        },
        
        // Format currency
        formatCurrency: function(amount) {
            return '₦' + parseFloat(amount).toLocaleString('en-NG');
        },
        
        // Format date
        formatDate: function(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
    
    // Expose globally
    window.ZonaTech = ZonaTech;
    
})(jQuery);