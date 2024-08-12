#!/usr/bin/env python3.10
from mongodb import MongoDBHelper
import json

# Initialize MongoDBHelper
mdb = MongoDBHelper()

champ_name = "Ahri"
current_patch_short = "^14.13"
current_patch = "14.13.1"

# Load the JSON data from the file
with open(f"/hdd1/clashapp/data/patch/{current_patch}/data/en_US/champion.json", "r") as file:
    champion_data_array = json.load(file)

# Create a dictionary to map champion names to their image file names
champion_array = {}
for champion_key, champion_info in champion_data_array['data'].items():
    champion_array[champion_info['name']] = champion_info['image']['full']

# Count documents in MongoDB
all_matches = mdb.count_documents('matches')
all_current_matches = mdb.count_documents('matches', {"info.gameVersion": {'$regex': f"^{current_patch_short}"}})

win_count = mdb.count_documents('matches', {
    "info.participants": {'$elemMatch': {"championName": champ_name, "win": True}},
    "info.gameVersion": {'$regex': f"^{current_patch_short}"}
})

lose_count = mdb.count_documents('matches', {
    "info.participants": {'$elemMatch': {"championName": champ_name, "win": False}},
    "info.gameVersion": {'$regex': f"^{current_patch_short}"}
})

ban_count = mdb.count_documents('matches', {
    "info.teams": {'$elemMatch': {"bans": {'$elemMatch': {"championId": 103}}}},
    "info.gameVersion": {'$regex': f"^{current_patch_short}"}
})

# Output the results
print(f"All Matches: {all_matches['count']}")
print(f"All Current Matches: {all_current_matches['count']}")
print(f"Win Count: {win_count['count']}")
print(f"Lose Count: {lose_count['count']}")
print(f"Ban Count: {ban_count['count']}")

# Define the aggregation pipeline
pipeline = [
    {'$match': {
        "info.gameVersion": {'$regex': current_patch_short},
        "info.participants": {'$elemMatch': {'championName': champ_name}}
    }},
    {'$unwind': '$info.participants'},
    {'$match': {'info.participants.championName': champ_name}},
    {'$project': {
        '_id': 0,
        'perks': {
            'statPerks': '$info.participants.perks.statPerks',
            'primaryStyle': {
                'perk1': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 0]}, 0]},
                'perk2': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 0]}, 1]},
                'perk3': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 0]}, 2]},
                'perk4': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 0]}, 3]},
            },
            'secondaryStyle': {
                'perk1': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 1]}, 0]},
                'perk2': {'$arrayElemAt': [{'$arrayElemAt': ['$info.participants.perks.styles.selections', 1]}, 1]},
            },
            'win': '$info.participants.win',
        },
    }},
    {'$group': {
        '_id': {
            'statPerks': '$perks.statPerks',
            'primaryStyle': {
                'perk1': '$perks.primaryStyle.perk1.perk',
                'perk2': '$perks.primaryStyle.perk2.perk',
                'perk3': '$perks.primaryStyle.perk3.perk',
                'perk4': '$perks.primaryStyle.perk4.perk',
            },
            'secondaryStyle': {
                'perk1': '$perks.secondaryStyle.perk1.perk',
                'perk2': '$perks.secondaryStyle.perk2.perk',
            },
        },
        'wins': {'$sum': {'$cond': [{'$eq': ['$perks.win', True]}, 1, 0]}},
        'losses': {'$sum': {'$cond': [{'$eq': ['$perks.win', False]}, 1, 0]}},
    }},
]

# Execute the aggregation
cursor = mdb.aggregate('matches', pipeline)

# Process the results
for document in cursor:
    perks = {
        'statPerks': document['_id']['statPerks'],
        'primaryStyle': {
            'perk1': document['_id']['primaryStyle']['perk1'],
            'perk2': document['_id']['primaryStyle']['perk2'],
            'perk3': document['_id']['primaryStyle']['perk3'],
            'perk4': document['_id']['primaryStyle']['perk4'],
        },
        'secondaryStyle': {
            'perk1': document['_id']['secondaryStyle']['perk1'],
            'perk2': document['_id']['secondaryStyle']['perk2'],
        },
    }
    wins = document['wins']
    losses = document['losses']
    total = wins + losses

    if total > 100:
        # You can now use the perk combination, wins, and losses data
        print("Perks:", perks)
        print(f"Wins: {wins}, Losses: {losses}\n")
