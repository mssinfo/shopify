const queryStrings = window.location.search;
const urlParams = new URLSearchParams(queryStrings);
window.$GLOBALS = {
    shopName: urlParams.get('shop'),
    host: urlParams.get('host'),
    csrfToken: document.head.querySelector("[name~=csrf-token][content]").content,
    push : (path, param) => {
        if(typeof window.router == "undefined"){
            const currentURL = new URL(window.location.href);
            // Ensure the new URL includes the host
            const host = currentURL.host || window.location.host;
            const protocol = currentURL.protocol || window.location.protocol;
            const newUrlWithHost = protocol + '//' + host + '/'+ (path.startsWith('/') ? path.substring(1) : path);
            // Append current query parameters to the new URL
            const searchParams = currentURL.searchParams.toString();
            // Construct the query parameters string with the custom parameters
            let newUrlWithParams = newUrlWithHost;
            if (searchParams) {
                newUrlWithParams += '?' + searchParams;
            }
            if(typeof param != "undefined"){
                for (const [key, value] of Object.entries(param)) {
                newUrlWithParams += '&' + key + '=' + encodeURIComponent(value);
                }
            }
            // Append hash fragment to the new URL
            const hashFragment = currentURL.hash;
            const newUrlWithParamsAndHash = newUrlWithParams + hashFragment;
            // Redirect to the new URL with all current parameters and the custom parameters
            window.location.href = newUrlWithParamsAndHash;
            return false;
        }
        let query = window.router.currentRoute.value.query
        if(window.router.getRoutes().find((route) => route.name === path))
            return window.router.push({name: path, params: param, query: query})
        else {
           query = {...param,...window.router.currentRoute.value.query}
           return window.router.push({path: path, query: query})
        }
    }, 
    processRequest : async (url, data, isImageRequest) => {
        const myHeaders = new Headers();
        myHeaders.append("Accept", "application/json");
        myHeaders.append("Content-Type", "application/json");
        myHeaders.append("X-CSRF-TOKEN", window.$GLOBALS.csrfToken);
        myHeaders.append("shop", window.$GLOBALS.shopName);
        let urlInfo = url.split(' ')
        if(typeof isImageRequest !== 'undefined' && isImageRequest){
            myHeaders.delete('Accept');
            myHeaders.delete('Content-Type');
            myHeaders.append("Content-Type", "multipart/form-data");
        }
        let params = {
            method: urlInfo[0],
            headers: myHeaders
        }
        if(typeof data !== 'undefined' && data){
            if (urlInfo[0].toLowerCase()=="get" || urlInfo[0].toLowerCase()=="head") {
                urlInfo[1] = urlInfo[1]+'?'+(Object.keys(data).map(key => key + '=' + data[key]).join('&'));
            }else{
                params['body'] = JSON.stringify(data);
            }
        }
        const response = await fetch(window.URL_ROOT+'/'+urlInfo[1], params)
        let responseData = await response.json();
        return responseData;
    },
    showToast : (msg,isError) => {
        if(appBridgeEnabled == "1"){
            isError = typeof isError == 'undefined' || !isError ? false : true
            shopify.toast.show(msg, {
                duration: 5000,
                isError: isError,
            });
        }else{
            alert(msg)
        }
    },
    modal : (modalOptions, successFun, errorFun) =>{
        modalOptions = {...{
                title: 'title',
                size: actions.Modal.Size.small,
            },
            ...modalOptions
        };
        if(typeof successFun == 'function'){
            const okButton = actions.Button.create(app, {label: 'Ok'});
            const cancelButton = actions.Button.create(app, {label: 'Cancel'});
            modalOptions.footer = {
                buttons: {
                  primary: okButton,
                  secondary: [cancelButton],
                }
            }
            okButton.subscribe(actions.Button.Action.CLICK, () => {
                successFun()
                myModal.dispatch(actions.Modal.Action.CLOSE);
            });
            if(typeof errorFun == 'function'){
                cancelButton.subscribe(actions.Button.Action.CLICK, () => {
                    errorFun()
                    myModal.dispatch(actions.Modal.Action.CLOSE);
                });
            }else{
                cancelButton.subscribe(actions.Button.Action.CLICK, () => {
                    myModal.dispatch(actions.Modal.Action.CLOSE);
                });
            }
        }
        const myModal = actions.Modal.create(app, modalOptions);
        myModal.dispatch(actions.Modal.Action.OPEN);
    }
}
// Normalize path: trim trailing slash, ensure leading slash for comparison
function normalizePath(p){
    if(!p) return '/';
    try{
        // If a full URL passed, extract pathname
        var u = new URL(p, window.location.origin);
        p = u.pathname;
    }catch(e){ /* ignore */ }
    // remove trailing slash unless root
    if(p.length > 1 && p.endsWith('/')) p = p.slice(0, -1);
    return p;
}

