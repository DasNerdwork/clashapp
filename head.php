<?php
function setCodeHeader($title, $css, $javascript, $favicon = 'https://clash.dasnerdwork.net/clashapp/data/misc/favicon.ico'){
    echo '<head>';
    echo '<title>'.$title.' – DasNerdwork.net</title>';
    echo '<link id="favicon" rel="shortcut icon" href='.$favicon.'>';
    // if($css){ echo '<link rel="stylesheet" href="/clashapp/clash.css">'; }
    if($css){ echo '<link rel="stylesheet" href="/clashapp/css/output.css">'; }
    if($javascript){
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>';
        echo '<script type="text/javascript" src="../clashapp/clash.js"></script>';
    }
    echo '</head>';
}
?>