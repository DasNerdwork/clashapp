document.addEventListener("DOMContentLoaded", function(){
  var regex = RegExp("(\/clash\/).+$"); 
  if (window.location.pathname.match(regex)) {
    document.getElementById("updateBtn").style.display = "initial";
  }
});