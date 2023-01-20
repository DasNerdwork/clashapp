<?php
function setCodeHeader($title, $css, $javascript, $websocket, $favicon = 'https://clash.dasnerdwork.net/clashapp/data/misc/favicon.ico'){
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
        if($javascript){
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>';
            echo '<script src="//unpkg.com/alpinejs" defer></script>';
            echo '<script type="text/javascript" src="../clashapp/clash.js"></script>';
        }
        if($websocket){
            echo '<script type="text/javascript" src="../clashapp/websocket.js"></script>';
        }
    echo '</head>';
} // TODO: Websocket nur auf Team ID seiten aktivieren bzw. nur bestimmte websocket funktionalitäten
?>