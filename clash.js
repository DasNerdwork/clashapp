$.get( "https://clash.dasnerdwork.net/clashapp/data/patch/version.txt", function( data ) {
  const currentpatch = data;
  $('document').ready(function() {
    var allClashPages = RegExp("(\/clash\/).+$");
    var allTeamPages = RegExp("(\/team\/).+$");
    if (window.location.pathname.match(allClashPages) || window.location.pathname.match(allTeamPages)) {
      document.getElementById("updateBtn").style.display = "initial";
    } else if (window.location.href == "https://clash.dasnerdwork.net/") {
      footer = document.getElementById("full-footer");
      footer.style.position = "fixed";
      suchfeld = document.getElementById("suchfeld");
      suchfeld.style.position = "absolute";
      suchfeld.style.top = "60%";
      suchfeld.style.left = "50%";
      suchfeld.style.transform = "translate(-50%,-50%)";
      suchfeld.style.width = "800px";
      input = document.getElementById("name");
      input.style.height = "60px";
      input.style.width = "100%";
      input.style.padding = "10px 60px 10px 10px";
      input.style.border = "none";
      input.style.fontSize = "20px";
      input.style.fontWeight = "400";
      input.style.borderRadius = "30px 0 0 30px";
      input.style.textIndent= "20px";
      input.placeholder = "Gib einen Beschwörernamen ein";
      input.style.outlineStyle = "none";
      searchBtn = document.getElementById("submitBtn");
      searchBtn.value = "";
      searchBtn.style.borderRadius = "0 30px 30px 0";
      searchBtn.style.height = "60px";
      searchBtn.style.padding = "10px 60px 10px 10px";
      searchBtn.style.border = "none";
      searchBtn.style.fontSize = "20px";
      searchBtn.style.backgroundImage = 'url(/clashapp/data/misc/svg/searchicon.svg)';
      searchBtn.style.backgroundRepeat = "no-repeat";
      searchBtn.style.backgroundPosition = "center";
      searchBtn.style.backgroundSize = "50%";
      document.body.style.backgroundImage = 'url(/clashapp/data/misc/svg/background.svg)';
      document.body.style.backgroundRepeat = "no-repeat";
      document.body.style.backgroundPosition = "50% 20%";
      document.body.style.backgroundSize = "40%";
      document.body.style.backgroundColor = "#c6ccd8";
    }

      // TABLE COLLAPSER

    if(window.location.pathname.match(allTeamPages)){
      document.title = document.getElementById("team-title").innerText;
      var coll = document.getElementsByClassName("collapsible");
      var i;

      for (i = 0; i < coll.length; i++) {
        coll[i].addEventListener("click", function() {
          this.classList.toggle("active");
          var content = this.nextElementSibling;
          if (content.style.maxHeight == "0px" ){
            content.style.maxHeight = content.scrollHeight + "px";
          } else {
            content.style.maxHeight = "0px";
          }
        });
      }

    // MATCH COLLAPSER

      var match = document.getElementsByClassName("match");
      var collapser = document.getElementsByClassName("collapser");

      for (i = 0; i < match.length; i++) {
        collapser[i].addEventListener("click", function() {
          var matchcontent = this.parentNode.parentNode.getElementsByClassName("grid-container")[0];
          if (matchcontent.style.maxHeight == "140px" || matchcontent.style.maxHeight == ""){
          matchcontent.getElementsByClassName("damage-dealt")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("damage-tanked")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("damage-healed-and-shielded")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("damage-to-objectives")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("visionscore")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("creepscore")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("matchscore")[0].style.bottom = "0px";
          matchcontent.getElementsByClassName("matchscore")[0].style.right = "0px";
          matchcontent.getElementsByClassName("matchscore")[0].style.fontSize = "16px";
          matchcontent.getElementsByClassName("lane-opponent")[0].style.visibility = "visible";
          matchcontent.getElementsByClassName("collapser")[0].style.top = "-22px";
          matchcontent.getElementsByClassName("collapser")[0].style.right = "-170px";
          matchcontent.style.maxHeight = matchcontent.scrollHeight + "px";
          this.innerHTML = "&#8963;";
          } else {
          matchcontent.getElementsByClassName("damage-dealt")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("damage-tanked")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("damage-healed-and-shielded")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("damage-to-objectives")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("visionscore")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("creepscore")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("matchscore")[0].style.bottom = "61px";
          matchcontent.getElementsByClassName("matchscore")[0].style.right = "30px";
          matchcontent.getElementsByClassName("matchscore")[0].style.fontSize = "12px";
          matchcontent.getElementsByClassName("lane-opponent")[0].style.visibility = "hidden";
          matchcontent.getElementsByClassName("collapser")[0].style.top = "";
          matchcontent.getElementsByClassName("collapser")[0].style.right = "";
          matchcontent.style.maxHeight = "140px";
          this.innerHTML = "&#8964;";
          }
        });
      }
      // CHAMP SELECT

      var championList = document.getElementsByClassName("champ-select-champion"); // separate champion icon+name elements in the list
      var champInput = document.getElementById("champSelector"); // Input field of the champion name search
      champInput.addEventListener('keyup', selectChampions); // event listener to fire for typing

      // CHAMPION LIST FILTERING
      function selectChampions(){          
        var lanes = document.getElementsByClassName("lane-selector"); // the 5 lane icons as elements in a list
          for (let i = 0; i < lanes.length; i++) { 
            if(lanes.item(i).style.filter == "brightness(100%)"){ // checks if a lane filter icon is set to active (brightness(100%)) and saves the specific lane name for futher sorting
              laneFilter = lanes.item(i).dataset.lane.toUpperCase();
              break;
            } else {
              laneFilter = false;
            }
          }
          if(!laneFilter){ // if we don't have an active lane filter
            for (j = 0; j < championList.length; j++) { // -> Sort if input matches element span name or abbreviation data tag
              span = championList[j].getElementsByTagName("span")[0].innerText; // the current champion names in a list
              if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase())))) { 
                championList[j].style.display = "";
              } else {
                championList[j].style.display = "none";
              }
            }
          } else { // if we have an active lane filter
            for (j = 0; j < championList.length; j++) { // -> Sort if input matches element span name or abbreviation data tag AND either also matches the active lane filter
              span = championList[j].getElementsByTagName("span")[0].innerText; // the current champion names in a list
              if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase()))) && (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(laneFilter))) {
                championList[j].style.display = "";
              } else {
                championList[j].style.display = "none";
            }
          }
        }
      }
      // END OF CHAMPION LIST FILTERING

      // GET TIME ZONE

      let timezone = Intl.DateTimeFormat().resolvedOptions().locale;
      if (timezone == "de" || timezone == "de-DE") {
        document.getElementById("language-selector").innerHTML = "Deutsch";
      } else {
        document.getElementById("language-selector").innerHTML = "English";
      }
      // console.log("Users browser language: "+timezone)
























      $(".ban-hoverer").click(function() {
        var name = this.parentElement.getElementsByTagName("span")[0].innerText;
        var id = this.parentElement.getElementsByTagName("img")[0].dataset.id;
        $.ajax({
        type: "POST",
        url: "../clashapp/addToFile.php",
        data: {
          champname: name,
          champid: id,
          teamid: window.location.pathname.split("/team/")[1]
          }
        }).done(function( msg ) {
          var statusJson = JSON.parse(msg);
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
          } else if (statusJson.status == "FileDoesNotExist") {
            window.location.reload();
          } else {
            html = '<div class="selected-ban-champion">'+
                        '<div class="hoverer" draggable="true" onclick="selected_ban_champion(this.parentElement)">'+
                          '<img class="selected-ban-icon" style="height: auto; z-index: 1;" data-id="' + id + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + id + '.png" width="48" loading="lazy">'+
                          '<img class="removal-overlay" src="/clashapp/data/misc/RemovalOverlay.png" width="48"></div>'+
                        '<span class="selected-ban-caption" style="display: block;">' + name + '</span>'+
                      '</div>';
            selectedBans.innerHTML += html;
          }
        });
      });

      var selectedBans = document.getElementById("selectedBans");
      var teamid = window.location.pathname.split("/team/")[1];
      var status = 0;

      setInterval(function() {
        $.ajax({
          cache: false,
          url: "https://clash.dasnerdwork.net/clashapp/data/teams/"+teamid+".json",
          dataType: "json",
          success: function(data) {
            if(data["Status"] > status){
              status = data["Status"];
              var html ="";
              for (const element of data["SuggestedBans"]) {
                html += '<div class="selected-ban-champion">'+
                          '<div class="hoverer" draggable="true" onclick="selected_ban_champion(this.parentElement)">'+
                            '<img class="selected-ban-icon" style="height: auto; z-index: 1;" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.png" width="48" loading="lazy">'+
                            '<img class="removal-overlay" src="/clashapp/data/misc/RemovalOverlay.png" width="48"></div>'+
                          '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                        '</div>';
              }
              selectedBans.innerHTML = html;
              makeDragDroppable();
            }
          }
        });
      }, 500);
    }

    // DROPPABLE
    
  // setTimeout(function() {
  //   makeDragDroppable();
  // }, 600);

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

      if(e.dataTransfer.getData('isDraggable') != "hoverer"){ // get the "isDraggable" event data, if class of dragged element == hoverer continue, else cancel
        cancelDefault(e);
      } else {
        // console.log("Moving index "+oldIndex+" to "+newIndex);
        // insert the dropped item at new place
        // if (newIndex < oldIndex) {
        //   selectedBans.insertBefore(selectedBans.children[oldIndex], selectedBans.children[newIndex]);
        // } else {
        //   selectedBans.insertBefore(selectedBans.children[oldIndex], selectedBans.children[newIndex].nextElementSibling);
        // }
        $.ajax({
          type: "POST",
          url: "../clashapp/swapInFile.php",
          data: {
            fromName: fromName,
            toName: toName,
            teamid: window.location.pathname.split("/team/")[1]
            }
          })
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

  });

  $('document').ready(function() {
    var suggestedBans = document.getElementsByClassName("suggested-ban-champion");
    for(var i = 0; i < suggestedBans.length;i++){
      suggestedBans[i].style.visibility = "visible";
      $("#suggestedBans").append(suggestedBans[i])
    }

    $.get( "https://clash.dasnerdwork.net/clashapp/data/patch/version.txt", function( data ) {
      const currentpatch = data;
    });
    var allTeamPages = RegExp("(\/team\/).+$");
    if(window.location.pathname.match(allTeamPages)){
      var selectedBans = document.getElementById("selectedBans");
      var teamid = window.location.pathname.split("/team/")[1];
      var status = 0;

      $.ajax({
        cache: false,
        url: "https://clash.dasnerdwork.net/clashapp/data/teams/"+teamid+".json",
        dataType: "json",
        success: function(data) {
          if(data["Status"] > status){
            status = data["Status"];
            var html ="";
            for (const element of data["SuggestedBans"]) {
              html += '<div class="selected-ban-champion">'+
                        '<div class="hoverer" draggable="true" onclick="selected_ban_champion(this.parentElement)">'+
                          '<img class="selected-ban-icon" style="height: auto; z-index: 1;" data-id="' + element["id"] + '" src="/clashapp/data/patch/' + currentpatch + '/img/champion/' + element["id"] + '.png" width="48" loading="lazy">'+
                          '<img class="removal-overlay" src="/clashapp/data/misc/RemovalOverlay.png" width="48"></div>'+
                        '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                      '</div>';
            }
            selectedBans.innerHTML = html;
          }
        }
      });
    }
  });
});

