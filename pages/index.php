<?php if (session_status() === PHP_SESSION_NONE) session_start(); 
include_once('/hdd1/clashapp/src/functions.php');
include_once('/hdd1/clashapp/src/apiFunctions.php');
require_once '/hdd1/clashapp/db/mongo-db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.avif)";
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

include('/hdd1/clashapp/templates/footer.php');