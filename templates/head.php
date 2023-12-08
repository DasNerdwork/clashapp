<?php
function setCodeHeader($title, $css, $javascript, $alpinejs, $websocket, $favicon = 'https://clashscout.com/clashapp/data/misc/favicon.ico'){
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta name="viewport" content="width=device-width">
        <meta name="description" content="Offizielle League of Legends Website zum scouten von League Clash Teams inklusive Profilen und mehr.">
        <title>'.$title.' – ClashScout.com</title>';
        echo '<link id="favicon" rel="shortcut icon" href='.$favicon.'>';
        if($css){ echo '<link rel="stylesheet" href="/clashapp/css/output.css?version='.md5_file("/hdd1/clashapp/css/output.css").'">'; }
        if($alpinejs){ echo '<script type="text/javascript" src="../clashapp/alpine.min.js?version='.md5_file("/hdd1/clashapp/js/alpine.min.js").'" defer></script>'; }
        if($javascript || $javascript == "qr"){ echo '<script type="text/javascript" src="../clashapp/main.min.js?version='.md5_file("/hdd1/clashapp/js/main.min.js").'"></script><script type="text/javascript" src="../clashapp/clash.min.js?version='.md5_file("/hdd1/clashapp/js/clash.min.js").'"></script><script type="text/javascript" src="../clashapp/lazyhtml.min.js?version='.md5_file("/hdd1/clashapp/js/lazyhtml.min.js").'"></script>'; }
        if($javascript === "qr"){ echo '<script type="text/javascript" src="../clashapp/qr-codes.min.js?version='.md5_file("/hdd1/clashapp/js/qr-codes.min.js").'"></script>'; }
        if($websocket){ echo '<script type="text/javascript" src="../clashapp/websocket.min.js?version='.md5_file("/hdd1/clashapp/js/websocket.min.js").'"></script>'; }
        echo "<script>
        function loadAds() {
            var adsScript = document.createElement('script');
            adsScript.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8928684248089281';
            adsScript.async = true;
            adsScript.crossOrigin = 'anonymous';
            document.head.appendChild(adsScript);
          }
          ready(function() {loadAds();}); 
          </script>";
    echo '</head>';
}
?>