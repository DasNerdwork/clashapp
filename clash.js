document.addEventListener("DOMContentLoaded", function(){
  var allClashPages = RegExp("(\/clash\/).+$");
  var mainPage = RegExp("(\/clash.*)$"); 
  if (window.location.pathname.match(allClashPages)) {
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
  var match = document.getElementsByClassName("match");
  var collapser = document.getElementsByClassName("collapser");

  for (i = 0; i < match.length; i++) {
    collapser[i].addEventListener("click", function() {
      var matchcontent = this.parentNode.parentNode.getElementsByClassName("grid-container")[0];
      console.log(this.parentNode);
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
});