function ready(fn) {
  if (document.readyState !== 'loading') {
    fn();
    return;
  }
  document.addEventListener('DOMContentLoaded', fn);
}

if (typeof getCookie !== 'function') {
  function getCookie(name) {
      var value = '; ' + document.cookie;
      var parts = value.split('; ' + name + '=');
      if (parts.length === 2) {
          return parts.pop().split(';').shift();
      }
  }
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
    'top-center': '-ml-20 twok:-mt-24 fullhd:-mt-20',
    'top-right': 'ml-4 -mt-14',
  };

  const timestamp = new Date().getTime(); // Get a unique timestamp

  element.insertAdjacentHTML(
    'beforeend',
    `<div data-tooltip-id="${timestamp}" class="w-auto z-30 bg-opacity-65 bg-black text-white text-center text-xs p-2 rounded-lg absolute ${positions[direction]} hidden ${additionalCSS}"
      id="tooltip">${text}</div>`
  );

  const tooltip = document.getElementById('tooltip');
  setTimeout(() => {
    if (tooltip) {
      tooltip.classList.remove('hidden'); // Show the tooltip after adding it to the DOM
      tooltip.style.opacity = '1'; // Set opacity after a brief delay for the fade-in effect
    }
  }, delay);
  return tooltip;
}

function hideTooltip(tooltipParent) {
  var firstTooltip = tooltipParent.querySelector('div[data-tooltip-id]');
  if (firstTooltip) {
    firstTooltip.style.opacity = '0';
    firstTooltip.classList.add('hidden');
    firstTooltip.remove();
  }
}

function copyInviteLink(element, text, delay, direction, additionalCSS = '') {
  copyToClipboard(window.location.href + '?join=' + getCookie('roomCode'));
  var tooltipElement = showTooltip(element, text, delay, direction, additionalCSS);
  setTimeout(() => {
    hideTooltip(tooltipElement);
  }, 1000);
}

function updateTagColor(checkbox) {
  const playerTags = document.querySelectorAll('.playerTag');
  
  if (getCookie("tagOptions") === "multi-colored") {
    playerTags.forEach(tag => {
      Array.from(tag.classList).forEach(className => {
        if (className.startsWith('bg-tag-')) {
          tag.classList.replace(className, tag.dataset.color);
        }
      });
    });
  } else {
    playerTags.forEach(tag => {
      let currentBgClass = tag.dataset.color;
      let newClass = "";
      
      // Ändere die Hintergrundklasse basierend auf data-type
      if (tag.dataset.type === 'positive') {
        newClass = 'bg-tag-lime';
      } else {
        newClass = 'bg-tag-red';
      }
      
      tag.classList.replace(currentBgClass, newClass);
    });
  }
}

document.onreadystatechange = function () {
  if (document.readyState == "complete") {
    searchAutosuggestData(autosuggestData, currentPatch, containerTitle);
  }
}

