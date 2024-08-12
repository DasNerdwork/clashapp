# Necessary import block
from urllib.request import urlopen
from datetime import datetime
from pathlib import Path
from PIL import Image
from concurrent.futures import ThreadPoolExecutor
from pymongo import MongoClient
import fnmatch
import pillow_avif
import logging
import logging.handlers as handlers
import json
import subprocess
import requests
import os, sys
import tarfile
import shutil
import time

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
    
    def get_oldest_version_prefix(self):
        version_history_path = '/hdd1/clashapp/data/patch/version_history.txt'
        
        if os.path.exists(version_history_path):
            with open(version_history_path, 'r') as f:
                version_history = json.load(f)
            
            if version_history:
                oldest_version = sorted(version_history.items(), key=lambda x: x[1])[0][0]
                oldest_version_prefix = oldest_version.split('.')[0] + '.' + oldest_version.split('.')[1]
                return oldest_version_prefix
            else:
                logger.error("Version history is empty")
                raise ValueError("Version history is empty")
        else:
            logger.error(f"{version_history_path} does not exist")
            raise FileNotFoundError(f"{version_history_path} does not exist")
    
    def delete_outdated_documents(self, collection_name):
        collection = self.db[collection_name]
        try:
            oldest_version_prefix = self.get_oldest_version_prefix()
            result = collection.delete_many({
                "info.gameVersion": {
                    "$not": {"$regex": f"^{oldest_version_prefix}"}
                }
            })
            return {
                'success': True,
                'count': result.deleted_count
            }
        except Exception as e:
            return {
                'success': False,
            }

def handle_exception(exc_type, exc_value, exc_traceback):
    if issubclass(exc_type, KeyboardInterrupt):
        sys.__excepthook__(exc_type, exc_value, exc_traceback)
        return
    logger.error("Uncaught exception", exc_info=(exc_type, exc_value, exc_traceback))
    
def convert_to_avif(source):
    try:
        with Image.open(source) as img:
            pass
    except (FileNotFoundError, OSError):
        raise ValueError("Unsupported file format")

    root, ext = os.path.splitext(source)
    destination = f"{root}.avif"

    # Check if AVIF file already exists
    if os.path.exists(destination):
        # print(f"AVIF file already exists: {destination}")
        return None

    image = Image.open(source)  # Open image
    width, height = image.size
    if width < 16000 and height < 16000:
        image.save(destination, format="AVIF", quality=60)  # Convert image to AVIF

    return destination

def process_files(file_paths):
    with ThreadPoolExecutor() as executor:
        executor.map(convert_to_avif, file_paths)

# Function to update abbreviations.json based on champion.json
def update_abbreviations(newPatch):
    abbreviations_path = "/hdd1/clashapp/data/misc/abbreviations.json"
    champions_path = "/hdd1/clashapp/data/patch/"+newPatch+"/data/en_US/champion.json"

    with open(abbreviations_path, "r", encoding="utf-8") as abbr_file:
        abbreviations_data = json.load(abbr_file)

    with open(champions_path, "r") as champions_file:
        champions_data = json.load(champions_file)

    # Iterate through champions in champion.json
    for champion_id, champion_info in champions_data["data"].items():
        # Use the "name" as the key for abbreviations
        champion_name = champion_info["name"]

        # Ensure each champion has an entry in abbreviations.json
        if champion_name not in abbreviations_data:
            abbreviations_data[champion_name] = {"abbr": []}

        # Convert champion tags to lowercase and add ", " between tags
        tags = [tag.lower() for tag in champion_info["tags"]]

        # Update abbreviations.json if tags are not already present
        for tag in tags:
            if tag not in abbreviations_data[champion_name]["abbr"]:
                abbreviations_data[champion_name]["abbr"].append(tag)

    # Sort champions alphabetically
    abbreviations_data = dict(sorted(abbreviations_data.items()))

    # Move "Last Updated" to the end and update the version
    last_updated = abbreviations_data.pop("Last Updated", None)
    if last_updated:
        abbreviations_data["Last Updated"] = {"Patch": newPatch}  # Set to the latest version

    # Write updated abbreviations.json with proper encoding
    with open(abbreviations_path, "w", encoding="utf-8") as abbr_file:
        json.dump(abbreviations_data, abbr_file, separators=(",", ":"), ensure_ascii=False) # seperators attribute to reduce filesize

    return True

def update_version_history(current_version):
    version_history_path = '/hdd1/clashapp/data/patch/version_history.txt'
    
    if os.path.exists(version_history_path):
        with open(version_history_path, 'r') as f:
            version_history = json.load(f)
    else:
        version_history = {}
    
    timestamp = int(time.time())
    version_history[current_version] = timestamp
    
    # Keep only the last 5 versions
    if len(version_history) > 5:
        sorted_versions = sorted(version_history.items(), key=lambda x: x[1], reverse=True)
        version_history = dict(sorted_versions[:5])
    
    with open(version_history_path, 'w') as f:
        json.dump(version_history, f, separators=(",", ":"))


