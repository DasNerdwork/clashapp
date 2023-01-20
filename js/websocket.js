const ws = new WebSocket('wss://dasnerdwork.net:8081/');
$.get( "https://clash.dasnerdwork.net/clashapp/data/patch/version.txt", function( data ) {
  var currentpatch = data;
  var executeOnlyOnce = true;
  
    ws.onopen = (event) => { // Do this on client opening the webpage
        if (document.getElementById("highlighter") != null) {
            var name = document.getElementById("highlighter").innerText
        } else {
            var name = "";
        }
        let sendInfo =  {
            teamid: window.location.pathname.split("/team/")[1],
            name: name,
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
                // console.log(messageAsJson);
                if(messageAsJson["Status"] > status){
                    status = messageAsJson["Status"];
                    var html = "";
                    let animateTimer = 0;
                    for (const element of messageAsJson["SuggestedBans"]) {
                        if(executeOnlyOnce){
                            html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24 opacity-0" style="animation: .5s ease-in-out '+animateTimer+'s 1 fadeIn; animation-fill-mode: forwards;">'+
                                    '<div class="hoverer group" draggable="true" onclick="removeFromFile(this.parentElement);">'+
                                    '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.webp">'+
                                    '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp"></div>'+
                                    '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                                    '</div>';
                            animateTimer += 0.1;
                            
                        } else {
                            html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24">'+
                                    '<div class="hoverer group" draggable="true" onclick="removeFromFile(this.parentElement);">'+
                                    '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.webp">'+
                                    '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp"></div>'+
                                    '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                                    '</div>';
                        }
                    }
                    executeOnlyOnce = false;
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
                } else if (messageAsJson.status == "FirstConnect") {
                    if (document.getElementById("highlighter") != null) {
                        let nameElement = document.getElementById("highlighter");
                        nameElement.classList.add("underline","decoration-"+messageAsJson.color);
                        // document.getElementById("highlighterAfter") <- // insert before this element
                    } else {
                        const newParentDiv = document.createElement("div");
                        const newImg = document.createElement("img");
                        const newSpan = document.createElement("span");
                        // newParentDiv.classList.add("flex","justify-center","items-center","px-4");
                        newParentDiv.style.marginTop = "-13px";
                        switch (messageAsJson.name){
                            case "Krug": case "Wolf":
                                newParentDiv.style.marginLeft = "-5.75rem";
                                break;
                            case "Nashor": case "Herald": case "Minion": case "Raptor": case "Gromp":
                                newParentDiv.style.marginLeft = "-6.75rem";
                                break;
                            case "Sentinel": case "Scuttler":
                                newParentDiv.style.marginLeft = "-7rem";
                                break;
                            case "Brambleback":
                                newParentDiv.style.marginLeft = "-9rem";
                                break;
                            default:
                                newParentDiv.style.marginLeft = "-9rem";
                        }
                        newParentDiv.setAttribute("onmouseover", "showIdentityNotice(true)");
                        newParentDiv.setAttribute("onmouseout", "showIdentityNotice(false)");
                        newImg.src="/clashapp/data/misc/monsters/"+messageAsJson.name.toLowerCase()+".webp"
                        newImg.width = "32";
                        newImg.classList.add("align-middle","mr-2.5","no-underline","inline-flex");
                        newSpan.id = "highlighter";
                        newSpan.classList.add("underline","decoration-2","decoration-"+messageAsJson.color);
                        newSpan.style.textDecorationSkipInk = "none";
                        newSpan.innerText = messageAsJson.name;
                        newParentDiv.appendChild(newImg);
                        newParentDiv.appendChild(newSpan);
                        document.getElementById("highlighterAfter").insertBefore(newParentDiv, null);
                    }
                } else if (messageAsJson.status == "Message"){
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color);
                }
            }
        } else {
            addHistoryMessage(event.data);
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

function showIdentityNotice(boolean){
    if(boolean){
        document.getElementById("identityNotice").classList.replace("opacity-0","opacity-100");
    } else {
        document.getElementById("identityNotice").classList.replace("opacity-100","opacity-0");
    }
}

function addHistoryMessage(message){
    const historyContainer = document.getElementById("historyContainer");
    const textMessage = document.createElement("span");
    textMessage.innerText = message;
    textMessage.classList.add("text-[#333344]");
    historyContainer.insertBefore(textMessage, historyContainer.firstChild.nextSibling);
}

function addCustomHistoryMessage(message, name, color){
const historyContainer = document.getElementById("historyContainer");
    const textMessage = document.createElement("span");
    // const highlighterColor = document.getElementById("highlighter").classList.split('decoration-')[2];
    textMessage.innerHTML = "<span class='text-"+color+"'>"+name+"</span> "+message;
    historyContainer.insertBefore(textMessage, historyContainer.firstChild.nextSibling);
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
        let sendInfo =  { //TODO: Add change fromName and toName to fromID and toID + add catch for champ names instead of IDs for return message
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