function selected_ban_champion(el){
  el.style.opacity = '0';
  clickableHoverers = document.getElementsByClassName("hoverer");
  for (h = 0; h < clickableHoverers.length; h++) {
    clickableHoverers[h].style.pointerEvents = "none";
  }
    $.ajax({
    type: "POST",
    url: "../clashapp/removeFromFile.php",
    data: {
      champid: el.getElementsByTagName("img")[0].dataset.id,
      teamid: window.location.pathname.split("/team/")[1]
      }
    })
  // }, 50);
}

// LANE HIGHLIGHT
function highlightLaneIcon(laneImg){
  var lanes = document.getElementsByClassName("lane-selector");
  if (laneImg.style.filter == "brightness(50%)") {
    laneImg.style.filter = "brightness(100%)";
    var championList = document.getElementsByClassName("champ-select-champion");
    var champInput = document.getElementById("champSelector");
    laneFilter = laneImg.dataset.lane.toUpperCase();
    for (j = 0; j < championList.length; j++) {
      span = championList[j].getElementsByTagName("span")[0].innerText;
      if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase()))) && (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(laneFilter))) {
        championList[j].style.display = "";
      } else {
        championList[j].style.display = "none";
      }
    }
    for (let i = 0; i < lanes.length; i++) {
      if(lanes.item(i) != laneImg){
        lanes.item(i).style.filter = "brightness(50%)";
      }
    }
  } else {
    laneImg.style.filter = "brightness(50%)";
    for (let i = 0; i < lanes.length; i++) {
      if(lanes.item(i).style.filter == "brightness(100%)"){
        var activeLane = true;
      } else {
        var activeLane = false;
      }
    }
    if (!activeLane){
      var championList = document.getElementsByClassName("champ-select-champion");
      for (j = 0; j < championList.length; j++) {
        var champInput = document.getElementById("champSelector");
        for (j = 0; j < championList.length; j++) {
          span = championList[j].getElementsByTagName("span")[0].innerText;
          if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase())))) {
            championList[j].style.display = "";
          } else {
            championList[j].style.display = "none";
          }
        }
      }
    }
  }
}

