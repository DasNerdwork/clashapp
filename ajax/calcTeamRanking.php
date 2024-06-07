<?php
include_once('/hdd1/clashapp/src/functions.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Data Validation checks
if(isset($_POST['inTeamRanking'])){
    try {
        $inTeamRankingArray = json_decode($_POST['inTeamRanking'], true);
    } catch (ValueError $e) {
        die("Failed to decode inTeamRanking: " . $e->getMessage());
    }
} else {
    die("Missing post value iTR");
}
if(isset($_POST['csrf_token'])){
    if(!isValidCSRF($_POST['csrf_token'])){
        die("Invalid csrf_token");
    }
} else {
    die("Missing post value csrf");
}
// End of Data Validation checks

$responseArray = array();
$ranks = [
    'IRON' => 1, 'BRONZE' => 2, 'SILVER' => 3, 'GOLD' => 4, 'PLATINUM' => 5,
    'EMERALD' => 6, 'DIAMOND' => 7, 'MASTER' => 8, 'GRANDMASTER' => 9, 'CHALLENGER' => 10
];
$tiers = [ 'IV' => 1, 'III' => 2, 'II' => 3, 'I' => 4 ];

uasort($inTeamRankingArray, function ($a, $b) use ($ranks, $tiers) {
    $hasRankA = isset($a['RankedData']['HighestRank']);
    $hasRankB = isset($b['RankedData']['HighestRank']);

    if ($hasRankA && $hasRankB) {
        $rankA = $ranks[$a['RankedData']['HighestRank']];
        $rankB = $ranks[$b['RankedData']['HighestRank']];

        if ($rankA != $rankB) {
            return $rankB <=> $rankA;
        }
    }

    $hasRankNumberA = isset($a['RankedData']['RankNumber']);
    $hasRankNumberB = isset($b['RankedData']['RankNumber']);

    if ($hasRankNumberA && $hasRankNumberB) {
        $rankNumberA = $tiers[$a['RankedData']['RankNumber']];
        $rankNumberB = $tiers[$b['RankedData']['RankNumber']];

        if ($rankNumberA != $rankNumberB) {
            return $rankNumberB <=> $rankNumberA;
        }
    }

    $hasMatchscoreA = isset($a['Matchscore']);
    $hasMatchscoreB = isset($b['Matchscore']);

    if ($hasMatchscoreA && $hasMatchscoreB) {
        $matchscoreComparison = $b['Matchscore'] <=> $a['Matchscore'];
        if ($matchscoreComparison !== 0) {
            return $matchscoreComparison;
        }
    }

    $hasWinrateA = isset($a['RankedData']['Winrate']);
    $hasWinrateB = isset($b['RankedData']['Winrate']);

    if ($hasWinrateA && $hasWinrateB) {
        $winrateA = (float) $a['RankedData']['Winrate'];
        $winrateB = (float) $b['RankedData']['Winrate'];
        return $winrateB <=> $winrateA;
    }
});

$keys = array_keys($inTeamRankingArray);
$reducedArray = array_values($keys);

$responseArray["csrfToken"] = $_POST['csrf_token'];
// $responseArray["inTeamRanking"] = $inTeamRankingArray; // Uncomment if testing purposes are necessary
$responseArray["inTeamRanking"] = $reducedArray;

echo json_encode($responseArray);
?>