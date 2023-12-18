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
        // console.log(messageAsJson);
        if(messageAsJson.hasOwnProperty("SuggestedBans")){
            var status = 0;
            // console.log(messageAsJson);
            if(messageAsJson["Status"] > status){
                status = messageAsJson["Status"];
                var html = "";
                let animateTimer = 0;
                fetch("https://clashscout.com/clashapp/data/patch/version.txt")
                .then(response => { return response.text();
                }).then(currentpatch => {
                    const championImagePromises  = messageAsJson["SuggestedBans"].map(element => {
                        return getVersionedUrl('/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.avif');
                    });
                    const removalOverlayPromise = getVersionedUrl('/clashapp/data/misc/RemovalOverlay.avif');
                    const allPromises = [...championImagePromises, removalOverlayPromise];

                    Promise.all(allPromises)
                        .then(versionedPaths => {
                            // Separate champion images and removal-overlay image paths
                            const championImagePaths = versionedPaths.slice(0, messageAsJson["SuggestedBans"].length);
                            const removalOverlayPath = versionedPaths.pop();
                        
                            championImagePaths.forEach((versionedPath, index) => {
                                const element = messageAsJson["SuggestedBans"][index];
                                const versionedChampImg = versionedPath;
                                const versionedRemovalOverlayImg = removalOverlayPath;
                            if(executeOnlyOnce){
                                html += '<div class="selected-ban-champion h-fit fullhd:w-16 twok:w-24 opacity-0" style="animation: .5s ease-in-out '+animateTimer+'s 1 fadeIn; animation-fill-mode: forwards;">'+
                                            '<div class="hoverer group' + (element.status === "locked" ? ' locked' : '') + '" ' + (element.status === "locked" ? 'draggable="false" ' : 'draggable="true" ') + 'onclick="' + (element.status === "locked" ? '' : 'removeFromFile(this.parentElement);') + '">'+
                                                '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="' + versionedChampImg + '" style="filter: ' + (element.status === "locked" ? 'grayscale(100%)' : 'none') + '" alt="A champion icon of the league of legends champion '+ element["name"]+'">'+
                                                '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 twok:-mt-14 opacity-0 group-hover:opacity-100' + (element.status === "locked" ? ' hidden' : '') + '" src="' + versionedRemovalOverlayImg + '" alt="Removal overlay icon in red">'+
                                            '</div>'+
                                        '<span class="selected-ban-caption block">' + element["name"] + '</span>'+
                                        '</div>';
                                animateTimer += 0.1;
                            } else {
                                if (!(selectedBans.innerHTML.includes("/champion/" + element["id"] + ".avif"))) {
                                    html += '<div class="selected-ban-champion h-fit fullhd:w-16 twok:w-24" style="animation: .1s ease-in-out 0s 1 slideIn; animation-fill-mode: forwards;">';
                                } else {
                                    html += '<div class="selected-ban-champion h-fit fullhd:w-16 twok:w-24">';
                                }
                                html += '<div class="hoverer group' + (element.status === "locked" ? ' locked' : '') + '" ' + (element.status === "locked" ? '' : ' draggable="true"') + '" ' + (element.status === "locked" ? '' : 'onclick="removeFromFile(this.parentElement);"') + '>'+
                                            '<img class="selected-ban-icon twok:max-h-14 fullhd:max-h-11" data-id="' + element["id"] + '" src="' + versionedChampImg + '" style="filter: ' + (element.status === "locked" ? 'grayscale(100%)' : 'none') + '" alt="A champion icon of the league of legends champion '+ element["name"]+'">'+
                                            '<img class="removal-overlay twok:max-h-14 fullhd:max-h-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100' + (element.status === "locked" ? ' hidden' : '') + '" src="' + versionedRemovalOverlayImg + '" alt="Removal overlay icon in red">'+
                                        '</div>'+
                                    '<span class="selected-ban-caption block">' + element["name"] + '</span>'+
                                    '</div>';
                            }
                        });
                        executeOnlyOnce = false;
                        selectedBans.innerHTML = html;
                        banSearchContainer = document.getElementById('banSearch').parentElement;
                        champSelectorContainer = document.getElementById('champSelect');
                        if(messageAsJson["SuggestedBans"].length >= 6){ // If our container exceeds one line of elements
                            banSearchContainer.classList.replace('twok:h-[16.5rem]', 'twok:h-[10.5rem]');
                            banSearchContainer.classList.replace('fullhd:h-[17rem]', 'fullhd:h-[12rem]');
                            champSelectorContainer.classList.replace('twok:h-[13rem]', 'twok:h-[7.5rem]');
                            champSelectorContainer.classList.replace('fullhd:h-[13.5rem]', 'fullhd:h-[9rem]');
                            selectedBans.parentElement.classList.replace('pb-3', 'pb-1');
                        } else {
                            banSearchContainer.classList.replace('twok:h-[10.5rem]', 'twok:h-[16.5rem]');
                            banSearchContainer.classList.replace('fullhd:h-[12rem]', 'fullhd:h-[17rem]');
                            champSelectorContainer.classList.replace('twok:h-[7.5rem]', 'twok:h-[13rem]');
                            champSelectorContainer.classList.replace('fullhd:h-[9rem]', 'fullhd:h-[13.5rem]');
                            selectedBans.parentElement.classList.replace('pb-1', 'pb-3');
                        }
                        makeDragDroppable();
                        var currentSelectedContextMenuElement = null;
                        addContextMenuToSelectedBans();
                        })
                    .catch(error => {
                        console.error("Error fetching versioned URLs:", error);
                    });
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
                            newParentDiv.style.marginLeft = "-10rem";
                            break;
                        default:
                            newParentDiv.style.marginLeft = "-9rem";
                    }
                    newParentDiv.setAttribute("onmouseover", "showIdentityNotice(true)");
                    newParentDiv.setAttribute("onmouseout", "showIdentityNotice(false)");
                    newSpan.setAttribute("onmouseover", "showIdentityNotice(true)");
                    newSpan.setAttribute("onmouseout", "showIdentityNotice(false)");
                    newParentDiv.classList.add("z-20","w-40","h-8");
                    getVersionedUrl("/clashapp/data/misc/monsters/"+messageAsJson.name.toLowerCase()+".avif").then(versionedPath => {
                        newImg.src = versionedPath;
                    });
                    newImg.width = "32";
                    newImg.height = "32";
                    newImg.alt = "An icon of a random monster from League of Legends";
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
                if((messageAsJson.message == "added %1.") || (messageAsJson.message == "removed %1.")){
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                } else if(messageAsJson.message == "swapped %1 with %2.") {
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ1, messageAsJson.champ2);
                } else {
                    addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color);
                }
            } else if (messageAsJson.status == "Lock") {
                addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                lockSelectedBan(document.getElementById('selectedBans').children[messageAsJson.index], false);
            } else if (messageAsJson.status == "Unlock") {
                addCustomHistoryMessage(messageAsJson.message, messageAsJson.name, messageAsJson.color, messageAsJson.champ);
                unlockSelectedBan(document.getElementById('selectedBans').children[messageAsJson.index], false);
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
    let name = el.getElementsByTagName("span")[0].innerText;
    let id = el.getElementsByTagName("img")[0].dataset.id;
    let sendInfo =  {
        champname: name,
        champid: id,
        teamid: window.location.pathname.split("/team/")[1],
        request: "add"
    };
    ws.send(JSON.stringify(sendInfo))
}

