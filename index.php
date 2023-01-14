<?php session_start(); 
include('/hdd2/clashapp/templates/head.php');
setCodeHeader('Clash', true, true);
include('/hdd2/clashapp/templates/header.php');

if (!isset($_SESSION)) session_start();
echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.webp)";
document.body.style.backgroundRepeat = "no-repeat";
document.body.style.backgroundPosition = "50% 20%";
document.body.style.backgroundSize = "40%";
</script>
';
?>
<script src="../clashapp/websocket.js"></script>



<?php
include('/hdd2/clashapp/templates/footer.php');
?>