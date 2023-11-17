<?php if (session_status() === PHP_SESSION_NONE) session_start(); 
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/mongo-db.php';
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

$mdb = new MongoDBHelper();

echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.webp)";
document.body.style.backgroundRepeat = "no-repeat";
document.body.style.backgroundPosition = "50% 20%";
document.body.style.backgroundSize = "40%";
</script>
';

$challengeNameArray = [
    'abilityUses', 'acesBefore15Minutes', 'alliedJungleMonsterKills', 'baronTakedowns', 'bountyGold', 'buffsStolen', 'controlWardsPlaced', 'damagePerMinute', 'damageTakenOnTeamPercentage', 'deathsByEnemyChamps', 'dragonTakedowns',
    'earliestBaron', 'earlyLaningPhaseGoldExpAdvantage', 'effectiveHealAndShielding', 'elderDragonKillsWithOpposingSoul', 'elderDragonMultikills', 'enemyChampionImmobilizations', 'enemyJungleMonsterKills',
    'epicMonsterKillsWithin30SecondsOfSpawn', 'epicMonsterSteals', 'firstTurretKilledTime', 'flawlessAces', 'fullTeamTakedown', 'gameLength', 'goldPerMinute', 'hadAfkTeammate', 'initialBuffCount', 'initialCrabCount',
    'jungleCsBefore10Minutes', 'junglerTakedownsNearDamagedEpicMonster', 'kda', 'killParticipation', 'killsNearEnemyTurret', 'killsOnLanersEarlyJungleAsJungler', 'killsOnOtherLanesEarlyJungleAsLaner', 'killsUnderOwnTurret',
    'killsWithHelpFromEpicMonster', 'laneMinionsFirst10Minutes', 'laningPhaseGoldExpAdvantage', 'legendaryCount', 'maxCsAdvantageOnLaneOpponent', 'maxKillDeficit', 'maxLevelLeadLaneOpponent', 'multikills', 'multikillsAfterAggressiveFlash',
    'outerTurretExecutesBefore10Minutes', 'outnumberedKills', 'outnumberedNexusKill', 'perfectDragonSoulsTaken', 'riftHeraldTakedowns', 'scuttleCrabKills', 'skillshotsDodged', 'skillshotsHit', 'soloKills', 'stealthWardsPlaced',
    'takedownOnFirstTurret', 'takedowns', 'takedownsAfterGainingLevelAdvantage', 'takedownsBeforeJungleMinionSpawn', 'takedownsFirst25Minutes', 'teamBaronKills', 'teamDamagePercentage', 'teamElderDragonKills', 'teamRiftHeraldKills',
    'teleportTakedowns', 'turretPlatesTaken', 'turretsTakenWithRiftHerald', 'wardTakedowns', 'wardsGuarded', 'controlWardTimeCoverageInRiverOrEnemyHalf', 'earliestDragonTakedown', 'epicMonsterStolenWithoutSmite',
    'soloTurretsLategame', 'immobilizeAndKillWithAlly', 'killAfterHiddenWithAlly', 'knockEnemyIntoTeamAndKill', 'saveAllyFromDeath', 'survivedThreeImmobilizesInFight', 'tookLargeDamageSurvived'];
$statNameArray = [
    'assists', 'baronKills', 'consumablesPurchased', 'damageDealtToBuildings', 'damageDealtToObjectives', 'damageDealtToTurrets', 'damageSelfMitigated', 'deaths', 'detectorWardsPlaced', 'doubleKills', 'dragonKills', 'goldEarned', 'inhibitorKills',
    'inhibitorTakedowns', 'inhibitorsLost', 'killingSprees', 'kills', 'longestTimeSpentLiving', 'magicDamageDealt', 'magicDamageDealtToChampions', 'magicDamageTaken', 'neutralMinionsKilled', 'objectivesStolen', 'objectivesStolenAssists',
    'pentaKills', 'physicalDamageDealt', 'physicalDamageDealtToChampions', 'physicalDamageTaken', 'quadraKills', 'timeCCingOthers', 'totalDamageDealt', 'totalDamageDealtToChampions', 'totalDamageShieldedOnTeammates', 'totalDamageTaken',
    'totalHeal', 'totalHealsOnTeammates', 'totalMinionsKilled', 'totalTimeCCDealt', 'totalTimeSpentDead', 'tripleKills', 'trueDamageDealt', 'trueDamageDealtToChampions', 'trueDamageTaken', 'turretKills', 'turretTakedowns', 'turretsLost',
    'visionScore', 'visionWardsBoughtInGame', 'wardsKilled', 'wardsPlaced'];

$averageStatsJson = json_decode(file_get_contents('/hdd1/clashapp/data/misc/averageStats.json'), true);

$lane = 'FILL';
$pipeline = [
    [
        '$unwind' => '$info.participants',
    ],
    [
        '$match' => [
            '$or' => [
                ['info.participants.teamPosition' => $lane],
                ['info.participants.individualPosition' => $lane],
                ['info.participants.lane' => $lane],
            ],
        ],
    ],
    [
        '$group' => [
            '_id' => null,
            'abilityUses' => ['$avg' => '$info.participants.challenges.abilityUses'],
            'acesBefore15Minutes' => ['$avg' => '$info.participants.challenges.acesBefore15Minutes'],
            'assists' => ['$avg' => '$info.participants.assists'],
            'baronKills' => ['$avg' => '$info.participants.baronKills'],
            // Add more aggregation expressions for other challenges and stats here
        ],
    ],
];

// Execute the aggregation pipeline
$cursor = $mdb->aggregate('matches', $pipeline);

// Fetch the results
$result = $cursor->toArray();

// Return the result as JSON
echo "<pre>";
// print_r($result);
echo "<pre>";



include('/hdd1/clashapp/templates/footer.php');
?>