// This file contains the necessary POST for the QR Code generation via PHP in /accounts/qr-codes.php
function postAjax(url, data, success) {
    var params = typeof data == 'string' ? data : Object.keys(data).map(
            function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
        ).join('&');

    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    xhr.open('POST', url);
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200) { success(xhr.responseText); }
    };
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
    return xhr;
}

function getQRCode(){
    postAjax('../qr-codes.php', { twofa: 'text' }, function(data){ 
        let existingQR = document.getElementById("qrcode");
        if(existingQR == null){
            let desc = document.getElementById("2fa-desc");
            let qrcode = document.createElement("img");
            qrcode.setAttribute("src", "data:image/webp;base64,"+data);
            qrcode.setAttribute("class", "block my-8 mx-auto w-3/4")
            qrcode.setAttribute("id", "qrcode");
            desc.appendChild(qrcode);
        }
    });
}