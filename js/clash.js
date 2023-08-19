function ready(fn) {
  if (document.readyState !== 'loading') {
    fn();
    return;
  }
  document.addEventListener('DOMContentLoaded', fn);
}

ready(function() {
  // var allClashPages = RegExp("(\/clash\/).+$");
  var allTeamPages = RegExp("(\/team\/).+$");
  if(window.location.pathname.match(allTeamPages)){

    // CHAMP SELECT

    var championList = document.getElementsByClassName("champ-select-champion"); // separate champion icon+name elements in the list
    var champInput = document.getElementById("champSelector"); // Input field of the champion name search
    var clearButton = document.getElementById("champSelectorClear"); // The clear button "x" on the right of the input field
    champInput.addEventListener('keyup', selectChampions); // event listener to fire for typing
    clearButton.addEventListener('click', selectChampions); // event listener to fire for x clicking

    // CHAMPION LIST FILTERING
    function selectChampions(){       
      var lanes = document.getElementsByClassName("lane-selector"); // the 5 lane icons as elements in a list
      for (let i = 0; i < lanes.length; i++) { 
        if(lanes.item(i).classList.contains == "brightness-200"){ // checks if a lane filter icon is set to active (brightness(100%)) and saves the specific lane name for futher sorting
          laneFilter = lanes.item(i).dataset.lane.toUpperCase();
          break;
        } else {
          laneFilter = false;
        }
      }
      let showNone = true;
      if(!laneFilter){ // if we don't have an active lane filter
        for (j = 0; j < championList.length; j++) { // -> Sort if input matches element span name or abbreviation data tag
          span = championList[j].getElementsByTagName("span")[0].innerText; // the current champion names in a list
          if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase())))) { 
            championList[j].style.display = "";
            showNone = false;
          } else {
            championList[j].style.display = "none";
          }
        }
      } else { // if we have an active lane filter
        for (j = 0; j < championList.length; j++) { // -> Sort if input matches element span name or abbreviation data tag AND either also matches the active lane filter
          span = championList[j].getElementsByTagName("span")[0].innerText; // the current champion names in a list
          if (((span.toUpperCase().indexOf(champInput.value.toUpperCase()) > -1) || (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(champInput.value.toUpperCase()))) && (championList[j].getElementsByTagName("img")[0].dataset.abbr.toUpperCase().split(",").includes(laneFilter))) {
            championList[j].style.display = "";
            showNone = false;
          } else {
            championList[j].style.display = "none";
          }
        }
      }
      if(showNone){
        document.getElementById("emptySearchEmote").classList.add("flex");
        document.getElementById("emptySearchEmote").classList.remove("hidden");
      }else{
        document.getElementById("emptySearchEmote").classList.add("hidden");
        document.getElementById("emptySearchEmote").classList.remove("flex");
      }
    }
    // END OF CHAMPION LIST FILTERING

    // GET TIME ZONE

    // let timezone = Intl.DateTimeFormat().resolvedOptions().locale;
    // if (timezone == "de" || timezone == "de-DE") {
    //   document.getElementById("language-selector").innerHTML = "Deutsch";
    // } else {
    //   document.getElementById("language-selector").innerHTML = "English";
    // }
    // console.log("Users browser language: "+timezone)

  }
  });

ready(function(){
  var suggestedBans = document.getElementsByClassName("suggested-ban-champion");
  for(var i = 0; i < suggestedBans.length;i++){
    document.getElementById("suggestedBans").appendChild(suggestedBans[i])
  }
});

ready(function(){
  // Event-Listener für die Checkbox
  let matchExpander = document.getElementById("expand-all-matches");
  matchExpander.addEventListener('change', function() {
    if(matchExpander.checked){
      setCookie("matches-expanded", true);
    } else {
      deleteCookie("matches-expanded");
    }
  });
  if(getCookie("matches-expanded")){
    matchExpander.checked = true;
  }
});

// FROM MAIN PAGE

function showLoader(){
  document.getElementById("main-search-loading-spinner").style.opacity = 1;
  document.getElementById("name").disabled = true;
  document.getElementById("submitBtn").disabled = true;
}