function getChildIndex(node) {
  return Array.prototype.indexOf.call(node.parentNode.childNodes, node);
}

function deleteAccount(status){
  let button = document.getElementById("account-delete-button");
  let cancel = document.getElementById("account-delete-cancel");
  let confirm = document.getElementById("account-delete-confirm");
  let form = document.getElementById("account-delete-form");
  if(status){
    button.style.display = "none";
    cancel.style.display = "unset";
    confirm.style.display = "unset";
    form.style.display = "unset";
  } else {
    button.style.display = "unset";
    cancel.style.display = "none";
    confirm.style.display = "unset";
    form.style.display = "none";
  }
}

function resetPassword(status){
  let button = document.getElementById("reset-password-button");
  let cancel = document.getElementById("reset-password-cancel");
  let confirm = document.getElementById("reset-password-confirm");
  let form = document.getElementById("reset-password-form");
  if(status){
    button.style.display = "none";
    cancel.style.display = "unset";
    confirm.style.display = "unset";
    form.style.display = "unset";
  } else {
    button.style.display = "unset";
    cancel.style.display = "none";
    confirm.style.display = "unset";
    form.style.display = "none";
  }
}

function enablePWR(){
  $('document').ready(function() {
    let login = document.getElementById("login-button");
    let pwrbutton = document.createElement("input");
    pwrbutton.setAttribute("type", "submit");
    pwrbutton.setAttribute("name", "reset");
    pwrbutton.setAttribute("id", "reset-password-button");
    pwrbutton.setAttribute("value", "Reset Password");
    pwrbutton.style.marginLeft = "20px";
    login.parentNode.insertBefore(pwrbutton, login.nextSibling);
  });
}