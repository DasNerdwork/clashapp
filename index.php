<?php session_start(); 
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/mongo-db.php';
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.webp)";
document.body.style.backgroundRepeat = "no-repeat";
document.body.style.backgroundPosition = "50% 20%";
document.body.style.backgroundSize = "40%";
</script>
';

include('/hdd1/clashapp/templates/footer.php');
?>