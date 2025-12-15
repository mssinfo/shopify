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
    styleElement.textContent = ``;
    
    // Wait for DOM to be ready before injecting styles
    if (document.head) {
        document.head.appendChild(styleElement);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            document.head.appendChild(styleElement);
        });
    }

    // Modal CSS moved to SCSS: src/resources/assets/scss/shopify.scss
    window.shopify = window.shopify || {};
    // ensure config exists to avoid runtime errors when reading properties
    window.shopify.config = window.shopify.config || { shopUrl: '', appUrl: '' };
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
            if (!modal) return;
            // lock background scroll
            if (!document.__shopify_original_overflow) {
                document.__shopify_original_overflow = document.body.style.overflow || '';
            }
            document.body.style.overflow = 'hidden';

            // Ensure content is wrapped in .modal-content so CSS targets it correctly
            try{
                if (!modal.querySelector(':scope > .modal-content')) {
                    var titleBar = modal.querySelector(':scope > ui-title-bar');
                    var childNodes = Array.prototype.slice.call(modal.childNodes);
                    var wrapper = document.createElement('div');
                    wrapper.className = 'modal-content';
                    // move titleBar first if present
                    if (titleBar) {
                        // ensure title text exists as an element so CSS can reliably show it
                        try{
                            var titleText = titleBar.querySelector('.title-text');
                            var titleValue = titleBar.getAttribute('title') || '';
                            if(!titleText){
                                // try to extract a text node (not inside buttons)
                                var extracted = '';
                                Array.prototype.slice.call(titleBar.childNodes).forEach(function(n){
                                    if(n.nodeType === Node.TEXT_NODE){
                                        var t = n.textContent.trim();
                                        if(t) extracted += (extracted? ' ' : '') + t;
                                        n.parentNode.removeChild(n);
                                    }
                                });
                                if(!titleValue && extracted) titleValue = extracted;
                                titleText = document.createElement('span');
                                titleText.className = 'title-text';
                                titleText.textContent = titleValue || '';
                                // insert at start
                                titleBar.insertBefore(titleText, titleBar.firstChild);
                            } else {
                                // sync attribute into element if needed
                                if(!titleText.textContent.trim() && titleBar.getAttribute('title')){
                                    titleText.textContent = titleBar.getAttribute('title');
                                }
                            }
                        }catch(e){/*ignore*/}
                        wrapper.appendChild(titleBar);
                    }
                    childNodes.forEach(function(n){
                        if (n === titleBar) return;
                        if (n === wrapper) return;
                        wrapper.appendChild(n);
                    });
                    // move modal to document.body to avoid ancestor CSS interference
                    if (modal.parentNode !== document.body) {
                        document.body.appendChild(modal);
                    }
                    modal.appendChild(wrapper);
                } else {
                    // ensure modal is at body level to avoid inherited layout rules
                    if (modal.parentNode !== document.body) {
                        document.body.appendChild(modal);
                    }
                }
            }catch(e){ /* defensive */ }

            // prefer using the element's API if available
            if (typeof modal.show === 'function') {
                modal.show();
            } else {
                modal.setAttribute('open', '');
            }

            // Attach a single backdrop click handler (use capture false)
            var handler = function(e) {
                if (e.target === modal) {
                    shopify.modal.hide(modalId);
                }
            };
            // remove existing to avoid duplicates
            try{ modal.removeEventListener('click', modal.__backdropHandler || handler); }catch(e){}
            modal.addEventListener('click', handler);
            modal.__backdropHandler = handler;
        },
        hide: function(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            if (typeof modal.hide === 'function') {
                modal.hide();
            } else {
                modal.removeAttribute('open');
            }
            // restore body overflow
            if (document.__shopify_original_overflow !== undefined) {
                document.body.style.overflow = document.__shopify_original_overflow;
                delete document.__shopify_original_overflow;
            }
            // cleanup handler
            if (modal.__backdropHandler) {
                try{ modal.removeEventListener('click', modal.__backdropHandler); }catch(e){}
                delete modal.__backdropHandler;
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
        // Check if URL starts with "shopify:" and we have an appUrl configured
        if (typeof url === 'string' && url.indexOf('shopify:') === 0 && shopify.config && shopify.config.appUrl) {
            // Replace "shopify:" with shop URL
            url = shopify.config.appUrl + '/admin/shopify-graph?shop=' + encodeURIComponent(shopify.config.shopUrl || '');
        }
        // add shopify access token header
        options = options || {};
        options.headers = options.headers || {};
        // append csrf token header if present
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta && csrfMeta.getAttribute) {
            var token = csrfMeta.getAttribute('content');
            if (token) options.headers['X-CSRF-Token'] = token;
        }
        options.headers['Content-Type'] = options.headers['Content-Type'] || 'application/json';
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
    try{
        console.log('📝 Configure: shopify.config.shopUrl = "' + (shopify.config && shopify.config.shopUrl ? shopify.config.shopUrl : '') + '"');
    }catch(e){ /* silent */ }

})();