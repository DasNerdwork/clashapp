<?php session_start(); 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

// print_r($_SESSION);
echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.webp)";
document.body.style.backgroundRepeat = "no-repeat";
document.body.style.backgroundPosition = "50% 20%";
document.body.style.backgroundSize = "40%";
</script>
';
?>



<?php
include('/hdd1/clashapp/templates/footer.php');
?>