function searchAutosuggestData(autosuggestData, currentPatch, containerTitle) {
    const inputElement = document.getElementById('main-input');
    const autosuggestContainer = document.getElementById('autosuggest-container');
    const tagLineSuggest = document.getElementById('tagLineSuggest');
    
    const searchHistory = JSON.parse(localStorage.getItem("SearchHistory"));

    if(searchHistory != null){
      var historyUlElement = document.getElementById("autosuggest-history-parent");
      if(!historyUlElement){
        var historyTitleDiv = document.createElement('div');
        historyTitleDiv.className = 'p-1.5 bg-searchtitle';
        const historyTitleSpan = document.createElement('span');
        historyTitleSpan.textContent = searchHistoryTitle;
        historyTitleSpan.className = 'font-bold';
        var historyUlElement = document.createElement('ul');
        historyUlElement.id = 'autosuggest-history-parent';
        historyTitleDiv.appendChild(historyTitleSpan);
        for (let i = searchHistory.length - 1; i >= 0; i--) {
          let normalizedSearchHistory = normalizeString(searchHistory[i]);
          let handled = false;
          if(autosuggestData !== ""){
            for (let key in autosuggestData) {
              const normalizedUserKey = normalizeString(key);
              if (normalizedUserKey == normalizedSearchHistory) {
                const liElement = createAutosuggestItem(key, autosuggestData[key], currentPatch, 'player');
                historyUlElement.appendChild(liElement);
                handled = true;
              } 
            }

            if(!handled){
              for (let key in championData) {
                if (key.trim().toLowerCase() == normalizedSearchHistory) {
                  const liElement = createAutosuggestItem(key, championData[key], currentPatch, 'champion');
                  historyUlElement.appendChild(liElement);
                  handled = true;
                } 
              }
            }

            if(!handled) {
              const liElement = createAutosuggestItem(searchHistory[i], '9', currentPatch, 'player');
              historyUlElement.appendChild(liElement);
            }
          }
        }
      }
    }

    function addAutosuggestContainer(){
      autosuggestContainer.innerHTML = '';
     
      const innerDiv1 = document.createElement('div');
      innerDiv1.className = 'text-xs bg-searchbg';
      const innerDiv2 = document.createElement('div');
      const titleDiv = document.createElement('div');
      titleDiv.className = 'p-1.5 bg-searchtitle';
      const titleSpan = document.createElement('span');
      titleSpan.textContent = containerTitle;
      titleSpan.className = 'font-bold';
      const ulElement = document.createElement('ul');
      ulElement.id = 'autosuggest-search-parent';
  
      // Construct the structure by appending child elements
      if(inputElement.value.length > 0){
        titleDiv.appendChild(titleSpan);
        innerDiv2.appendChild(titleDiv);
        innerDiv2.appendChild(ulElement);
      }
      if(searchHistory != null){
        innerDiv2.appendChild(historyTitleDiv);
        innerDiv2.appendChild(historyUlElement);
      }
      innerDiv1.appendChild(innerDiv2);
      autosuggestContainer.appendChild(innerDiv1);

      // Show the autosuggest container
      if (inputElement.value.length > 2) {
        const inputValue = inputElement.value.trim().toLowerCase();
        const normalizedInputValue = normalizeString(inputValue);
        const currentInputLi = createAutosuggestItem(inputElement.value, '9', currentPatch, 'player');
        ulElement.appendChild(currentInputLi);
        if(autosuggestData !== ""){
          for (let key in autosuggestData) {
            const normalizedUserKey = normalizeString(key);
            if (normalizedUserKey.includes(normalizedInputValue)) {
              const liElement = createAutosuggestItem(key, autosuggestData[key], currentPatch, 'player');
              ulElement.appendChild(liElement);
            }
          }
        }
        for (let key in championData) {
          if (key.trim().toLowerCase().includes(normalizedInputValue)) {
            const liElement = createAutosuggestItem(key, championData[key], currentPatch, 'champion');
            const championUlElement = document.getElementById("autosuggest-champion-parent");
            if(!championUlElement){
              const championTitleDiv = document.createElement('div');
              championTitleDiv.className = 'p-1.5 bg-searchtitle';
              const championTitleSpan = document.createElement('span');
              championTitleSpan.textContent = "Champion";
              championTitleSpan.className = 'font-bold';
              const championUlElement = document.createElement('ul');
              championUlElement.id = 'autosuggest-champion-parent';
              championTitleDiv.appendChild(championTitleSpan);
              ulElement.parentElement.appendChild(championTitleDiv);
              championUlElement.appendChild(liElement);
              ulElement.parentElement.appendChild(championUlElement);
            } else {
              championUlElement.appendChild(liElement);
            }
          }
        }
      } else if(inputElement.value.length > 0) {
        ulElement.innerHTML = '';
        const currentInputLi = createAutosuggestItem(inputElement.value, '9', currentPatch, 'player');
        ulElement.appendChild(currentInputLi);
      }
    }

    inputElement.addEventListener('keyup', addAutosuggestContainer);

    inputElement.addEventListener('input', function(e) {
      checkForTagSuggest(e);
    });

    inputElement.addEventListener('keydown', function(e) {
      if(e.key === 'Tab'){
        e.preventDefault();
        if(!inputElement.value.includes('#')){
          inputElement.value += "#EUW";
        }
        tagLineSuggest.innerHTML = "";
      }
    });

    inputElement.addEventListener('dragstart', function (event) {
      event.preventDefault();
    });

    inputElement.addEventListener('focus', function(e) {
      addAutosuggestContainer();
      autosuggestContainer.classList.remove('hidden');
      checkForTagSuggest(e);
    });

    inputElement.addEventListener('blur', function () {
      autosuggestContainer.classList.add('hidden');
      tagLineSuggest.innerHTML = "";
    });

    function checkForTagSuggest(e){
      if (inputElement.value.length > 2 && !inputElement.value.includes('#')) {
        let currentInput = inputElement.value;
        tagLineSuggest.innerHTML = currentInput + "<span class='bg-searchtitle px-1 rounded ml-1 text-sm text-[#9ea4bd]'>#EUW</span>";
      } else {
        tagLineSuggest.innerHTML = "";
        if(e.data === "#"){
          inputElement.value = inputElement.value.split('#')[0] + "#" +inputElement.value.split('#')[1].slice(0, 5);
        }
        if(inputElement.value.includes('#') && inputElement.value.split('#')[1].length > 5){
          inputElement.value = inputElement.value.split('#')[0] + "#" +inputElement.value.split('#')[1].slice(0, 5);
        }
      }
    }

    // Hilfsfunktion zur Normalisierung von Zeichenfolgen
    function normalizeString(input) {
      var characterSet = "A-Za-z0-9\\xAA\\xB5\\xBA\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\u02C1\\u02C6-\\u02D1\\u02E0-\\u02E4\\u02EC\\u02EE\\u0370-\\u0374\\u0376\\u0377\\u037A-\\u037D\\u037F\\u0386\\u0388-\\u038A\\u038C\\u038E-\\u03A1\\u03A3-\\u03F5\\u03F7-\\u0481\\u048A-\\u052F\\u0531-\\u0556\\u0559\\u0561-\\u0587\\u05D0-\\u05EA\\u05F0-\\u05F2\\u0620-\\u064A\\u066E\\u066F\\u0671-\\u06D3\\u06D5\\u06E5\\u06E6\\u06EE\\u06EF\\u06FA-\\u06FC\\u06FF\\u0710\\u0712-\\u072F\\u074D-\\u07A5\\u07B1\\u07CA-\\u07EA\\u07F4\\u07F5\\u07FA\\u0800-\\u0815\\u081A\\u0824\\u0828\\u0840-\\u0858\\u08A0-\\u08B4\\u0904-\\u0939\\u093D\\u0950\\u0958-\\u0961\\u0971-\\u0980\\u0985-\\u098C\\u098F\\u0990\\u0993-\\u09A8\\u09AA-\\u09B0\\u09B2\\u09B6-\\u09B9\\u09BD\\u09CE\\u09DC\\u09DD\\u09DF-\\u09E1\\u09F0\\u09F1\\u0A05-\\u0A0A\\u0A0F\\u0A10\\u0A13-\\u0A28\\u0A2A-\\u0A30\\u0A32\\u0A33\\u0A35\\u0A36\\u0A38\\u0A39\\u0A59-\\u0A5C\\u0A5E\\u0A72-\\u0A74\\u0A85-\\u0A8D\\u0A8F-\\u0A91\\u0A93-\\u0AA8\\u0AAA-\\u0AB0\\u0AB2\\u0AB3\\u0AB5-\\u0AB9\\u0ABD\\u0AD0\\u0AE0\\u0AE1\\u0AF9\\u0B05-\\u0B0C\\u0B0F\\u0B10\\u0B13-\\u0B28\\u0B2A-\\u0B30\\u0B32\\u0B33\\u0B35-\\u0B39\\u0B3D\\u0B5C\\u0B5D\\u0B5F-\\u0B61\\u0B71\\u0B83\\u0B85-\\u0B8A\\u0B8E-\\u0B90\\u0B92-\\u0B95\\u0B99\\u0B9A\\u0B9C\\u0B9E\\u0B9F\\u0BA3\\u0BA4\\u0BA8-\\u0BAA\\u0BAE-\\u0BB9\\u0BD0\\u0C05-\\u0C0C\\u0C0E-\\u0C10\\u0C12-\\u0C28\\u0C2A-\\u0C39\\u0C3D\\u0C58-\\u0C5A\\u0C60\\u0C61\\u0C85-\\u0C8C\\u0C8E-\\u0C90\\u0C92-\\u0CA8\\u0CAA-\\u0CB3\\u0CB5-\\u0CB9\\u0CBD\\u0CDE\\u0CE0\\u0CE1\\u0CF1\\u0CF2\\u0D05-\\u0D0C\\u0D0E-\\u0D10\\u0D12-\\u0D3A\\u0D3D\\u0D4E\\u0D5F-\\u0D61\\u0D7A-\\u0D7F\\u0D85-\\u0D96\\u0D9A-\\u0DB1\\u0DB3-\\u0DBB\\u0DBD\\u0DC0-\\u0DC6\\u0E01-\\u0E30\\u0E32\\u0E33\\u0E40-\\u0E46\\u0E81\\u0E82\\u0E84\\u0E87\\u0E88\\u0E8A\\u0E8D\\u0E94-\\u0E97\\u0E99-\\u0E9F\\u0EA1-\\u0EA3\\u0EA5\\u0EA7\\u0EAA\\u0EAB\\u0EAD-\\u0EB0\\u0EB2\\u0EB3\\u0EBD\\u0EC0-\\u0EC4\\u0EC6\\u0EDC-\\u0EDF\\u0F00\\u0F40-\\u0F47\\u0F49-\\u0F6C\\u0F88-\\u0F8C\\u1000-\\u102A\\u103F\\u1050-\\u1055\\u105A-\\u105D\\u1061\\u1065\\u1066\\u106E-\\u1070\\u1075-\\u1081\\u108E\\u10A0-\\u10C5\\u10C7\\u10CD\\u10D0-\\u10FA\\u10FC-\\u1248\\u124A-\\u124D\\u1250-\\u1256\\u1258\\u125A-\\u125D\\u1260-\\u1288\\u128A-\\u128D\\u1290-\\u12B0\\u12B2-\\u12B5\\u12B8-\\u12BE\\u12C0\\u12C2-\\u12C5\\u12C8-\\u12D6\\u12D8-\\u1310\\u1312-\\u1315\\u1318-\\u135A\\u1380-\\u138F\\u13A0-\\u13F5\\u13F8-\\u13FD\\u1401-\\u166C\\u166F-\\u167F\\u1681-\\u169A\\u16A0-\\u16EA\\u16F1-\\u16F8\\u1700-\\u170C\\u170E-\\u1711\\u1720-\\u1731\\u1740-\\u1751\\u1760-\\u176C\\u176E-\\u1770\\u1780-\\u17B3\\u17D7\\u17DC\\u1820-\\u1877\\u1880-\\u18A8\\u18AA\\u18B0-\\u18F5\\u1900-\\u191E\\u1950-\\u196D\\u1970-\\u1974\\u1980-\\u19AB\\u19B0-\\u19C9\\u1A00-\\u1A16\\u1A20-\\u1A54\\u1AA7\\u1B05-\\u1B33\\u1B45-\\u1B4B\\u1B83-\\u1BA0\\u1BAE\\u1BAF\\u1BBA-\\u1BE5\\u1C00-\\u1C23\\u1C4D-\\u1C4F\\u1C5A-\\u1C7D\\u1CE9-\\u1CEC\\u1CEE-\\u1CF1\\u1CF5\\u1CF6\\u1D00-\\u1DBF\\u1E00-\\u1F15\\u1F18-\\u1F1D\\u1F20-\\u1F45\\u1F48-\\u1F4D\\u1F50-\\u1F57\\u1F59\\u1F5B\\u1F5D\\u1F5F-\\u1F7D\\u1F80-\\u1FB4\\u1FB6-\\u1FBC\\u1FBE\\u1FC2-\\u1FC4\\u1FC6-\\u1FCC\\u1FD0-\\u1FD3\\u1FD6-\\u1FDB\\u1FE0-\\u1FEC\\u1FF2-\\u1FF4\\u1FF6-\\u1FFC\\u2071\\u207F\\u2090-\\u209C\\u2102\\u2107\\u210A-\\u2113\\u2115\\u2119-\\u211D\\u2124\\u2126\\u2128\\u212A-\\u212D\\u212F-\\u2139\\u213C-\\u213F\\u2145-\\u2149\\u214E\\u2183\\u2184\\u2C00-\\u2C2E\\u2C30-\\u2C5E\\u2C60-\\u2CE4\\u2CEB-\\u2CEE\\u2CF2\\u2CF3\\u2D00-\\u2D25\\u2D27\\u2D2D\\u2D30-\\u2D67\\u2D6F\\u2D80-\\u2D96\\u2DA0-\\u2DA6\\u2DA8-\\u2DAE\\u2DB0-\\u2DB6\\u2DB8-\\u2DBE\\u2DC0-\\u2DC6\\u2DC8-\\u2DCE\\u2DD0-\\u2DD6\\u2DD8-\\u2DDE\\u2E2F\\u3005\\u3006\\u3031-\\u3035\\u303B\\u303C\\u3041-\\u3096\\u309D-\\u309F\\u30A1-\\u30FA\\u30FC-\\u30FF\\u3105-\\u312D\\u3131-\\u318E\\u31A0-\\u31BA\\u31F0-\\u31FF\\u3400-\\u4DB5\\u4E00-\\u9FD5\\uA000-\\uA48C\\uA4D0-\\uA4FD\\uA500-\\uA60C\\uA610-\\uA61F\\uA62A\\uA62B\\uA640-\\uA66E\\uA67F-\\uA69D\\uA6A0-\\uA6E5\\uA717-\\uA71F\\uA722-\\uA788\\uA78B-\\uA7AD\\uA7B0-\\uA7B7\\uA7F7-\\uA801\\uA803-\\uA805\\uA807-\\uA80A\\uA80C-\\uA822\\uA840-\\uA873\\uA882-\\uA8B3\\uA8F2-\\uA8F7\\uA8FB\\uA8FD\\uA90A-\\uA925\\uA930-\\uA946\\uA960-\\uA97C\\uA984-\\uA9B2\\uA9CF\\uA9E0-\\uA9E4\\uA9E6-\\uA9EF\\uA9FA-\\uA9FE\\uAA00-\\uAA28\\uAA40-\\uAA42\\uAA44-\\uAA4B\\uAA60-\\uAA76\\uAA7A\\uAA7E-\\uAAAF\\uAAB1\\uAAB5\\uAAB6\\uAAB9-\\uAABD\\uAAC0\\uAAC2\\uAADB-\\uAADD\\uAAE0-\\uAAEA\\uAAF2-\\uAAF4\\uAB01-\\uAB06\\uAB09-\\uAB0E\\uAB11-\\uAB16\\uAB20-\\uAB26\\uAB28-\\uAB2E\\uAB30-\\uAB5A\\uAB5C-\\uAB65\\uAB70-\\uABE2\\uAC00-\\uD7A3\\uD7B0-\\uD7C6\\uD7CB-\\uD7FB\\uF900-\\uFA6D\\uFA70-\\uFAD9\\uFB00-\\uFB06\\uFB13-\\uFB17\\uFB1D\\uFB1F-\\uFB28\\uFB2A-\\uFB36\\uFB38-\\uFB3C\\uFB3E\\uFB40\\uFB41\\uFB43\\uFB44\\uFB46-\\uFBB1\\uFBD3-\\uFD3D\\uFD50-\\uFD8F\\uFD92-\\uFDC7\\uFDF0-\\uFDFB\\uFE70-\\uFE74\\uFE76-\\uFEFC\\uFF21-\\uFF3A\\uFF41-\\uFF5A\\uFF66-\\uFFBE\\uFFC2-\\uFFC7\\uFFCA-\\uFFCF\\uFFD2-\\uFFD7\\uFFDA-\\uFFDC\\u0023#";
      var re = RegExp("^[" + characterSet + "\\s'-]+$");
      if(input.match(re) && input.length < 23){
        return input.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      }
    }
}

