<?php 
session_start();
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Profile', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

$folderPath = '/hdd1/clashapp/data/misc/graphs/';

$files = scandir($folderPath);

echo "<div class='w-full flex justify-center flex-wrap gap-4'>";
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
        echo '<img src="/clashapp/data/misc/graphs/' . $file . '" alt="A graph describing some calcuation logic" class="max-w-5xl">';
    }
}
echo "</div>";

include('/hdd1/clashapp/templates/footer.php');
?>



