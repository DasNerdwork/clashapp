#!/usr/bin/env python3.10
# Necessary import block
from datetime import datetime
from pathlib import Path
from pymongo import MongoClient
import logging
import logging.handlers as handlers
import json
import os, sys
import time
import glob

# Start count of whole program time
start_fetcher = time.time() 
# Preparing code statements for logging, formatting of log and 2 rotating log files with max 10MB filesize
logger = logging.getLogger('statFetcher.py')
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s] [%(name)s - %(levelname)s]: %(message)s", "%d.%m.%Y %H:%M:%S")
logHandler = handlers.RotatingFileHandler('/hdd1/clashapp/data/logs/statFetcher.log', maxBytes=10000000, backupCount=2)
logHandler.setLevel(logging.INFO)
logHandler.setFormatter(formatter)
logger.addHandler(logHandler)
logger.info("Starting statFetcher and initializing dicts, vars and arrays of stat names")

class MongoDBHelper:
    def __init__(self):
        self.host = os.getenv('MDB_HOST')
        self.username = os.getenv('MDB_USER')
        self.password = os.getenv('MDB_PW')
        self.auth_source = os.getenv('MDB_DB')
        self.tls_ca_file = os.getenv('MDB_PATH')
        self.database_name = os.getenv('MDB_DB')
        self.client = self._connect_to_mongodb()
        self.mdb = self.database_name

    def _connect_to_mongodb(self):
        connection_uri = f"mongodb://{self.username}:{self.password}@{self.host}/{self.auth_source}?authMechanism=SCRAM-SHA-1&tls=true&tlsCAFile={self.tls_ca_file}"
        return MongoClient(connection_uri)

    def get_collection(self, collection_name):
        return self.client[self.database_name][collection_name]

mongo_helper = MongoDBHelper()

# Initializing dicts, vars and arrays
averageJsonDict = dict.fromkeys(['FILL', 'TOP', 'JUNGLE', 'MIDDLE', 'UTILITY', 'BOTTOM'])
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
championChallengeNameArray = [
    'abilityUses', 'alliedJungleMonsterKills', 'baronTakedowns', 'buffsStolen', 'completeSupportQuestInTime', 'controlWardsPlaced', 'damagePerMinute', 'damageTakenOnTeamPercentage', 'deathsByEnemyChamps', 'dodgeSkillShotsSmallWindow',
    'dragonTakedowns', 'effectiveHealAndShielding', 'enemyChampionImmobilizations', 'enemyJungleMonsterKills', 'epicMonsterKillsNearEnemyJungler', 'epicMonsterKillsWithin30SecondsOfSpawn', 'epicMonsterSteals', 'epicMonsterStolenWithoutSmite',
    'firstTurretKilledTime', 'flawlessAces', 'fullTeamTakedown', 'getTakedownsInAllLanesEarlyJungleAsLaner', 'goldPerMinute', 'immobilizeAndKillWithAlly', 'initialBuffCount', 'initialCrabCount', 'jungleCsBefore10Minutes', 'junglerKillsEarlyJungle',
    'junglerTakedownsNearDamagedEpicMonster', 'kda', 'killAfterHiddenWithAlly', 'killParticipation', 'killedChampTookFullTeamDamageSurvived', 'killsNearEnemyTurret', 'killsOnLanersEarlyJungleAsJungler', 'killsOnOtherLanesEarlyJungleAsLaner',
    'killsOnRecentlyHealedByAramPack', 'killsUnderOwnTurret', 'killsWithHelpFromEpicMonster', 'knockEnemyIntoTeamAndKill', 'landSkillShotsEarlyGame', 'laneMinionsFirst10Minutes', 'multiKillOneSpell', 'multiTurretRiftHeraldCount', 'multikills',
    'multikillsAfterAggressiveFlash', 'outerTurretExecutesBefore10Minutes', 'outnumberedKills', 'outnumberedNexusKill', 'perfectDragonSoulsTaken', 'pickKillWithAlly', 'quickCleanse', 'riftHeraldTakedowns', 'saveAllyFromDeath', 'scuttleCrabKills',
    'skillshotsDodged', 'skillshotsHit', 'soloBaronKills', 'soloKills', 'soloTurretsLategame', 'stealthWardsPlaced', 'survivedThreeImmobilizesInFight', 'takedownOnFirstTurret', 'takedowns', 'takedownsAfterGainingLevelAdvantage',
    'takedownsBeforeJungleMinionSpawn', 'takedownsFirst25Minutes', 'takedownsInAlcove', 'takedownsInEnemyFountain', 'teamDamagePercentage', 'threeWardsOneSweeperCount', 'tookLargeDamageSurvived', 'turretPlatesTaken', 'turretsTakenWithRiftHerald',
    'twentyMinionsIn3SecondsCount', 'unseenRecalls', 'visionScorePerMinute', 'wardTakedowns', 'wardTakedownsBefore20M']