// END FROM MAINPAGE
// LANE HIGHLIGHT
function highlightLaneIcon(laneImg){
  var lanes = document.getElementsByClassName("lane-selector");
  if (laneImg.classList.contains("brightness-50")) {
    laneImg.classList.remove("brightness-50");
    laneImg.classList.remove("saturate-0");
    // laneImg.classList.add("brightness-200"); // not possible due to tailwind
    laneImg.style.filter = "saturate(0) brightness(200%)";
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
        lanes.item(i).style.filter= "";
        lanes.item(i).classList.add("brightness-50");
        lanes.item(i).classList.add("saturate-0");
      }
    }
  } else {
    laneImg.style.filter = "";
    laneImg.classList.add("brightness-50");
    laneImg.classList.add("saturate-0");
    for (let i = 0; i < lanes.length; i++) {
      if(lanes.item(i).style.filter == "saturate(0) brightness(200%)"){
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

function enablePWR(){
  ready(function() {
    let password = document.getElementById("password-label");
    let pwrbutton = document.createElement("input");
    pwrbutton.setAttribute("type", "submit");
    pwrbutton.setAttribute("name", "reset");
    pwrbutton.setAttribute("id", "reset-password-popup");
    pwrbutton.setAttribute("value", "Reset Password?");
    password.parentNode.appendChild(pwrbutton);
  });
}

function setError(message){
  let header = document.getElementsByTagName("header")[0];
  var errorBanners = document.getElementsByClassName("bg-[#ff000040]");
  let check = true;
  // let bannerId;
  if(errorBanners != null){
    for (let i = 0; i < errorBanners.length; i++) {
      const eb = errorBanners[i];
      if(eb.firstChild.innerHTML == message){
        check = false
        break
      }
    }
  }
  if(!check) return "error bereits vorhanden";

  let errorMsg = document.createElement("div");
  errorMsg.setAttribute("class", "bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]");
  errorMsg.innerHTML = "<strong>"+message+"</strong>";
  header.parentNode.insertBefore(errorMsg, header.nextElementSibling);
}

// $('#updateBtn').click(function() {
//   $.ajax({
//   type: "POST",
//   url: "../clashapp/updateTeam.php",
//   data: { usernames: ["<?= $playerNameTeamArray[0] ?>","<?=$playerNameTeamArray[1] ?>","<?=$playerNameTeamArray[2] ?>","<?=$playerNameTeamArray[3] ?>","<?=$playerNameTeamArray[4] ?>"] }
//   }).done(function( msg ) {
//       console.log(msg);
//       var statusJson = JSON.parse(msg);
//       if(statusJson.status == "up-to-date"){
//           var d = new Date();
//           console.log("[" + d.toLocaleTimeString() + "] Benutzerdaten bereits auf dem neusten Stand\n");
//           hideLoader();
//       }else{
//           window.location.reload();
//       }
//   });
// });

function copyToClipboard(text){
  navigator.clipboard.writeText(text).then(function() {
  }, function(err) {
    console.error('Async: Could not copy text: ', err);
  });
}

function showTooltip(element, text, delay, direction, additionalCSS = '') {
  const positions = {
    'top-center': 'max-w-[10%] -ml-20 twok:-mt-24 fullhd:-mt-28',
    'top-right': 'ml-4 -mt-14',
  };

  element.insertAdjacentHTML(
    'beforeend',
    `<div class="w-auto bg-opacity-65 bg-black text-white text-center text-xs p-2 rounded-lg absolute ${positions[direction]} hidden z-10 ${additionalCSS}"
      id="tooltip">${text}</div>`
  );

  const tooltip = document.getElementById('tooltip');
  setTimeout(() => {
    if (tooltip) {
      tooltip.classList.remove('hidden'); // Show the tooltip after adding it to the DOM
      tooltip.style.opacity = '1'; // Set opacity after a brief delay for the fade-in effect
    }
  }, delay);
}

function hideTooltip(element) {
  const tooltip = document.getElementById('tooltip');
  if (tooltip) {
    tooltip.style.opacity = '0';
    tooltip.classList.add('hidden'); // Add 'hidden' class to hide the tooltip
    setTimeout(() => {
      tooltip.remove(); // Remove the tooltip after the fade-out effect
    }, 300); // Delay before removing the tooltip to allow fade-out effect
  }
}

var playerDataCalls = 0;
var masteryScoresCalls = 0;
var currentRankCalls = 0;
var matchIdCalls = 0;
var matchDownloadCalls = 0;