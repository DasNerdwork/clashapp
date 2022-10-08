<?php
$link = mysqli_connect('localhost', 'clashapp', 'F-))#pp!dat7g&CA', 'clashappdb');
//if connection is not successful
if (!$link) {
       die('Could not connect.');
}
//if connection is successfully
echo 'Connected successfully';
 
mysqli_close($link);
?>