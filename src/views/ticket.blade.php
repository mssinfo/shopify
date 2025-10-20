@extends('msdev2::layout.master')
@section('css', 'ticket')
@section('content')
<header>
    <h1>Create a ticket</h1>
    <h2>Please provide the details of the problem</h2>
</header>
<section>
<article>
    <form method="post" class="card columns " id="TicketForm">
        <div class="row">
            <label for="email">Email*</label>
            <input type="email" name="email" id="email" value="{{$shop->detail['email']}}" />
        </div>
        <div class="row">
            <label for="subject">Subject*</label>
            <input type="text" name="subject" id="subject" />
        </div>
        <div class="row">
            <label for="category">Support Category*</label>
            <select name="category" id="category">
                <option value="">Please select</option>
                <option value="App Features Inquiry">App Features Inquiry</option>
                <option value="Installation/Theme Integration Request">Installation/Theme Integration Request</option>
                <option value="App Setup/Configuration Request">App Setup/Configuration Request</option>
                <option value="Styling and Customization">Styling and Customization</option>
                <option value="New Feature Request">New Feature Request</option>
                <option value="Expert Advice">Expert Advice</option>
                <option value="App Performance Feedback">App Performance Feedback</option>
                <option value="Report a Bug">Report a Bug</option>
                <option value="Plan or Billing Related Inquiry">Plan or Billing Related Inquiry</option>
                <option value="Refund/Charge Cancellation Request">Refund/Charge Cancellation Request</option>
                <option value="App Uninstallation Help">App Uninstallation Help</option>
                <option value="Preview Request - Mobile App">Preview Request - Mobile App</option>
                <option value="Review Notification">Review Notification</option>
                <option value="Premium Support">Premium Support</option>
                <option value="Marketing Emails">Marketing Emails</option>
                <option value="Collaborator Request">Collaborator Request</option>
                <option value="Other">Other</option>
                <option value="Question">Question</option>
                <option value="Incident">Incident</option>
                <option value="Task">Task</option>
                <option value="Problem">Problem</option>
                <option value="Installation Request">Installation Request</option>
            </select>
        </div>
        <div class="row">
            <label for="detail">Details*</label>
            <textarea id="detail" name="detail"></textarea>
        </div>
        <div class="row">
            <label for="file">Attach File</label>
            <input type="file" id="file" multiple>
            <span id="js-error" style="color:red;"></span>
            <div id="file-list-display" class="mt-20"></div>
        </div>
        <div class="row">
            <label for="password">Store Password (if password protected)</label>
            <input type="text" name="password" id="password" />
        </div>
        <div class="row">
            <label for="priority">Priority</label>
            <select name="priority" id="priority" >
                <option value="1">Low</option>
                <option value="2">Medium</option>
                <option value="3">High</option>
                <option value="4">Critical</option>
            </select>
        </div>
        <div class="row" id="statusTicket"></div>
        <button type="submit" id="ticketSubmit">Submit</button>
        <button class="secondary" id="ticketReset" type="reset">Reset</button>
    </form>    
</article>
</section>
@endsection
@section("scripts")
<script>
(function () { //defining filecatcher 
    var inputFile = document.getElementById('file'), 
    fileListDisplay = document.getElementById('file-list-display'),
    form = document.getElementById("TicketForm"),
    status = document.getElementById("statusTicket"),
    errorContainer = document.getElementById("js-error"),
    allowedExtensions = ["jpg", "jpeg", "png", "pdf"],
    maximumAllowedDimensions = [1920, 1920], files = [], renderFileList, sendFile; 
    form.addEventListener('submit', function (evnt) {
        status.innerHTML = 'Loading...';
        evnt.preventDefault();
        sendFile();
        return false
    });
 
    inputFile.addEventListener('change', function (evnt) {
        if (!event.target.files) {
            return;
        }
        const selectedFiles = Array.from(event.target.files);
        const selectedFilesPreview = selectedFiles.map((fileItem) => {
            let size = fileItem.size;
            let sizeSuffixIndex = 0;
            const sizeSuffix = ["B", "KB", "MB", "GB"];
            while (size / 1024 > 1) {
                size = size / 1024;
                sizeSuffixIndex++;
            }
            size = Number(size.toFixed(1));
            const name = fileItem.name.split(".").slice(0, -1).join(".");
            const type = fileItem.type.split("/")[0];
            const extension = fileItem.type.split("/")[1];
            //const extension = filesItem.name.split('.').pop();
            const ensureAllowExtensions = allowedExtensions.find(
                (allowedExtensionsItem) => allowedExtensionsItem === extension
            );

            return new Promise((resolve, reject) => {
                if (!ensureAllowExtensions) {
                    reject(`Arquivos permitidos: ${allowedExtensions.join(", ")}`);
                }
                if (type === "image") {
                    const image = new Image();
                    image.onload = () => {
                        const { width, height } = image;
                        if (
                            width > maximumAllowedDimensions[0] ||
                            height > maximumAllowedDimensions[1]
                        ) {
                            reject(
                            `Dimensões máxima permitida: ${maximumAllowedDimensions.join(
                                "x"
                            )}`
                            );
                        }
                        fileToBase64({
                            width,
                            height
                        });
                    };
                    image.onerror = () => {
                        reject("Image Erro");
                    };
                    image.src = window.URL.createObjectURL(fileItem);
                } else {
                    fileToBase64();
                }
                function fileToBase64(props) {
                    const reader = new FileReader();
                    reader.readAsDataURL(fileItem);
                    reader.onloadend = function () {
                        const base64data = reader.result;
                        let result = {
                            name,
                            type,
                            extension,
                            source: base64data,
                            size,
                            sizeSuffix: sizeSuffix[sizeSuffixIndex],
                        };
                        if (props) {
                            const { width, height } = props;
                            result = { ...result, width, height };
                        }
                        resolve(result);
                    };
                }
            });
        });

        Promise.all(selectedFilesPreview).then((values) => {
            files = [...files, ...values];
            renderFiles();
        }).catch((data) => {
            renderError(data);
        }).then(() => {
            inputFile.type = "text";
            inputFile.type = "file";
        });
    });
    function renderFiles() {
        fileListDisplay.innerHTML = "";
        files.map((filesItem) => {
            if (filesItem.type === "image") {
            const image = document.createElement("img");
            image.src = filesItem.source;
            image.alt = filesItem.name;
            image.height = 64;
            image.style.float = "right";
            image.style.marginLeft = "10px"
            const info = document.createElement("div");
            info.classList.add('alert');
            info.innerHTML = `<dl>
                <dt>${filesItem.name}.${filesItem.extension} </dt>
                <dd>size: ${filesItem.size} ${filesItem.sizeSuffix} | type: ${filesItem.type} </dd>
                </dl>
            `;
            fileListDisplay.appendChild(image);
            fileListDisplay.appendChild(info);
            }
        });
    }

    function renderError(error) {
        errorContainer.innerHTML = error;
    }

    sendFile = function () {
        let formEntries = new FormData(form).entries();
        const json = Object.assign(...Array.from(formEntries, ([x,y]) => ({[x]:y})));
        if(files.length > 0) json['files'] = files
        console.log(json);
        let request = window.$GLOBALS.processRequest('POST ticket', json)
        request.then(result => {
            status.innerHTML = result.message
            status.classList.add("alert")
            if(result.status == "success"){
                status.classList.remove("error")
                status.classList.add("success")
                document.getElementById("ticketReset").click();
                fileListDisplay.innerHTML = "";
                errorContainer.innerHTML = "";
            }else{
                status.classList.remove("success")
                status.classList.add("error")
            }
        })
    };
})();
    </script>
@endsection