function updateVueMenuActive(){
    // Select all navigational anchors inside nav (includes menu items and the Plan 'right' link)
    var links = document.querySelectorAll('nav a.vue_link');
    var current = normalizePath(window.location.pathname || '/');
    links.forEach(function(a){
        // skip logo link or placeholders
        if (a.classList.contains('logo')) return;
        var href = a.getAttribute('href') || a.getAttribute('data-href') || '';
        // skip empty or javascript links
        if(!href || href.trim() === '#' || href.trim().toLowerCase().startsWith('javascript:')) return;
        // skip absolute external links (different host)
        try{
            var testUrl = new URL(href, window.location.origin);
            if(testUrl.origin !== window.location.origin) return;
        }catch(e){ /* ignore, href might be relative */ }

        var target = normalizePath(href);
        var isActive = (current === target) || (target !== '/' && current.indexOf(target + '/') === 0);
        if(isActive){
            a.classList.add('active');
        } else {
            a.classList.remove('active');
        }
    });
}

// dispatch event when history methods are called
function hookHistory(){
    var push = history.pushState;
    history.pushState = function(){
        var ret = push.apply(this, arguments);
        window.dispatchEvent(new Event('locationchange'));
        return ret;
    };
    var replace = history.replaceState;
    history.replaceState = function(){
        var ret = replace.apply(this, arguments);
        window.dispatchEvent(new Event('locationchange'));
        return ret;
    };
    window.addEventListener('popstate', function(){
        window.dispatchEvent(new Event('locationchange'));
    });
}
function pingExternalLinks(){
    var links = document.querySelectorAll('nav a[data-external-host]');
    links.forEach(function(a){
        var host = a.getAttribute('data-external-host');
        if(!host) return;
        var testUrl = (location.protocol === 'https:' ? 'https://' : 'http://') + host + '/favicon.ico?ping=' + Date.now();
        var img = new Image();
        var handled = false;
        var t = setTimeout(function(){ if(handled) return; handled = true; a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; }, 2500);
        img.onload = function(){ if(handled) return; handled = true; clearTimeout(t); };
        img.onerror = function(){ if(handled) return; handled = true; clearTimeout(t); a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; };
        try{ img.src = testUrl; }catch(e){ handled = true; clearTimeout(t); a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; }
    });
}
document.addEventListener('DOMContentLoaded', function () {
    // Get all elements with the class "clickable"
    const clickableElements = document.querySelectorAll('.vue_link');
    // Add a click event listener to each clickable element
    clickableElements.forEach(function (element) {
        element.addEventListener('click', function (event) {
            // Handle the click event here
            // console.log(window.apps,window.router,element.getAttribute('href'));
            event.preventDefault();
            return window.$GLOBALS.push(element.getAttribute('href'));
            // You can perform any action or call a function here when the element is clicked
        });
    });
    // run once on load
    setTimeout(pingExternalLinks, 100);
    // Responsive menu toggle
    var _toggle = document.querySelector('nav .menu-toggle');
    var _nav = document.querySelector('nav');
    if (_toggle && _nav) {
        _toggle.addEventListener('click', function(e){
            var isOpen = _nav.classList.toggle('open');
            _toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        // close when clicking outside
        document.addEventListener('click', function(e){
            if (_nav.classList.contains('open') && !_nav.contains(e.target)) {
                _nav.classList.remove('open');
                _toggle.setAttribute('aria-expanded', 'false');
            }
        });
        // close when a link is clicked (helpful on mobile)
        Array.prototype.slice.call(document.querySelectorAll('nav a')).forEach(function(a){
            a.addEventListener('click', function(){ if (_nav.classList.contains('open')) { _nav.classList.remove('open'); _toggle.setAttribute('aria-expanded','false'); } });
        });
    }
});
if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
        hookHistory();
        updateVueMenuActive();
    });
} else {
    hookHistory();
    updateVueMenuActive();
}
// Also update when custom locationchange occurs
window.addEventListener('locationchange', function(){
    // small timeout to allow router to update url
    setTimeout(updateVueMenuActive, 10);
});
// ensure clicks on vue links update immediately
document.addEventListener('click', function(e){
    var a = e.target.closest && e.target.closest('a.vue_link');
    if(a){
        setTimeout(updateVueMenuActive, 10);
    }
});