ws.onerror = (error) => {
    console.error('WebSocket Error:', error);
};

function removeFromFile(el){
    let name = el.getElementsByTagName("span")[0].innerText;
    let id = el.getElementsByTagName("img")[0].dataset.id;
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
    textMessage.classList.add("text-[#8984a5]");
    __(message).then(function (result) {
        textMessage.innerText = result;
        historyContainer.insertBefore(textMessage, historyContainer.firstChild.nextSibling);
    });
}

function addCustomHistoryMessage(message, name, color, arg1, arg2){
    arg1 = arg1 || "";
    arg2 = arg2 || "";
    const historyContainer = document.getElementById("historyContainer");
    const textMessage = document.createElement("span");
    __(message).then(function (result) {
        textMessage.innerHTML = "<span class='text-"+color+"/100'>"+name+"</span> "+result.replace("%1", arg1).replace("%2", arg2);
        historyContainer.insertBefore(textMessage, historyContainer.firstChild.nextSibling);
    });
}

// DROPPABLE

function makeDragDroppable(){
    let draggables = document.getElementsByClassName('hoverer')
    
    for (i = 0; i < draggables.length; i++) {
        if (!draggables[i].classList.contains('locked')) {
            draggables[i].addEventListener('dragstart', dragStart)
            draggables[i].addEventListener('drop', dropped)
            draggables[i].addEventListener('dragenter', cancelDefault)
            draggables[i].addEventListener('dragover', dragOver)
        }
    }

    function dragStart (e) {
        if (!e.srcElement.parentElement.classList.contains('locked')) {
            e.dataTransfer.setData('fromID', e.srcElement.previousSibling.dataset.id);
            e.dataTransfer.setData('fromName', e.srcElement.parentElement.parentElement.getElementsByTagName("span")[0].innerText);
            e.dataTransfer.setData('isDraggable', e.srcElement.parentElement.classList); // set event data "isDraggable" to the class name "hoverer" of the parent element
            e.dataTransfer.setDragImage(e.srcElement.previousSibling, 24, 24);
            return oldIndex = getChildIndex(e.srcElement.parentElement.parentElement); // returning so function dropped is able to acces the old index variable
        } else {
            e.preventDefault(); // Prevent dragging if the element is locked
        }
    }

    function dropped (e) {
        if (!e.srcElement.parentElement.classList.contains('locked')) {
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
        } else {
            e.preventDefault();
        }
    }

    function dragOver (e) {
        cancelDefault(e);
        if(e.target.parentElement.classList.contains("locked")){
            e.dataTransfer.dropEffect = "none";
        } else {
            e.dataTransfer.dropEffect = "move";
        }
    }

    function cancelDefault (e) {
        e.preventDefault()
        e.stopPropagation()
        return false
    }
}

