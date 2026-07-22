<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '/hdd1/clashapp/src/functions.php';

function respond($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

// CSRF-Check analog zum bestehenden Muster
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    respond(['status' => 'error', 'message' => 'invalid_csrf'], 403);
}

$name = trim($_POST['gameName'] ?? '');
$tag  = strtoupper(trim($_POST['tagLine'] ?? ''));

// #EUW-Autovervollständigung serverseitig absichern:
// leer oder Präfix von "EUW" -> EUW
if ($tag === '' || strpos('EUW', $tag) === 0) $tag = 'EUW';

if (mb_strlen($name) < 3 || mb_strlen($name) > 16 || !preg_match('/^[0-9A-Z]{2,5}$/', $tag)) {
    respond(['status' => 'invalid'], 400);
}

// Permanente Livedemo-Ausnahme
if (mb_strtolower($name) === 'dasnerdwork' && $tag === 'EUW') {
    respond(['status' => 'ok', 'redirect' => '/team/test']);
}

$playerData = API::getPlayerData("riot-id", $name."#".$tag);
if (empty($playerData) || !isset($playerData["PUUID"])) {
    respond(['status' => 'not_found'], 404);
}

// In Clash-Team registriert? -> Teamseite, sonst Profil
$teamId = API::getTeamIdByPUUID($playerData["PUUID"]);
if ($teamId !== null) {
    respond(['status' => 'ok', 'redirect' => '/team/'.rawurlencode($teamId)]);
}

respond([
    'status'   => 'ok',
    'redirect' => '/profile/'.rawurlencode(mb_strtolower($playerData["GameName"])).'/'.rawurlencode(mb_strtolower($playerData["Tag"]))
]);