statNameArray = [
    'assists', 'baronKills', 'consumablesPurchased', 'damageDealtToBuildings', 'damageDealtToObjectives', 'damageDealtToTurrets', 'damageSelfMitigated', 'deaths', 'detectorWardsPlaced', 'doubleKills', 'dragonKills', 'goldEarned', 'inhibitorKills',
    'inhibitorTakedowns', 'inhibitorsLost', 'killingSprees', 'kills', 'longestTimeSpentLiving', 'magicDamageDealt', 'magicDamageDealtToChampions', 'magicDamageTaken', 'neutralMinionsKilled', 'objectivesStolen', 'objectivesStolenAssists',
    'pentaKills', 'physicalDamageDealt', 'physicalDamageDealtToChampions', 'physicalDamageTaken', 'quadraKills', 'timeCCingOthers', 'totalDamageDealt', 'totalDamageDealtToChampions', 'totalDamageShieldedOnTeammates', 'totalDamageTaken',
    'totalHeal', 'totalHealsOnTeammates', 'totalMinionsKilled', 'totalTimeCCDealt', 'totalTimeSpentDead', 'tripleKills', 'trueDamageDealt', 'trueDamageDealtToChampions', 'trueDamageTaken', 'turretKills', 'turretTakedowns', 'turretsLost',
    'visionScore', 'visionWardsBoughtInGame', 'wardsKilled', 'wardsPlaced']
championStatNameArray = [
    'assists', 'baronKills', 'champExperience', 'consumablesPurchased', 'damageDealtToBuildings', 'damageDealtToObjectives', 'damageDealtToTurrets', 'damageSelfMitigated', 'deaths', 'detectorWardsPlaced', 'doubleKills', 'dragonKills',
    'goldEarned', 'goldSpent', 'inhibitorKills', 'inhibitorTakedowns', 'inhibitorsLost', 'itemsPurchased', 'kills', 'largestCriticalStrike', 'longestTimeSpentLiving', 'magicDamageDealt', 'magicDamageDealtToChampions', 'magicDamageTaken',
    'neutralMinionsKilled', 'objectivesStolen', 'pentaKills', 'physicalDamageDealt', 'physicalDamageDealtToChampions', 'physicalDamageTaken', 'quadraKills', 'spell1Casts', 'spell2Casts', 'spell3Casts', 'spell4Casts', 'timeCCingOthers',
    'totalDamageDealt', 'totalDamageDealtToChampions', 'totalDamageShieldedOnTeammates', 'totalDamageTaken', 'totalHeal', 'totalHealsOnTeammates', 'totalMinionsKilled', 'totalTimeCCDealt', 'totalTimeSpentDead', 'totalUnitsHealed', 'trueDamageDealt',
    'trueDamageDealtToChampions', 'trueDamageTaken', 'turretKills', 'turretTakedowns', 'turretsLost']
championDict = {}
sortedChampionDict = {}
currentPatch = Path('/hdd1/clashapp/data/patch/version.txt').read_text()
championJsonPath = '/hdd1/clashapp/data/patch/' + currentPatch + '/data/de_DE/champion.json'
counter = 1

