/**
 * Shopify App Bridge Mock Library
 * A standalone JavaScript library that replicates Shopify App Bridge functionality
 * 
 * Usage:
 * 1. Include this script: <script src="shopify-bridge.js"></script>
 * 2. Configure: shopify.config.shopUrl = 'https://your-shop.myshopify.com';
 * 3. Use the APIs: shopify.toast.show(), shopify.modal.show(), etc.
 */

(function() {
    'use strict';

    // Inject styles into document
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        :root {
            --shopify-color-primary: #008060;
            --shopify-color-primary-hover: #006e52;
            --shopify-color-error: #d82c0d;
            --shopify-color-success: #008060;
            --shopify-color-background: #f6f6f7;
            --shopify-color-surface: #ffffff;
            --shopify-color-text: #202223;
            --shopify-color-text-secondary: #6d7175;
            --shopify-color-border: #c9cccf;
            --shopify-shadow-modal: 0 26px 80px rgba(0, 0, 0, 0.2);
            --shopify-shadow-toast: 0 4px 16px rgba(0, 0, 0, 0.1);
            --shopify-radius-base: 8px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --shopify-color-background: #1a1a1a;
                --shopify-color-surface: #2c2c2c;
                --shopify-color-text: #e3e3e3;
                --shopify-color-text-secondary: #b5b5b5;
                --shopify-color-border: #3d3d3d;
            }
        }

        #shopify-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }

        .shopify-toast {
            background: var(--shopify-color-surface);
            color: var(--shopify-color-text);
            padding: 16px 20px;
            border-radius: var(--shopify-radius-base);
            box-shadow: var(--shopify-shadow-toast);
            min-width: 300px;
            max-width: 500px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shopifySlideIn 0.3s ease;
            pointer-events: auto;
            border-left: 4px solid var(--shopify-color-success);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .shopify-toast.error {
            border-left-color: var(--shopify-color-error);
        }

        .shopify-toast-icon {
            font-size: 20px;
            line-height: 1;
        }

        .shopify-toast-message {
            flex: 1;
            font-size: 14px;
            line-height: 1.5;
        }

        @keyframes shopifySlideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes shopifySlideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        #shopify-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        #shopify-loading-overlay.active {
            display: flex;
        }

        .shopify-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: shopifySpin 0.8s linear infinite;
        }

        @keyframes shopifySpin {
            to { transform: rotate(360deg); }
        }

        ui-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 9998;
            animation: shopifyFadeIn 0.2s ease;
        }

        ui-modal[open] {
            display: flex;
        }

        ui-modal > .modal-content {
            background: var(--shopify-color-surface);
            border-radius: var(--shopify-radius-base);
            box-shadow: var(--shopify-shadow-modal);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow: auto;
            animation: shopifyModalSlideIn 0.3s ease;
        }

        @keyframes shopifyFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes shopifyModalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        ui-title-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-top: 1px solid var(--shopify-color-border);
            gap: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        ui-title-bar::before {
            content: attr(title);
            font-weight: 600;
            font-size: 16px;
            flex: 1;
            color: var(--shopify-color-text);
        }

        .shopify-modal-body {
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--shopify-color-text);
        }

        .shopify-resource-picker-modal {
            padding: 20px;
        }

        .shopify-resource-picker-header {
            margin-bottom: 16px;
        }

        .shopify-resource-picker-header h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: var(--shopify-color-text);
        }

        .shopify-resource-picker-header p {
            color: var(--shopify-color-text-secondary);
            font-size: 14px;
            margin: 0 0 12px 0;
        }

        .shopify-resource-picker-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--shopify-color-border);
            border-radius: 6px;
            font-size: 14px;
            background: var(--shopify-color-surface);
            color: var(--shopify-color-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            box-sizing: border-box;
        }

        .shopify-resource-picker-input:focus {
            outline: 2px solid var(--shopify-color-primary);
            outline-offset: 0;
        }

        .shopify-resource-picker-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        .shopify-btn {
            padding: 10px 16px;
            border: 1px solid var(--shopify-color-border);
            border-radius: 6px;
            background: var(--shopify-color-surface);
            color: var(--shopify-color-text);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .shopify-btn:hover {
            background: var(--shopify-color-background);
        }

        .shopify-btn-primary {
            background: var(--shopify-color-primary);
            color: white;
            border-color: var(--shopify-color-primary);
        }

        .shopify-btn-primary:hover {
            background: var(--shopify-color-primary-hover);
            border-color: var(--shopify-color-primary-hover);
        }
    `;
    
    // Wait for DOM to be ready before injecting styles
    if (document.head) {
        document.head.appendChild(styleElement);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            document.head.appendChild(styleElement);
        });
    }
    window.shopify = window.shopify || {};
    // Toast Notifications
    window.shopify.toast = {
        show: function(message, options) {
            options = options || {};
            var duration = options.duration !== undefined ? options.duration : 5000;
            var isError = options.isError || false;
            
            // Create toast container if it doesn't exist
            var container = document.getElementById('shopify-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'shopify-toast-container';
                document.body.appendChild(container);
            }

            // Create toast element
            var toast = document.createElement('div');
            toast.className = 'shopify-toast' + (isError ? ' error' : '');
            toast.innerHTML = '<span class="shopify-toast-icon">' + (isError ? '❌' : '✅') + '</span>' +
                            '<span class="shopify-toast-message">' + message + '</span>';

            container.appendChild(toast);

            // Auto remove after duration
            setTimeout(function() {
                toast.style.animation = 'shopifySlideOut 0.3s ease';
                setTimeout(function() {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                    if (container.children.length === 0 && document.body.contains(container)) {
                        document.body.removeChild(container);
                    }
                }, 300);
            }, duration);
        }
    };

    // Loading State
    shopify.loading = function(show) {
        var overlay = document.getElementById('shopify-loading-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'shopify-loading-overlay';
            overlay.innerHTML = '<div class="shopify-spinner"></div>';
            document.body.appendChild(overlay);
        }

        if (show) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    };

    // Modal Management
    shopify.modal = {
        show: function(modalId) {
            var modal = document.getElementById(modalId);
            if (modal && modal.tagName === 'UI-MODAL') {
                modal.setAttribute('open', '');
                
                // Close on backdrop click
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        shopify.modal.hide(modalId);
                    }
                });
            }
        },
        hide: function(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.removeAttribute('open');
            }
        }
    };

    // Resource Picker
    shopify.resourcePicker = function(options) {
        options = options || {};
        return new Promise(function(resolve) {
            var type = options.type || 'product';
            var selectionIds = options.selectionIds || [];
            
            // Create modal
            var modalId = 'shopify-resource-picker-modal-' + Date.now();
            var modal = document.createElement('ui-modal');
            modal.id = modalId;
            modal.setAttribute('open', '');
            
            var preselectedText = selectionIds.length > 0 
                ? selectionIds.map(function(s) { return s.id; }).join(', ')
                : '';

            var resourceType = type === 'product' ? 'Product' : 'Collection';
            modal.innerHTML = 
                '<div class="modal-content">' +
                    '<div class="shopify-resource-picker-modal">' +
                        '<div class="shopify-resource-picker-header">' +
                            '<h3>Select ' + resourceType + '</h3>' +
                            '<p>Enter ' + type + ' ID or leave empty for simulation</p>' +
                            '<input type="text" class="shopify-resource-picker-input" ' +
                                'placeholder="gid://shopify/' + resourceType + '/123456" ' +
                                'value="' + preselectedText + '" />' +
                        '</div>' +
                        '<div class="shopify-resource-picker-actions">' +
                            '<button class="shopify-btn" onclick="window.__shopifyResourcePickerCancel(\'' + modalId + '\')">Cancel</button>' +
                            '<button class="shopify-btn shopify-btn-primary" onclick="window.__shopifyResourcePickerSelect(\'' + modalId + '\', \'' + type + '\')">Select</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            
            document.body.appendChild(modal);

            // Store resolver for callbacks
            window.__shopifyResourcePickerResolvers = window.__shopifyResourcePickerResolvers || {};
            window.__shopifyResourcePickerResolvers[modalId] = resolve;

            // Handle backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    window.__shopifyResourcePickerCancel(modalId);
                }
            });
        });
    };

    // Global handlers for resource picker
    window.__shopifyResourcePickerSelect = function(modalId, type) {
        var modal = document.getElementById(modalId);
        var input = modal.querySelector('.shopify-resource-picker-input');
        var value = input.value.trim();
        
        var result;
        if (value) {
            // User entered an ID - redirect to admin
            var id = value.replace('gid://shopify/', '').replace(/\//g, '/');
            var urlPath = type === 'product' ? 'products' : 'collections';
            var numericId = id.match(/\d+$/);
            numericId = numericId ? numericId[0] : '';
            
            if (numericId) {
                window.open(shopify.config.shopUrl + '/admin/' + urlPath + '/' + numericId, '_blank');
            }
            
            result = [{
                id: value.indexOf('gid://') === 0 ? value : 'gid://shopify/' + (type === 'product' ? 'Product' : 'Collection') + '/' + numericId
            }];
        } else {
            // Simulate selection
            result = [{
                id: 'gid://shopify/' + (type === 'product' ? 'Product' : 'Collection') + '/' + Math.floor(Math.random() * 1000000),
                title: 'Sample ' + type + ' ' + Math.floor(Math.random() * 100)
            }];
        }

        var resolver = window.__shopifyResourcePickerResolvers[modalId];
        if (resolver) {
            resolver(result);
            delete window.__shopifyResourcePickerResolvers[modalId];
        }

        modal.remove();
    };

    window.__shopifyResourcePickerCancel = function(modalId) {
        var modal = document.getElementById(modalId);
        var resolver = window.__shopifyResourcePickerResolvers[modalId];
        
        if (resolver) {
            resolver([]);
            delete window.__shopifyResourcePickerResolvers[modalId];
        }

        modal.remove();
    };

    // Fetch Interceptor
    var originalFetch = window.fetch;
    window.fetch = function(url, options) {
        // Check if URL starts with "shopify:"
        if (typeof url === 'string' && url.indexOf('shopify:') === 0) {
            // Replace "shopify:" with shop URL
            url = shopify.config.appUrl + '/admin/shopify-graph?shop=' + encodeURIComponent(shopify.config.shopUrl);
        }
        // add shopify access token header
        options = options || {};
        options.headers = options.headers || {};
        // append csrf token header
        options.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        options.headers['Content-Type'] = 'application/json';
        return originalFetch(url, options);
    };

    // Define custom elements
    if (typeof customElements !== 'undefined') {
        if (!customElements.get('ui-modal')) {
            customElements.define('ui-modal', class extends HTMLElement {
                constructor() {
                    super();
                }

                show() {
                    this.setAttribute('open', '');
                }

                hide() {
                    this.removeAttribute('open');
                }
            });
        }

        if (!customElements.get('ui-title-bar')) {
            customElements.define('ui-title-bar', class extends HTMLElement {
                constructor() {
                    super();
                }
            });
        }
    }

    console.log('✅ Shopify App Bridge Mock loaded');
    console.log('📝 Configure: shopify.config.shopUrl = "' + shopify.config.shopUrl + '"');

})();