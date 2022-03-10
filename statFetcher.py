# Necessary import block
from datetime import datetime
import logging
import logging.handlers as handlers
import json
import os, sys
import time
import glob
import array

# Start count of whole program time
start_fetcher = time.time() 
# Preparing code statements for logging, formatting of log and 2 rotating log files with max 10MB filesize
logger = logging.getLogger('statFetcher.py')
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s] [%(name)s - %(levelname)s]: %(message)s", "%d.%m.%Y %H:%M:%S")
logHandler = handlers.RotatingFileHandler('/var/www/html/wordpress/clashapp/data/logs/statFetcher.log', maxBytes=10000000, backupCount=2)
logHandler.setLevel(logging.INFO)
logHandler.setFormatter(formatter)
logger.addHandler(logHandler)
logger.info("Starting statFetcher and initializing dicts, vars and arrays of stat names")

# Initializing dicts, vars and arrays
averageJsonDict = dict.fromkeys(['GENERAL', 'TOP', 'JUNGLE', 'MIDDLE', 'UTILITY', 'BOTTOM'])
challengeNameArray = [
    'abilityUses', 'acesBefore15Minutes', 'alliedJungleMonsterKills', 'baronTakedowns', 'bountyGold', 'buffsStolen', 'controlWardsPlaced', 'damagePerMinute', 'damageTakenOnTeamPercentage', 'deathsByEnemyChamps', 'dragonTakedowns',
    'earliestBaron', 'earlyLaningPhaseGoldExpAdvantage', 'effectiveHealAndShielding', 'elderDragonKillsWithOpposingSoul', 'elderDragonMultikills', 'enemyChampionImmobilizations', 'enemyJungleMonsterKills',
    'epicMonsterKillsWithin30SecondsOfSpawn', 'epicMonsterSteals', 'firstTurretKilledTime', 'flawlessAces', 'fullTeamTakedown', 'gameLength', 'goldPerMinute', 'hadAfkTeammate', 'initialBuffCount', 'initialCrabCount',
    'jungleCsBefore10Minutes', 'junglerTakedownsNearDamagedEpicMonster', 'kda', 'killParticipation', 'killsNearEnemyTurret', 'killsOnLanersEarlyJungleAsJungler', 'killsOnOtherLanesEarlyJungleAsLaner', 'killsUnderOwnTurret',
    'killsWithHelpFromEpicMonster', 'laneMinionsFirst10Minutes', 'laningPhaseGoldExpAdvantage', 'legendaryCount', 'maxCsAdvantageOnLaneOpponent', 'maxKillDeficit', 'maxLevelLeadLaneOpponent', 'multikills', 'multikillsAfterAggressiveFlash',
    'outerTurretExecutesBefore10Minutes', 'outnumberedKills', 'outnumberedNexusKill', 'perfectDragonSoulsTaken', 'riftHeraldTakedowns', 'scuttleCrabKills', 'skillshotsDodged', 'skillshotsHit', 'soloKills', 'stealthWardsPlaced',
    'takedownOnFirstTurret', 'takedowns', 'takedownsAfterGainingLevelAdvantage', 'takedownsBeforeJungleMinionSpawn', 'takedownsFirst25Minutes', 'teamBaronKills', 'teamDamagePercentage', 'teamElderDragonKills', 'teamRiftHeraldKills',
    'teleportTakedowns', 'turretPlatesTaken', 'turretsTakenWithRiftHerald', 'wardTakedowns', 'wardsGuarded', 'controlWardTimeCoverageInRiverOrEnemyHalf', 'earliestDragonTakedown', 'epicMonsterStolenWithoutSmite',
    'soloTurretsLategame', 'immobilizeAndKillWithAlly', 'killAfterHiddenWithAlly', 'knockEnemyIntoTeamAndKill', 'saveAllyFromDeath', 'survivedThreeImmobilizesInFight', 'tookLargeDamageSurvived']
