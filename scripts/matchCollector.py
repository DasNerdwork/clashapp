#!/usr/bin/env python3.10
# Necessary import block
from pathlib import Path
from pymongo import MongoClient, errors
import datetime
import logging
import logging.handlers as handlers
import requests
import random
import json
import sys
import os
import time
import glob

# Start count of whole program time
start_fetcher = time.time() 
# Preparing code statements for logging, formatting of log and 2 rotating log files with max 10MB filesize
logger = logging.getLogger('matchCollector ')
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s] [%(name)s - %(levelname)s]: %(message)s", "%d.%m.%Y %H:%M:%S")
logHandler = handlers.RotatingFileHandler('/hdd1/clashapp/data/logs/matchDownloader.log', maxBytes=10000000, backupCount=2)
logHandler.setLevel(logging.INFO)
logHandler.setFormatter(formatter)
logger.addHandler(logHandler)
logger.info("Starting matchCollector...")

def handle_exception(exc_type, exc_value, exc_traceback):
    if issubclass(exc_type, KeyboardInterrupt):
        sys.__excepthook__(exc_type, exc_value, exc_traceback)
        return
    logger.error("Uncaught exception", exc_info=(exc_type, exc_value, exc_traceback))

def read_pid(filepath='/hdd1/clashapp/scripts/current.pid'):
    if os.path.exists(filepath):
        with open(filepath, 'r') as file:
            return file.read().strip()
    else:
        return None

def write_pid(puuid, filepath='/hdd1/clashapp/scripts/current.pid'):
    with open(filepath, 'w') as file:
        file.write(puuid)

def get_timestamp_by_index(index):
        version_history_path = '/hdd1/clashapp/data/patch/version_history.txt'
        
        if os.path.exists(version_history_path):
            with open(version_history_path, 'r') as f:
                version_history = json.load(f)
            
            if not version_history:
                raise ValueError("Version history is empty")
            
            # Convert version_history to a list of tuples for indexing
            sorted_versions = sorted(version_history.items(), key=lambda x: x[1])
            
            # Check if the index is valid
            if index < 0 or index >= len(sorted_versions):
                raise IndexError(f"Index {index} is out of range for version history")
            
            # Retrieve the version and timestamp at the specified index
            version_at_index, timestamp_at_index = sorted_versions[index]
            
            return timestamp_at_index
        else:
            raise FileNotFoundError(f"{version_history_path} does not exist")

class MongoDBHelper:
    def __init__(self):
        self.host = os.getenv('MDB_HOST')
        self.username = os.getenv('MDB_USER')
        self.password = os.getenv('MDB_PW')
        self.auth_source = os.getenv('MDB_DB')
        self.tls_ca_file = os.getenv('MDB_PATH')
        self.database_name = os.getenv('MDB_DB')
        self.client = self._connect_to_mongodb()
        self.db = self.client[self.database_name]

    def _connect_to_mongodb(self):
        connection_uri = f"mongodb://{self.username}:{self.password}@{self.host}/{self.auth_source}?authMechanism=SCRAM-SHA-1&tls=true&tlsCAFile={self.tls_ca_file}"
        return MongoClient(connection_uri)
    
    def count_documents(self, collection_name):
        collection = self.db[collection_name]
        try:
            count = collection.count_documents({})
            return {
                'success': True,
                'message': f'Successfully counted documents in collection {collection_name}',
                'count': count
            }
        except Exception as e:
            return {
                'success': False,
                'message': f'An error occurred while counting documents: {str(e)}'
            }

    def find_document_by_field(self, collection_name, field, value):
        collection = self.db[collection_name]
        try:
            document = collection.find_one({field: value})
            if document:
                return {
                    'success': True,
                    'message': f'Successfully found document by field {field}',
                    'document': document
                }
            else:
                return {
                    'success': False,
                    'message': f'Unable to find field {field} in document'
                }
        except Exception as e:
            return {
                'success': False,
                'message': f'An error occurred: {str(e)}'
            }

    def insert_document(self, collection_name, document):
        collection = self.db[collection_name]
        try:
            collection.insert_one(document)
            return {
                'success': True,
                'message': f'Successfully inserted document into {collection_name}'
            }
        except errors.DuplicateKeyError:
            return {
                'success': False,
                'message': f'Document with the same key already exists in {collection_name}'
            }
        except Exception as e:
            return {
                'success': False,
                'message': f'An error occurred: {str(e)}'
            }   