# Stat iterator function to run over every match and collect every "challenge" or "stat" info of every player in "lane", then get average of it
def statIterator(lane):
    challengeDict = {}
    sortedChallengeDict = {}
    match_collection = mongo_helper.get_collection('matches')
    cursor = match_collection.find({})
    for document in cursor:
        jdata = document
        if("info" not in jdata):
            continue
        for player in jdata['info']['participants']:
            if (player.get('challenges')) and ((lane == "FILL") or (player['teamPosition'] == lane) or (player['individualPosition'] == lane) or (player['lane'] == lane)):
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

with open(championJsonPath, encoding='utf-8', mode='r') as championData:
    championJson = json.loads(championData.read())
    for key in championJson['data']:
        if (key == "Fiddlesticks"):
            championDict['FiddleSticks'] = {}
            sortedChampionDict['FiddleSticks'] = {}
        else:
            championDict[key] = {}
            sortedChampionDict[key] = {}

def championStatIterator():
    logger.info("Starting fetching of champion specific average data")  
    match_collection = mongo_helper.get_collection('matches')
    cursor = match_collection.find({})
    for document in cursor:
        jdata = document
        if("info" not in jdata):
            continue
        for player in jdata['info']['participants']:
            for championName in championDict:
                if championName == player['championName']:
                    championDict[championName].setdefault('localMatches', 0)
                    championDict[championName]['localMatches'] += 1
                    if (player.get('challenges')):
                        for key, val in player['challenges'].items():
                            if key in championChallengeNameArray:
                                championDict[championName].setdefault(key, []).append(val)
                        for key, val in player.items():
                            if key in championStatNameArray:
                                championDict[championName].setdefault(key, []).append(val)

    # Calculate rounded averages of values
    logger.info("Calculating averages of champion data...")    
    for championName in championDict:       
        for key, val in championDict[championName].items():
            if (key != 'localMatches') :
                if ((sum(val)/len(val)) < 10):
                    championDict[championName][key] = round(sum(val)/len(val), 2)
                elif ((sum(val)/len(val)) < 100):
                    championDict[championName][key] = round(sum(val)/len(val), 1)
                else:
                    championDict[championName][key] = round(sum(val)/len(val))

        # Sort return array alphabetically
        for key in sorted(championDict[championName]):
            sortedChampionDict[championName][key] = championDict[championName][key]
    logger.info("Saving averageChampionStats.json in hdd1/clashapp/data/misc")
    return sortedChampionDict

start_champ = time.time() 
with open('/hdd1/clashapp/data/misc/averageChampionStats.json', 'w') as location:
    json.dump(championStatIterator(), location)
champ_filesize = str(os.path.getsize('/hdd1/clashapp/data/misc/averageChampionStats.json')/1024).split('.', 1)[0] # Get kB size of downloaded file
end_champ = str(round(time.time() - start_champ, 2))
logger.info("Successfully fetched champion averages. Time elapsed: " + end_champ + " seconds for " + champ_filesize + " kB")
 
for key in averageJsonDict:
    logger.info("Start fetching of \"" + key.title() + "\" challenges and stats (" + str(counter) + "/" + str(len(averageJsonDict)) + ")")
    averageJsonDict[key] = statIterator(key)

    counter+=1

logger.info("Saving averageStats.json in hdd1/clashapp/data/misc")
with open('/hdd1/clashapp/data/misc/averageStats.json', 'w') as location:
    json.dump(averageJsonDict, location)

filesize = str(os.path.getsize('/hdd1/clashapp/data/misc/averageStats.json')/1024).split('.', 1)[0] # Get kB size of downloaded file
end_fetcher = str(round(time.time() - start_fetcher, 2))
logger.info("Successfully fetched average data. Time elapsed: " + end_fetcher + " seconds for " + filesize + " kB")
logger.info("---------------------------------------------------------------------------")