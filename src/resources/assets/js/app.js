const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
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
        if(localStorage.getItem("token")){
            myHeaders.append("Authorization", "Bearer "+atob(localStorage.getItem("token")));
        }
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
        if ( responseData.code == 401) {
            if(typeof responseData.data === 'undefined' || responseData.data ===  null){
                this.$store.commit('isLogin', false)
                localStorage.removeItem("token");
                myHeaders.delete('Authorization');
                window.location.reload();
                return false;
            }
            localStorage.setItem("token", btoa(responseData.data.token));
            myHeaders.delete('Authorization');
            myHeaders.append("Authorization", "Bearer "+responseData.data.token);
            return this.processRequest(url, data, isImageRequest)
        }
        return responseData;
    },
    showToast : (msg,isError) => {
        isError = typeof isError == 'undefined' || !isError ? false : true
        let toastOptions = {
            duration: 5000,
            isError: isError,
        };
        if(appBridgeEnabled == "1"){
            shopify.toast.show(msg, {
                duration: 5000,
            });
            const toastNotice = actions.Toast.create(app, toastOptions);
            toastNotice.dispatch(actions.Toast.Action.SHOW);
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
});