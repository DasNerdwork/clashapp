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
        // if($css){ echo '<link rel="stylesheet" href="/clashapp/clash.css">'; }
        if($css){ echo '<link rel="stylesheet" href="/clashapp/css/output.css">'; }
        if($alpinejs){ echo '<script src="https://unpkg.com/alpinejs@3.13.0/dist/cdn.min.js" defer></script>'; }
        if($javascript || $javascript == "qr"){ echo '<script type="text/javascript" src="../clashapp/main.min.js"></script><script type="text/javascript" src="../clashapp/clash.min.js"></script>'; }
        if($javascript === "qr"){ echo '<script type="text/javascript" src="../clashapp/qr-codes.min.js"></script>'; }
        if($websocket){ echo '<script type="text/javascript" src="../clashapp/websocket.min.js"></script>'; }
        echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8928684248089281" crossorigin="anonymous"></script>';
    echo '</head>';
}
?>