# Start count of whole program time and set exception handling
sys.excepthook = handle_exception
start_patcher = time.time() 
# Preparing code statements for logging, formatting of log and 2 rotating log files with max 10MB filesize
logger = logging.getLogger('Patcher.py')
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s] [%(name)s - %(levelname)s]: %(message)s", "%d.%m.%Y %H:%M:%S")
logHandler = handlers.RotatingFileHandler('/hdd1/clashapp/data/logs/patcher.log', maxBytes=10000000, backupCount=2)
logHandler.setLevel(logging.INFO)
logHandler.setFormatter(formatter)
logger.addHandler(logHandler)
logger.info("Starting patcher and initializing variables")
# Initializing of variables
url = "http://ddragon.leagueoflegends.com/api/versions.json"
json_file = urlopen(url)
variables = json.load(json_file)
json_file.close()
folder = "/hdd1/clashapp/data/patch/"
logger.info("Comparing locale with live patch version") # Comparing locale with live patch version
with open('/hdd1/clashapp/data/patch/version.txt', 'r') as v:
    version = v.read()
dryRun = False
# Check for exec parameters
if __name__ == "__main__":
    if "--abbr" in sys.argv:
        logger.info("Dry Abbreviation Generation")
        abbr_updated = update_abbreviations(version)
        sys.exit()

if (version == variables[0] and not dryRun): # If locale version equals live version
    logger.info("Up-to-date. Current live patch: " + version)
elif (not os.path.isdir(folder + variables[0]) or not os.path.isdir(folder + "lolpatch_" + variables[0][:-2]) or dryRun): # If new patch folders don't already exist, upgrade
    logger.info("Found new live patch: " + variables[0] + " - Old: " + version)
    logger.info("Starting download of database upgrade...")
    start_download = time.time() # Start time calculation of tgz archive download
    url = 'https://ddragon.leagueoflegends.com/cdn/dragontail-' + variables[0] + '.tgz' # Grabpath
    target_path = '/hdd1/clashapp/data/patch/' + variables[0] + '.tar.gz' # Extractpath
    response = requests.get(url, stream=True)
    if (response.status_code == 200): # If files exist online
        with open(target_path, 'wb') as f:
            f.write(response.raw.read())
    end_download = str(round(time.time() - start_download, 2)) # Calculate time the download took
    filesize = str(os.path.getsize(target_path)/1024**2).split('.', 1)[0] # Get MB size of downloaded file
    logger.info("Successfully downloaded new files. Time elapsed: " + end_download + " seconds for " + filesize + " MB")
    
    # End of download and Start of extraction
    logger.info("Starting extraction of archives...")
    start_extraction = time.time()
    tar = tarfile.open(target_path, "r:gz")
    tar.extractall(path='/hdd1/clashapp/data/patch/')
    tar.close()
    end_extraction = str(round(time.time() - start_extraction, 2))
    logger.info("All files extracted and overwritten. Time elapsed: " + end_extraction + " seconds")

    # Convert all .png and .jpg to .avif
    start_time = time.time()
    max_duration = 600 # 10 Minuten
    timed_out = False # Flag für Zeitüberschreitung

    # Conversion Part
    logger.info("Starting image conversion of all available .png and .jpg images")
    png_paths = list(Path('/hdd1/clashapp/data/patch/').glob("**/*.png"))
    jpg_paths = list(Path('/hdd1/clashapp/data/patch/').glob("**/*.jpg"))

    all_paths = png_paths + jpg_paths

    process_files(all_paths)

    if time.time() - start_time >= max_duration:
        timed_out = True

    if timed_out:
        logger.warning("Time limit exceeded. Converted all available .png and .jpg to .avif")
    else:
        logger.info("Converted all available .png and .jpg to .avif")

    # End of extraction and start of old file deletion
    os.remove(target_path) # Delete tar.gz
    if (os.path.isdir(folder + variables[0])): # If we got new database files
        shutil.rmtree(folder + version, ignore_errors=True) # Delete old database files
    if (os.path.isdir(folder + "lolpatch_" + variables[0][:-2])): # If we got more new database files
        shutil.rmtree(folder + "lolpatch_" + version[:-2], ignore_errors=True) # Delete more old database files
    logger.info("Old directories deleted (/" + version + ", /lolpatch_" + version[:-2] + ")")

    # End of old file deletion, update version.txt with newest patch
    logger.info("Updating version.txt to current live patch")
    with open('/hdd1/clashapp/data/patch/version.txt', 'w') as f:
        f.write(variables[0])
    logger.info("Updating version_history.txt")
    update_version_history(variables[0])

    # Update abbreviations.json with newest information from champion.json of new patch
    abbr_updated = update_abbreviations(variables[0])
    if(abbr_updated):
        logger.info("Abbreviations.json updated successfully")
    else:
        logger.warning("Failed updating Abbreviations.json")

    # Delete all outdated matchids from database
    mdb = MongoDBHelper()
    delete_outdated = mdb.delete_outdated_documents('matches')
    if(delete_outdated['success']):
        logger.info(f"Successfully deleted {delete_outdated['count']} outdated match documents from database")
    else:
        logger.warning("Failed deleting outdated match documents")

    # Restart WebSocket Server after update
    try:
        subprocess.run("pm2 restart 'WS-Server'", shell=True, check=True)
        logger.info("WebSocket server restarted successfully.")
    except subprocess.CalledProcessError as e:
        logger.info(f"Error restarting WebSocket server: {e}")
else:
    # Only update version.txt with newest patch
    logger.info("Outdated version.txt although database up-to-date")
    logger.info("Updating version.txt to current live patch")
    with open('/hdd1/clashapp/data/patch/version.txt', 'w') as f:
        f.write(variables[0])
    logger.info("Updating version_history.txt")
    update_version_history(variables[0])
    # End Patcher and calculate runtime
endpatcher = str(round(time.time() - start_patcher, 2))
logger.info("Ending patcher, run took: " + endpatcher + " seconds")
logger.info("---------------------------------------------------------------------------")