function createAutosuggestItem(key, icon, currentPatch, variant) {
  const liElement = document.createElement('li');
  liElement.classList.add('p-1.5', 'hover:bg-darker', 'cursor-pointer');

  const divElement = document.createElement('div');
  divElement.classList.add('flex', 'items-center', 'gap-1.5');

  const imgElement = document.createElement('img');
  imgElement.width = 28;
  imgElement.height = 28;
  if(variant == 'player'){
    imgElement.src = `/clashapp/data/patch/${currentPatch}/img/profileicon/${icon}.avif`;
    imgElement.alt = `Current League of Legends Summoner Icon of ${key}`;
  } else if(variant == 'champion'){
    imgElement.src = `/clashapp/data/patch/${currentPatch}/img/champion/${icon.replace("png", "avif")}`;
    imgElement.alt = `Current League of Legends Champion Icon of ${key}`;
  }

  const spanElement = document.createElement('span');
  spanElement.classList.add('text-sm');
  if(variant == 'player' && key.includes("#")){
    spanElement.innerHTML = key.split('#')[0]+'<span class="bg-searchtitle px-1 rounded ml-1 text-sm text-[#9ea4bd]">#'+key.split('#')[1]+'</span>';
  } else {
    spanElement.textContent = key;
  }

  divElement.appendChild(imgElement);
  divElement.appendChild(spanElement);
  liElement.appendChild(divElement);

  function updateSearchHistory(text){
    let searchHistory = localStorage.getItem("SearchHistory");
    if(searchHistory == null){
      let historyArray = [text];
      localStorage.setItem("SearchHistory", JSON.stringify(historyArray));
    } else {
      searchHistory = JSON.parse(searchHistory);
      if (searchHistory.length < 3){
        const index = searchHistory.indexOf(text);
        if (index !== -1) {
          searchHistory.splice(index, 1);
        }
      } else if (searchHistory.length == 3){
        const index = searchHistory.indexOf(text);
        if (index !== -1) {
          searchHistory.splice(index, 1);
        } else {
          searchHistory.shift();
        }
        searchHistory.push(text);
      }
      localStorage.setItem("SearchHistory", JSON.stringify(searchHistory));
    }
  }

  if(variant == 'player'){
    liElement.addEventListener('mousedown', function (event) {
      event.preventDefault(); // Prevents the blur event from immediately firing
    });
    liElement.addEventListener('click', function() {
      updateSearchHistory(key);
      key2 = key.toLowerCase();
      if(key2 == "flokrastinator" || key2 == "jnnstv" || key2 == "5 min deathtimer" || key2 == "ilealori" || key2 == "vollbard" || key2 == "bard bard bard bard#brd" || key2 == "dasnerdwork#nerdy"){
        window.location.href="https://clashscout.com/team/test";
      } else {
        postAjax(`${window.location.protocol}//${window.location.hostname}/clashapp/src/apiFunctions.php`, { sumname: key }, function(data){
          if(data == "404"){
              window.location.href="https://clashscout.com/404";
          } else {
              window.location.href="https://clashscout.com/team/" + data;
          }
        });
      }
    });
  } else if(variant == 'champion'){
    liElement.addEventListener('mousedown', function (event) {
      event.preventDefault(); // Prevents the blur event from immediately firing
    });
    liElement.addEventListener('click', function() {
      updateSearchHistory(key);
      // window.location.href="https://clashscout.com/team/test";
      alert("Error: Champion pages not implemented yet.");
    });
  }

  return liElement;
}

