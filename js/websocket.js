// This file contains every necessary websocket, ban element, drag & drop function and more for the team pages to work properly
var executeOnlyOnce = true;
function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
      return;
    }
    document.addEventListener('DOMContentLoaded', fn);
  }
  
const ws = new WebSocket('wss://websocket.dasnerdwork.net/');

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
                fetch("https://clash.dasnerdwork.net/clashapp/data/patch/version.txt")
                .then(response => { return response.text();
                }).then(currentpatch => {
                    
                    for (const element of messageAsJson["SuggestedBans"]) {
                        if(executeOnlyOnce){
                            html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24 opacity-0" style="animation: .5s ease-in-out '+animateTimer+'s 1 fadeIn; animation-fill-mode: forwards;">'+
                                        '<div class="hoverer group" draggable="true" onclick="removeFromFile(this.parentElement);">'+
                                            '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.webp">'+
                                            '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp">'+
                                        '</div>'+
                                    '<span class="selected-ban-caption block">' + element["name"] + '</span>'+
                                    '</div>';
                            animateTimer += 0.1;
                            
                        } else {
                            if(!(selectedBans.innerHTML.includes("/champion/" + element["id"] + ".webp"))){
                                html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24" style="animation: .1s ease-in-out 0s 1 slideIn; animation-fill-mode: forwards;">';  
                            } else {
                                html += '<div class="selected-ban-champion fullhd:w-16 twok:w-24">';
                            }
                            html += '<div class="hoverer group" draggable="true" onclick="removeFromFile(this.parentElement);">'+
                                        '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.webp">'+
                                        '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/RemovalOverlay.webp">'+
                                    '</div>'+
                                '<span class="selected-ban-caption block">' + element["name"] + '</span>'+
                                '</div>';
                        }
                    }
                    executeOnlyOnce = false;
                    selectedBans.innerHTML = html;
                    makeDragDroppable();
                });
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
                    nameElement.classList.add("underline","text-"+messageAsJson.color+"/100");
                    // document.getElementById("highlighterAfter") <- // insert before this element
                } else {
                    const newParentDiv = document.createElement("div");
                    const newImg = document.createElement("img");
                    const newSpan = document.createElement("span");
                    const newP = document.createElement("p");
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
                    newSpan.setAttribute("onmouseover", "showIdentityNotice(true)");
                    newSpan.setAttribute("onmouseout", "showIdentityNotice(false)");
                    newParentDiv.classList.add("z-20","w-36","h-8");
                    newImg.src="/clashapp/data/misc/monsters/"+messageAsJson.name.toLowerCase()+".webp"
                    newImg.width = "32";
                    newImg.classList.add("align-middle","mr-2.5","no-underline","inline-flex");
                    newP.id = "highlighter";
                    newP.classList.add("inline", "underline","decoration-2","text-"+messageAsJson.color+"/100");
                    newP.style.textDecorationSkipInk = "none";
                    newSpan.classList.add("text-white");
                    newSpan.innerText = messageAsJson.name;
                    newP.appendChild(newSpan);
                    newParentDiv.appendChild(newImg);
                    newParentDiv.appendChild(newP);
                    document.getElementById("highlighterAfter").insertBefore(newParentDiv, null);
                    const identityNotice = document.getElementById("identityNotice");
                    identityNotice.setAttribute("onmouseover", "showIdentityNotice(true)");
                    identityNotice.setAttribute("onmouseout", "showIdentityNotice(false)");
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

function addToFile(el){
    var name = el.getElementsByTagName("span")[0].innerText;
    var id = el.getElementsByTagName("img")[0].dataset.id;
    let sendInfo =  {
        champname: name,
        champid: id,
        teamid: window.location.pathname.split("/team/")[1],
        request: "add"
    };
    ws.send(JSON.stringify(sendInfo))
}

function removeFromFile(el){
    var name = el.getElementsByTagName("span")[0].innerText;
    var id = el.getElementsByTagName("img")[0].dataset.id;
    let sendInfo =  {
        champname: name,
        champid: id,
        teamid: window.location.pathname.split("/team/")[1],
        request: "remove"
    };
    ws.send(JSON.stringify(sendInfo));
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
        document.getElementById("identityNotice").classList.replace("hidden","block");
    } else {
        document.getElementById("identityNotice").classList.replace("block","hidden");
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
    textMessage.innerHTML = "<span class='text-"+color+"/100'>"+name+"</span> "+message;
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
      e.dataTransfer.setData('fromID', e.srcElement.previousSibling.dataset.id);
      e.dataTransfer.setData('fromName', e.srcElement.parentElement.parentElement.getElementsByTagName("span")[0].innerText);
      e.dataTransfer.setData('isDraggable', e.srcElement.parentElement.classList); // set event data "isDraggable" to the class name "hoverer" of the parent element
      e.dataTransfer.setDragImage(e.srcElement.previousSibling, 24, 24);
      return oldIndex = getChildIndex(e.srcElement.parentElement.parentElement); // returning so function dropped is able to acces the old index variable
    }

    function dropped (e) {
        var fromID = e.dataTransfer.getData('fromID');
        var fromName = e.dataTransfer.getData('fromName');
        var toID = e.srcElement.previousSibling.dataset.id;
        var toName = e.srcElement.parentElement.parentElement.getElementsByTagName("span")[0].innerText;
        cancelDefault(e);
                
        if(e.dataTransfer.getData('isDraggable') != "hoverer group"){ // get the "isDraggable" event data, if class of dragged element == hoverer continue, else cancel
            cancelDefault(e);
      } else {
        let sendInfo =  {
            fromName: fromName,
            fromID: fromID,
            toName: toName,
            toID: toID,
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