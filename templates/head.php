<?php
function setCodeHeader($title, $css, $javascript, $alpinejs, $websocket, $favicon = 'https://clash.dasnerdwork.net/clashapp/data/misc/favicon.ico'){
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta name="viewport" content="width=device-width">
        <meta name="description" content="Offizielle League of Legends Website zum scouten von League Clash Teams inklusive Profilen und mehr.">
        <title>'.$title.' – DasNerdwork.net</title>';
        echo '<link id="favicon" rel="shortcut icon" href='.$favicon.'>';
        // if($css){ echo '<link rel="stylesheet" href="/clashapp/clash.css">'; }
        if($css){ echo '<link rel="stylesheet" href="/clashapp/css/output.css">'; }
        if($alpinejs){ echo '<script src="//unpkg.com/alpinejs" defer></script>'; }
        if($javascript || $javascript == "qr"){ echo '<script type="text/javascript" src="../clashapp/clash.min.js"></script><script type="text/javascript" src="../clashapp/main.js"></script>'; }
        if($javascript === "qr"){ echo '<script type="text/javascript" src="../clashapp/qr-codes.min.js"></script>'; }
        if($websocket){ echo '<script type="text/javascript" src="../clashapp/websocket.js"></script>'; }
    echo '</head>';
}
?>