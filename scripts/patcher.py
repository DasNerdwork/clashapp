# Necessary import block
from urllib.request import urlopen
from datetime import datetime
from pathlib import Path
from PIL import Image
import logging
import logging.handlers as handlers
import json
import requests
import os, sys
import tarfile
import shutil
import time

def convert_to_webp(source):
    """Convert image to WebP. Via https://www.webucator.com/tutorial/using-python-to-convert-images-to-webp/

    Args:
        source (pathlib.Path): Path to source image

    Returns:
        pathlib.Path: path to new image
    """
    try:
        with Image.open(source) as img:
            pass
    except (FileNotFoundError, OSError):
        raise ValueError("Unsupported file format")

    destination = source.with_suffix(".webp")

    image = Image.open(source)  # Open image
    width, height = image.size
    if(width < 16000 and height < 16000):
        image.save(destination, format="webp")  # Convert image to webp

    return destination

# Start count of whole program time
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

if (version == variables[0]): # If locale version equals live version
    logger.info("Up-to-date. Current live patch: " + version)
elif (not os.path.isdir(folder + variables[0]) or not os.path.isdir(folder + "lolpatch_" + variables[0][:-2])): # If new patch folders don't already exist, upgrade
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

    # Convert all .png and .jpg to .webp
    start_time = time.time()
    max_duration = 600 # 10 Minuten
    timed_out = False # Flag für Zeitüberschreitung

    # Conversion Part
    paths = Path('/hdd1/clashapp/data/patch/').glob("**/*.png")
    for path in paths:
        webp_path = convert_to_webp(path)
        if time.time() - start_time >= max_duration:
            timed_out = True
            break
    
    paths2 = Path('/hdd1/clashapp/data/patch/').glob("**/*.jpg")
    for path2 in paths2:
        webp_path2 = convert_to_webp(path2)
        if time.time() - start_time >= max_duration:
            timed_out = True
            break

    if timed_out:
        logger.info("Time limit exceeded. Converted all available .png and .jpg to .webp")
    else:
        logger.info("Converted all available .png and .jpg to .webp")

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
else:
    # Only update version.txt with newest patch
    logger.info("Outdated version.txt although database up-to-date")
    logger.info("Updating version.txt to current live patch")
    with open('/hdd1/clashapp/data/patch/version.txt', 'w') as f:
        f.write(variables[0])
    # End Patcher and calculate runtime
endpatcher = str(round(time.time() - start_patcher, 2))
logger.info("Ending patcher, run took: " + endpatcher + " seconds")
logger.info("---------------------------------------------------------------------------")