class RiotAPI:
    def __init__(self):
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
            "Accept-Language": "de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept-Charset": "application/x-www-form-urlencoded; charset=UTF-8",
            "Origin": "https://clashscout.com/",
            "X-Riot-Token": os.getenv('API_KEY')
        }
        self.pid_file = '/hdd1/clashapp/scripts/current.pid' 
        self.log_path = '/hdd1/clashapp/data/logs/matchDownloader.log'

    def get_match_ids(self, max_match_ids):
        current_puuid = read_pid(self.pid_file)
        match_ids = {
            'ranked_solo': [],
            'ranked_flex': [],
            'clash': []
        }
        queues = {
            'ranked_solo': 420,
            'ranked_flex': 440,
            'clash': 700
        }
        start = 0
        start_time = get_timestamp_by_index(2)
        match_count = 100
        retry_attempts = 0

        while start < max_match_ids:
            if (start + 100) > max_match_ids:
                match_count = max_match_ids - start

            for queue_name, queue_id in queues.items():
                if queues[queue_name] is not None and len(match_ids[queue_name]) < max_match_ids:
                    url = f"https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/{current_puuid}/ids?startTime={start_time}&queue={queue_id}&start={start}&count={match_count}"
                    response = requests.get(url, headers=self.headers)
                    time.sleep(0.051)

                    if response.status_code == 429:
                        retry_after_value = int(response.headers.get('Retry-After', 10))
                        logger.warning(f"Rate limit got exceeded -> Now sleeping for {retry_after_value} second - Status: {response.status_code} Too Many Requests")
                        time.sleep(retry_after_value)
                        retry_attempts += 1
                        if retry_attempts >= 3:
                            return []  # Return an empty list if the retry limit is reached
                    else:
                        retry_attempts = 0
                        if response.status_code == 200:
                            match_id_list = response.json()
                            match_ids[queue_name].extend(match_id_list)

                            if len(match_id_list) < match_count:
                                queues[queue_name] = None  # Mark this queue as finished
                        else:
                            logger.error(f"Error fetching match IDs for queue {queue_name}: {response.status_code}")
                            logger.error(f"{response.json()}")

            start += 100

        combined_match_ids = sum(match_ids.values(), [])
        combined_match_ids = sorted(combined_match_ids, reverse=True)
        filtered_matchids = [matchid for matchid in combined_match_ids if matchid.startswith('EUW1_')]
        return filtered_matchids[:max_match_ids]
    
    def download_matches_by_id(self, match_ids, username=None):
        for match_id in match_ids:
            if not mdb.find_document_by_field("matches", 'metadata.matchId', match_id)["success"]:
                response = requests.get(f"https://europe.api.riotgames.com/lol/match/v5/matches/{match_id}", headers=self.headers)
                http_code = response.status_code

                if http_code == 429:
                    retry_after_value = int(response.headers.get('Retry-After', 10))
                    logger.warning(f"Rate limit got exceeded -> Now sleeping for {retry_after_value} second - Status: {response.status_code} Too Many Requests")
                    time.sleep(retry_after_value)
                    response = requests.get(f"https://europe.api.riotgames.com/lol/match/v5/matches/{match_id}", headers=self.headers)
                    http_code = response.status_code

                    if http_code == 429:
                        retry_after_value = int(response.headers.get('Retry-After', 10))
                        logger.warning(f"Rate limit got exceeded -> Now sleeping for {retry_after_value} second - Status: {response.status_code} Too Many Requests")
                        time.sleep(retry_after_value)
                        response = requests.get(f"https://europe.api.riotgames.com/lol/match/v5/matches/{match_id}", headers=self.headers)
                        http_code = response.status_code

                logger.info(f"Downloading new matchdata from \"{username}\" via {match_id}.json - Status: {http_code}")

                if http_code == 200:
                    database_add = mdb.insert_document('matches', response.json())
                    if not database_add['success']:
                        logger.info(f"{database_add['message']}")
                else:
                    logger.warning(f"{match_id} received HTTP-Code: {http_code} - Skipping")
                time.sleep(1.2)
            else:
                logger.info(f"{match_id}.json already existing - Skipping")

        return True

sys.excepthook = handle_exception

mdb = MongoDBHelper()
riotapi = RiotAPI()
max_retries = 3
retry_count = 0

while retry_count < max_retries:
    try:
        # Check the current count of documents in the "matches" collection
        match_count = mdb.count_documents('matches')['count']
        
        if match_count >= 1000000:
            break  # Exit the loop if the count is below 2000
        
        # Fetch new match IDs and download matches
        matchids = riotapi.get_match_ids(100)

        if not matchids:
            logger.error("Unexpected Error: No match IDs could be fetched")
            time.sleep(60)  # Wait for 60 seconds before retrying
            retry_count += 1
            continue

        riotapi.download_matches_by_id(matchids, read_pid())
        
        # Attempt to find a random EUW1 match and its content
        random_match_id = random.choice(matchids)
        random_match_content = mdb.find_document_by_field('matches', 'metadata.matchId', random_match_id)
        
        if random_match_content['success']:
            # Select a random player's PUUID and write it to PID
            random_player = random.choice(random_match_content['document']['info']['participants'])
            write_pid(random_player['puuid'])
        else:
            # Increment retry count if finding the match content was unsuccessful
            retry_count += 1

        # Reset retry count on successful execution
        retry_count = 0

    except Exception as e:
        logger.error(f"An error occurred: {e}")
        retry_count += 1
        if retry_count < max_retries:
            logger.info(f"Retrying in 1 minute... (Attempt {retry_count + 1}/{max_retries})")
            time.sleep(60)
        else:
            logger.error("Max retries reached. Exiting the script.")

# Log the final count of documents in the "matches" collection
logger.info(f"Final count of documents in 'matches' collection: {mdb.count_documents('matches')['count']}")