statNameArray = [
    'assists', 'baronKills', 'consumablesPurchased', 'damageDealtToBuildings', 'damageDealtToObjectives', 'damageDealtToTurrets', 'damageSelfMitigated', 'deaths', 'detectorWardsPlaced', 'doubleKills', 'dragonKills', 'goldEarned', 'inhibitorKills',
    'inhibitorTakedowns', 'inhibitorsLost', 'killingSprees', 'kills', 'longestTimeSpentLiving', 'magicDamageDealt', 'magicDamageDealtToChampions', 'magicDamageTaken', 'neutralMinionsKilled', 'objectivesStolen', 'objectivesStolenAssists',
    'pentaKills', 'physicalDamageDealt', 'physicalDamageDealtToChampions', 'physicalDamageTaken', 'quadraKills', 'timeCCingOthers', 'totalDamageDealt', 'totalDamageDealtToChampions', 'totalDamageShieldedOnTeammates', 'totalDamageTaken',
    'totalHeal', 'totalHealsOnTeammates', 'totalMinionsKilled', 'totalTimeCCDealt', 'totalTimeSpentDead', 'tripleKills', 'trueDamageDealt', 'trueDamageDealtToChampions', 'trueDamageTaken', 'turretKills', 'turretTakedowns', 'turretsLost',
    'visionScore', 'visionWardsBoughtInGame', 'wardsKilled', 'wardsPlaced']
path = '/var/www/html/wordpress/clashapp/data/matches/'
counter = 1

# Stat iterator function to run over every match and collect every "challenge" or "stat" info of every player in "lane", then get average of it
def statIterator(lane):
    challengeDict = {}
    sortedChallengeDict = {}
    for filename in glob.glob(os.path.join(path, 'EUW1_*.json')): #only process .JSON files in folder.
        with open(filename, encoding='utf-8', mode='r') as currentFile:
            fileJson = currentFile.read()
            jdata = json.loads(fileJson)
        for player in jdata['info']['participants']:
            if (player.get('challenges')) and ((lane == "GENERAL") or (player['teamPosition'] == lane) or (player['individualPosition'] == lane) or (player['lane'] == lane)):
                for key, val in player['challenges'].items():
                    if key in challengeNameArray:
                        challengeDict.setdefault(key, []).append(val)
                for key, val in player.items():
                    if key in statNameArray:
                        challengeDict.setdefault(key, []).append(val)

    # Calculate rounded averages of values
    logger.info("Calculating averages of fetched data...")            
    for key, val in challengeDict.items():
        if ((sum(val)/len(val)) < 10):
            challengeDict[key] = round(sum(val)/len(val), 2)
        elif ((sum(val)/len(val)) < 100):
            challengeDict[key] = round(sum(val)/len(val), 1)
        else:
            challengeDict[key] = round(sum(val)/len(val))

    # Sort return array alphabetically
    for key in sorted(challengeDict):
        sortedChallengeDict[key] = challengeDict[key]

    return sortedChallengeDict
    
for key in averageJsonDict:
    logger.info("Start fetching of \"" + key.title() + "\" challenges and stats (" + str(counter) + "/" + str(len(averageJsonDict)) + ")")
    averageJsonDict[key] = statIterator(key)

    counter+=1

logger.info("Saving averageStats.json in var/www/html/wordpress/clashapp/data/misc")
with open('/var/www/html/wordpress/clashapp/data/misc/averageStats.json', 'w') as location:
    json.dump(averageJsonDict, location)

filesize = str(os.path.getsize('/var/www/html/wordpress/clashapp/data/misc/averageStats.json')/1024).split('.', 1)[0] # Get kB size of downloaded file
end_fetcher = str(round(time.time() - start_fetcher, 2))
logger.info("Successfully fetched average data. Time elapsed: " + end_fetcher + " seconds for " + filesize + " kB")
logger.info("---------------------------------------------------------------------------")