function addContextMenuToSelectedBans(){
    let selectedBanHoverElements = document.getElementsByClassName("selected-ban-champion");
    Array.from(selectedBanHoverElements).forEach((singleBanHoverElement) => {
        singleBanHoverElement.firstChild.oncontextmenu = customBanContextMenu;
        document.onclick = hideCustomBanContextMenu;
    })
}

function hideCustomBanContextMenu(){
    if(document.getElementById("customBanContextMenu")){
        document.getElementById("customBanContextMenu").classList.replace("opacity-100", "opacity-0")
        setTimeout(() => {
            document.getElementById("customBanContextMenu").style.left = "0px"; 
            document.getElementById("customBanContextMenu").style.top = "0px"; 
        }, 75);
    }
    if(document.getElementById("customBanUnlockMenu")){
        document.getElementById("customBanUnlockMenu").classList.replace("opacity-100", "opacity-0")
        setTimeout(() => {
            document.getElementById("customBanUnlockMenu").style.left = "0px"; 
            document.getElementById("customBanUnlockMenu").style.top = "0px"; 
        }, 75);
    }
}

function customBanContextMenu(e) {
    e.preventDefault();

    // Handle right-click behavior
    if (e.button === 2) {
        if (document.getElementById("customBanContextMenu").classList.contains("opacity-100") || document.getElementById("customBanUnlockMenu").classList.contains("opacity-100")) {
            if (e.target.parentElement.classList.contains("locked")) {
                let menu = document.getElementById("customBanUnlockMenu");
                menu.style.left = e.pageX + "px";
                menu.style.top = e.pageY + "px";
            } else {
                let menu = document.getElementById("customBanContextMenu");
                menu.style.left = e.pageX + "px";
                menu.style.top = e.pageY + "px";
            }
        } else {
            if (e.target.parentElement.classList.contains("locked")) {
                let menu = document.getElementById("customBanUnlockMenu");
                menu.classList.replace("opacity-0", "opacity-100");
                menu.style.left = e.pageX + "px";
                menu.style.top = e.pageY + "px";
            } else {
                let menu = document.getElementById("customBanContextMenu");
                menu.classList.replace("opacity-0", "opacity-100");
                menu.style.left = e.pageX + "px";
                menu.style.top = e.pageY + "px";
            }
            currentSelectedContextMenuElement = e.target.parentElement.parentElement;
        }
    }
}


function lockSelectedBan(selectedBanElement, websocket = true){
    selectedBanElement.firstChild.classList.add("locked");
    selectedBanElement.firstChild.draggable = false;
    selectedBanElement.firstChild.onclick = null;
    let selectedBanImgElement = selectedBanElement.firstChild.firstChild;
    selectedBanImgElement.style.filter = "url(\"data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><filter id=\'grayscale\'><feColorMatrix type=\'matrix\' values=\'0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0 0 1 0\'/></filter></svg>#grayscale\")";
    selectedBanImgElement.style.filter = "gray";
    selectedBanImgElement.style.WebkitFilter = "grayscale(100%)";
    selectedBanImgElement.nextSibling.classList.add("hidden");
    if(websocket){
        let selectedBanIndex = Array.from(selectedBanElement.parentElement.children).indexOf(selectedBanElement);
        let champname = selectedBanElement.firstChild.nextSibling.innerText;
        let champid = selectedBanElement.getElementsByTagName("img")[0].dataset.id;
        let sendInfo =  {
            index: selectedBanIndex,
            champname: champname,
            champid: champid,
            teamid: window.location.pathname.split("/team/")[1],
            request: "lock"
        };
        ws.send(JSON.stringify(sendInfo));
    }
}

function unlockSelectedBan(selectedBanElement, websocket = true) {
    selectedBanElement.firstChild.classList.remove("locked");
    selectedBanElement.firstChild.draggable = true;
    selectedBanElement.firstChild.onclick = function () {
    };
    let selectedBanImgElement = selectedBanElement.firstChild.firstChild;
    selectedBanImgElement.style.filter = "";
    selectedBanImgElement.style.WebkitFilter = "";
    selectedBanImgElement.nextSibling.classList.remove("hidden");
    if(websocket){
        let selectedBanIndex = Array.from(selectedBanElement.parentElement.children).indexOf(selectedBanElement);
        let champname = selectedBanElement.firstChild.nextSibling.innerText;
        let champid = selectedBanElement.getElementsByTagName("img")[0].dataset.id;
        let sendInfo =  {
            index: selectedBanIndex,
            champname: champname,
            champid: champid,
            teamid: window.location.pathname.split("/team/")[1],
            request: "unlock"
        };
        ws.send(JSON.stringify(sendInfo));
    }
}

function getVersionedUrl(url) {
    return fetch(url)
        .then(response => {
            const lastModified = response.headers.get('Last-Modified');
            return lastModified ? new Date(lastModified).getTime() : null;
        })
        .then(timestamp => {
            return timestamp !== null ? url + "?version=" + timestamp : url;
        })
        .catch(error => {
            console.error("Error fetching or extracting Last-Modified:", error);
            return url; // Return the original URL in case of an error
        });
}
