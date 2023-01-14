const ws = new WebSocket('wss://dasnerdwork.net:8081/');
$.get( "https://clash.dasnerdwork.net/clashapp/data/patch/version.txt", function( data ) {
  var currentpatch = data;
  
    ws.onopen = (event) => { // Do this on client opening the webpage
        ws.send("Hello there.");
    };

    ws.onmessage = (event) => { // Do this when the WS-Server sends a message to client
        if(Array.from(event.data)[0] == "{"){
            var statusJson = JSON.parse(event.data);
            if(statusJson.status == "ElementAlreadyInArray"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Dieser Champion wurde bereits ausgewählt.\n");
            } else if(statusJson.status == "MaximumElementsExceeded"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Die maximale Anzahl an ausgewählten Champions wurde erreicht.\n");
            } else if(statusJson.status == "CodeInjectionDetected"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] WARNUNG: Dieser Code Injection Versuch wurde geloggt und dem Administrator mitgeteilt.\n");
            } else if(statusJson.status == "InvalidTeamID"){
                var d = new Date();
                alert("[" + d.toLocaleTimeString() + "] Die Anfrage für diese Team ID ist nicht gültig.\n");
            } else if (statusJson.status == "FileDidNotExist") {
                window.location.reload();
            } else {
                html = '<div class="selected-ban-champion fullhd:w-16 twok:w-24">'+
                '<div class="hoverer group" draggable="true" onclick="selected_ban_champion(this.parentElement)">'+
                '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + statusJson.champid + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + statusJson.champid + '.webp" loading="lazy">'+
                '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp"></div>'+
                '<span class="selected-ban-caption" style="display: block;">' + statusJson.champname + '</span>'+
                '</div>';
                selectedBans.innerHTML += html;
            }
        } else {
            console.log(event.data);
        }
    }

    ws.onclose = (event) => { // Do this when the WS-Server stops
        clearTimeout(this.pingTimeout);
    }


    $('document').ready(function() { 
        $(".ban-hoverer").click(function() {
            var name = this.parentElement.getElementsByTagName("span")[0].innerText;
            var id = this.parentElement.getElementsByTagName("img")[0].dataset.id;
            var sendInfo =  {
                champname: name,
                champid: id,
                teamid: window.location.pathname.split("/team/")[1],
                request: "add"
            };
            // var buf = Buffer.from(sendInfo.toString());
            ws.send(JSON.stringify(sendInfo))
            // console.log(JSON.stringify(sendInfo));
        });
    });
    // ws.on('open', function open() { 
        //   ws.send('something');
        // });
        
        // ws.on('message', function message(data) {
            //   console.log('received: %s', data);
            // });
});