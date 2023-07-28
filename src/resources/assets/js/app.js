const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
window.$GLOBALS = {
    shop: urlParams.get('shop'),
    host: urlParams.get('host'),
    csrfToken: document.head.querySelector("[name~=csrf-token][content]").content,
    push : (path, param) => {
        if(typeof param == 'object'){
            param = {...param,...window.router.currentRoute.value.query}
        }else{
            param = window.router.currentRoute.value.query
        }
        if(window.router.getRoutes().find((route) => route.name === path))
            return window.router.push({name: path, params: param})
        return window.router.push({path: path, query: param})
    }, 
    processRequest : async (url, data, isImageRequest) => {
        const myHeaders = new Headers();
        myHeaders.append("Accept", "application/json");
        myHeaders.append("Content-Type", "application/json");
        myHeaders.append("X-CSRF-TOKEN", window.$GLOBALS.csrfToken);
        myHeaders.append("shop", window.$GLOBALS.shop);
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
    showToast : (msg,isError,subscribeFun,clearFun) => {
        isError = typeof isError == 'undefined' ? false : true
        let toastOptions = {
            message: msg,
            duration: 5000,
            isError: isError,
        };
        if(appBridgeEnabled == "1"){
            const toastNotice = actions.Toast.create(app, toastOptions);
            toastNotice.subscribe(actions.Toast.Action.SHOW, data => {
                if(typeof subscribeFun != 'undefined'){
                    subscribeFun()
                }
            });
            toastNotice.subscribe(actions.Toast.Action.CLEAR, data => {
                if(typeof clearFun != 'undefined'){
                    clearFun()
                }
            });
            toastNotice.dispatch(actions.Toast.Action.SHOW);
        }else{
            alert(msg);
            if(typeof subscribeFun != 'undefined'){
                subscribeFun()
            }
            if(typeof clearFun != 'undefined'){
                clearFun()
            }
        }
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
if(appBridgeEnabled == "1"){
    window.AppBridge = window['app-bridge'];
    var app = window.AppBridge.createApp({
        apiKey: apiKey,
        host: window.$GLOBALS.host,
        forceRedirect: isEmbeddedApp=="1" ? true : false,
    });
    var actions = window.AppBridge.actions;
    // var createApp = window.AppBridge.default;
    // var utils = window['app-bridge-utils'];
    // var getSessionToken = utils.getSessionToken;
    // console.log(getSessionToken,'getSessionToken');

    // app.dispatch(
    //     actions.Redirect.toRemote({
    //         url: '{{route("msdev2.shopify.plan.index")}}'
    //     })
    // );

    // const modalOptions = {
    //     title: 'My Modal',
    //     message: 'Hello world!',
    //     size: actions.Modal.Size.small,
    // };
    // const myModal = actions.Modal.create(app, modalOptions);
    // myModal.dispatch(actions.Modal.Action.OPEN);
    
}
function showToast(msg,isError,subscribeFun,clearFun){
    return window.showToast(msg,isError,subscribeFun,clearFun)
}