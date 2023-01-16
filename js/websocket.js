const ws = new WebSocket('wss://dasnerdwork.net:8081/');
$.get( "https://clash.dasnerdwork.net/clashapp/data/patch/version.txt", function( data ) {
  var currentpatch = data;
  
    ws.onopen = (event) => { // Do this on client opening the webpage
        let sendInfo =  {
            teamid: window.location.pathname.split("/team/")[1],
            request: "firstConnect"
        };
        ws.send(JSON.stringify(sendInfo))
    };

    ws.onmessage = (event) => { // Do this when the WS-Server sends a message to client
        if(Array.from(event.data)[0] == "{"){
            var messageAsJson = JSON.parse(event.data);
            var selectedBans = document.getElementById("selectedBans");
            if(messageAsJson.hasOwnProperty("SuggestedBans")){
                var status = 0;
                console.log(messageAsJson);
                if(messageAsJson["Status"] > status){
                    status = messageAsJson["Status"];
                    var html = "";
                    for (const element of messageAsJson["SuggestedBans"]) {
                    html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24">'+
                                '<div class="hoverer group" draggable="true" onclick="removeFromFile(this.parentElement);">'+
                                '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.webp" loading="lazy">'+
                                '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp"></div>'+
                                '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                            '</div>';
                    }
                    selectedBans.innerHTML = html;
                    // console.log(html)
                    makeDragDroppable();
                }
            } else {
                if(messageAsJson.status == "ElementAlreadyInArray"){
                    var d = new Date();
                    alert("[" + d.toLocaleTimeString() + "] Dieser Champion wurde bereits ausgewählt.\n");
                } else if(messageAsJson.status == "MaximumElementsExceeded"){
                    var d = new Date();
                    alert("[" + d.toLocaleTimeString() + "] Die maximale Anzahl an ausgewählten Champions wurde erreicht.\n");
                } else if(messageAsJson.status == "CodeInjectionDetected"){
                    var d = new Date();
                    alert("[" + d.toLocaleTimeString() + "] WARNUNG: Dieser Code Injection Versuch wurde geloggt und dem Administrator mitgeteilt.\n");
                } else if(messageAsJson.status == "InvalidTeamID"){
                    var d = new Date();
                    alert("[" + d.toLocaleTimeString() + "] Die Anfrage für diese Team ID ist nicht gültig.\n");
                } else if (messageAsJson.status == "FileDidNotExist") {
                    window.location.reload();
                } else if (messageAsJson.status == "Success") {
                    // if successful
                } else if (messageAsJson.status == "Update") {
                    // if update
                }
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
            let sendInfo =  {
                champname: name,
                champid: id,
                teamid: window.location.pathname.split("/team/")[1],
                request: "add"
            };
            ws.send(JSON.stringify(sendInfo))
        });
    });
});

$('document').ready(function() { 
    $(".hoverer").click(function() {
        var name = this.parentElement.getElementsByTagName("span")[0].innerText;
        var id = this.parentElement.getElementsByTagName("img")[0].dataset.id;
        let sendInfo =  {
            champname: name,
            champid: id,
            teamid: window.location.pathname.split("/team/")[1],
            request: "remove"
        };
        ws.send(JSON.stringify(sendInfo))
    });
});

function removeFromFile(el){
    var name = el.getElementsByTagName("span")[0].innerText;
    var id = el.getElementsByTagName("img")[0].dataset.id;
    let sendInfo =  {
        champname: name,
        champid: id,
        teamid: window.location.pathname.split("/team/")[1],
        request: "remove"
    };
    ws.send(JSON.stringify(sendInfo))
}

function modifyTeamRating(rating, hash){
    const d = new Date();
    d.setDate(d.getDate() + 365);
    let expires = "expires="+ d.toUTCString();
    let teamid = window.location.pathname.split("/team/")[1]
    document.cookie = teamid+"="+rating + ";" + expires + ";";
    let sendInfo =  {
        hash: hash,
        rating: rating,
        teamid: teamid,
        request: "rate"
    };
    ws.send(JSON.stringify(sendInfo))
  }


























// DROPPABLE

function makeDragDroppable(){
    let draggables = document.getElementsByClassName('hoverer')
    
    for (i = 0; i < draggables.length; i++) {
      draggables[i].addEventListener('dragstart', dragStart)
      draggables[i].addEventListener('drop', dropped)
      draggables[i].addEventListener('dragenter', cancelDefault)
      draggables[i].addEventListener('dragover', dragOver)
    }

    function dragStart (e) {
      e.dataTransfer.setData('fromName', e.srcElement.previousSibling.dataset.id);
      e.dataTransfer.setData('isDraggable', e.srcElement.parentElement.classList); // set event data "isDraggable" to the class name "hoverer" of the parent element
      e.dataTransfer.setDragImage(e.srcElement.previousSibling, 24, 24);
      return oldIndex = getChildIndex(e.srcElement.parentElement.parentElement); // returning so function dropped is able to acces the old index variable
    }

    function dropped (e) {
        var fromName = e.dataTransfer.getData('fromName');
        var toName = e.srcElement.previousSibling.dataset.id;
        cancelDefault(e);
        
        // get new and old index from dragStart return
        // var newIndex = getChildIndex(e.toElement.parentElement.parentElement) // Javascript swap of elements -> not necessary due to ajax
        // get parent as variable
        // var selectedBans = e.toElement.parentElement.parentElement.parentElement; // Javascript swap of elements -> not necessary due to ajax
        
        if(e.dataTransfer.getData('isDraggable') != "hoverer group"){ // get the "isDraggable" event data, if class of dragged element == hoverer continue, else cancel
            cancelDefault(e);
      } else {
        // console.log("Moving index "+oldIndex+" to "+newIndex);
        // insert the dropped item at new place
        // if (newIndex < oldIndex) {
        //   selectedBans.insertBefore(selectedBans.children[oldIndex], selectedBans.children[newIndex]);
        // } else {
        //   selectedBans.insertBefore(selectedBans.children[oldIndex], selectedBans.children[newIndex].nextElementSibling);
        // }
        let sendInfo =  {
            fromName: fromName,
            toName: toName,
            teamid: window.location.pathname.split("/team/")[1],
            request: "swap"
        };
        ws.send(JSON.stringify(sendInfo));
        }
    }

    function dragOver (e) {
        e.dataTransfer.dropEffect = "move";
        cancelDefault(e);
    }

    function cancelDefault (e) {
        e.preventDefault()
        e.stopPropagation()
        return false
    }
}
// END DROPPABLE