document.addEventListener('DOMContentLoaded', function () {
  const sliderContainers = document.querySelectorAll('.slider-container');
  if(sliderContainers != null){
    sliderContainers.forEach((slider) => {
      let isDown = false;
      let startX;
      let scrollLeft;
      let snapPoints = []; // Array to store the snap points
  
      // Calculate snap points based on the slider item width including the gap
      let sliderItem = slider.querySelector('.slider-item');
      if(sliderItem){
        const sliderItemWidth = slider.querySelector('.slider-item').offsetWidth;
        const gapWidth = 32; // Adjust this value to match your Tailwind gap class (e.g., gap-8 = 2rem)
    
        for (let i = 0; i < slider.scrollWidth; i += sliderItemWidth + gapWidth) {
          snapPoints.push(i);
        }
    
        const end = () => {
          isDown = false;
    
          // Find the closest snap point to the current scroll position
          const currentScrollLeft = slider.scrollLeft;
          const closestSnapPoint = snapPoints.reduce((prev, curr) => {
            return Math.abs(curr - currentScrollLeft) < Math.abs(prev - currentScrollLeft) ? curr : prev;
          });
    
          // Snap to the closest snap point
          slider.scrollTo({
            left: closestSnapPoint,
            behavior: 'smooth', // You can use 'auto' for instant snap
          });
        }
    
        const start = (e) => {
          isDown = true;
          startX = e.pageX || e.touches[0].pageX - slider.offsetLeft;
          scrollLeft = slider.scrollLeft;
        }
    
        const move = (e) => {
          if (!isDown) return;
    
          e.preventDefault();
          const x = e.pageX || e.touches[0].pageX - slider.offsetLeft;
          const dist = (x - startX);
          slider.scrollLeft = scrollLeft - dist;
        }
    
        // Add event listeners to the slider for mouse and touch events
        slider.addEventListener('mousedown', start);
        slider.addEventListener('touchstart', start, {passive: true});
        slider.addEventListener('mousemove', move);
        slider.addEventListener('touchmove', move, {passive: true});
        slider.addEventListener('mouseup', end);
        slider.addEventListener('touchend', end);
        slider.addEventListener('mouseleave', end);
        slider.addEventListener('dragstart', (e) => {e.preventDefault();});
    
        // Add scroll wheel event listener for whole-element scrolling
        slider.addEventListener('wheel', (e) => {
          e.preventDefault();
          if (e.deltaY > 0 || e.deltaX > 0) {
            slider.scrollLeft += sliderItemWidth + gapWidth;
          } else {
            slider.scrollLeft -= sliderItemWidth + gapWidth;
          }
        }, {passive: false});
      }
    });
  }
});