import os
import json

# Verzeichnispfad zu den JSON-Dateien
data_directory = '/hdd1/clashapp/data/player/'

# Ausgabedatei für die gesammelten Paare
output_file = '/hdd1/clashapp/data/player/autosuggest.json'

# Initialisiere ein leeres Dictionary für die Paare
result_data = {}

# Durchlaufe alle JSON-Dateien im Verzeichnis
for filename in os.listdir(data_directory):
    if filename.endswith('.json'):
        file_path = os.path.join(data_directory, filename)
        
        with open(file_path, 'r') as json_file:
            try:
                data = json.load(json_file)
                if 'PlayerData' in data and 'Name' in data['PlayerData'] and 'Icon' in data['PlayerData']:
                    name = data['PlayerData']['Name']
                    icon = data['PlayerData']['Icon']
                    result_data[name] = icon
            except json.JSONDecodeError as e:
                print(f"Fehler beim Analysieren von {filename}: {e}")

# Speichere die gesammelten Paare in der Ausgabedatei
with open(output_file, 'w') as output_json_file:
    json.dump(result_data, output_json_file, indent=4)