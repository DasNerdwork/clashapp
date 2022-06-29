document.addEventListener("DOMContentLoaded", function(){
  var allClashPages = RegExp("(\/clash\/).+$");
  var allTeamPages = RegExp("(\/team\/).+$");
  var mainPage = RegExp("(\/clash.*)$"); 
  if (window.location.pathname.match(allClashPages) || window.location.pathname.match(allTeamPages)) {
    document.getElementById("updateBtn").style.display = "initial";
  } else if (window.location.pathname.match(mainPage)) {
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
    searchBtn.style.backgroundImage = 'url(/wp-content/uploads/svg/searchicon.svg)';
    searchBtn.style.backgroundRepeat = "no-repeat";
    searchBtn.style.backgroundPosition = "center";
    searchBtn.style.backgroundSize = "50%";
    document.body.style.backgroundImage = 'url(/wp-content/uploads/svg/background.svg)';
    document.body.style.backgroundRepeat = "no-repeat";
    document.body.style.backgroundPosition = "50% 20%";
    document.body.style.backgroundSize = "40%";
    document.body.style.backgroundColor = "#c6ccd8";

    // TABLE COLLAPSER
  }
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
      if (matchcontent.style.maxHeight == "130px" || matchcontent.style.maxHeight == ""){
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
      matchcontent.getElementsByClassName("collapser")[0].style.bottom = "0px";
      matchcontent.getElementsByClassName("collapser")[0].style.right = "0px";
      matchcontent.style.maxHeight = matchcontent.scrollHeight + "px";
      } else {
      matchcontent.getElementsByClassName("damage-dealt")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("damage-tanked")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("damage-healed-and-shielded")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("damage-to-objectives")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("visionscore")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("creepscore")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("matchscore")[0].style.bottom = "53px";
      matchcontent.getElementsByClassName("matchscore")[0].style.right = "30px";
      matchcontent.getElementsByClassName("matchscore")[0].style.fontSize = "12px";
      matchcontent.getElementsByClassName("lane-opponent")[0].style.visibility = "hidden";
      matchcontent.getElementsByClassName("collapser")[0].style.bottom = "53px";
      matchcontent.getElementsByClassName("collapser")[0].style.right = "168px";
      matchcontent.style.maxHeight = "130px";
      } 
    });
  }

  // CHAMP SELECT

  var championList = document.getElementsByClassName("champ-select-champion");
  var champInput = document.getElementById("champSelector");
  
  champInput.addEventListener('keyup', selectChampions);


  // List Filtering
  function selectChampions(){
    for (j = 0; j < championList.length; j++) {
      span = championList[j].getElementsByTagName("span")[0].innerText;
      if (span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) {
        championList[j].style.display = "";
      } else {
        championList[j].style.display = "none";
      }
    }
  }
  // End of List Filtering

  $(".champ-select-icon").click(function() {
    $.ajax({
    type: "POST",
    url: "../clashapp/addToFile.php",
    data: {
      champname: this.parentElement.getElementsByTagName("span")[0].innerText,
      champid: this.parentElement.getElementsByTagName("img")[0].dataset.id,
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
      } else if (statusJson.status == "FileDoesNotExist") {
        window.location.reload();
      }
    });
  });

  var selectedBans = document.getElementById("selectedBans");
  var teamid = window.location.pathname.split("/team/")[1];
  var status = 0;

  $.getJSON('https://dasnerdwork.net/clashapp/data/teams/'+teamid+'.json', function(data) {
    // console.log(data)
    if(data["Status"] > status){
      status = data["Status"];
      var html ="";
      for (const element of data["SuggestedBans"]) {
        html += '<div class="selected-ban-champion">'+
                  '<div class="hoverer" onclick="selected_ban_champion(this.parentElement)">'+
                    '<img class="selected-ban-icon" style="height: auto; z-index: 1;" data-id="' + element["id"] + '" src="/clashapp/data/patch/12.12.1/img/champion/' + element["id"] + '.png" width="48">'+
                    '<img class="removal-overlay" src="/clashapp/data/misc/RemovalOverlay.png" width="48"></div>'+
                  '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                '</div>';
      }
    
      selectedBans.innerHTML = html;
    }
  });

  setInterval(function() {
    $.getJSON('https://dasnerdwork.net/clashapp/data/teams/'+teamid+'.json', function(data) {
      // console.log(data)
      if(data["Status"] > status){
        status = data["Status"];
        var html ="";
        for (const element of data["SuggestedBans"]) {
          html += '<div class="selected-ban-champion">'+
                    '<div class="hoverer" onclick="selected_ban_champion(this.parentElement)">'+
                      '<img class="selected-ban-icon" style="height: auto; z-index: 1;" data-id="' + element["id"] + '" src="/clashapp/data/patch/12.12.1/img/champion/' + element["id"] + '.png" width="48">'+
                      '<img class="removal-overlay" src="/clashapp/data/misc/RemovalOverlay.png" width="48"></div>'+  
                    '<span class="selected-ban-caption" style="display: block;">' + element["name"] + '</span>'+
                  '</div>';
        }
      
        selectedBans.innerHTML = html;
      }
    });
  }, 500);
});



function selected_ban_champion(el){
  el.style.display = "none";
  $.ajax({
  type: "POST",
  url: "../clashapp/removeFromFile.php",
  data: {
    champid: el.getElementsByTagName("img")[0].dataset.id,
    teamid: window.location.pathname.split("/team/")[1]
    }
  })
}
