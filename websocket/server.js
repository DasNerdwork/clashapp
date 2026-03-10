import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';
import { promises as fsPromises } from 'fs';
import ansiRegex from 'ansi-regex';
import util from 'util';
import '/hdd1/clashapp/websocket/consoleHandler.js';
import mongodb from 'mongodb';
import crypto from 'crypto';

const mongoURL = process.env.MDB_URL;
const logStream = fs.createWriteStream('/hdd1/clashapp/data/logs/server.log', { flags: 'a' });
const logPath = '/hdd1/clashapp/data/logs/server.log';
const attackLogPath = '/hdd1/clashapp/logs/attacks.log';
const maxFileSize = 5 * 1024 * 1024;
const roomPlayers = {};
const roomSettings = {};
const correctAnswerTimers = {};

// FIX: wss und validChamps auf Modul-Ebene deklarieren
let wss = null;
let validChamps = null;
let mongoClient = null;

async function initMongoDB() {
    try {
        mongoClient = new mongodb.MongoClient(mongoURL, {
            maxPoolSize: 10,
            minPoolSize: 2,
            serverSelectionTimeoutMS: 5000,
            socketTimeoutMS: 45000
        });
        await mongoClient.connect();
        console.log("[WS-Server]: MongoDB connected successfully with persistent connection", new Date().toLocaleTimeString());
    } catch (error) {
        console.error("[WS-Server]: Failed to connect to MongoDB:", error, new Date().toLocaleTimeString());
        process.exit(1);
    }
}

initMongoDB().then(() => {
    startWS();
}).catch(error => {
    console.error("[WS-Server]: Failed to initialize:", error);
    process.exit(1);
});

function startWS() {
    const server = createServer({
      cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
      key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
    }).listen(8083);

    // FIX: Modul-Variablen befüllen statt lokale consts
    wss = new WebSocketServer({ server });
    const currentPatch = fs.readFileSync('/hdd1/clashapp/data/patch/version.txt', 'utf-8');
    validChamps = JSON.parse(fs.readFileSync('/hdd1/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];

    console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully started the Websocket-Server!", new Date().toLocaleTimeString());

    var lastClient = "";

    wss.on('connection', function connection(ws, req) {
      const clientIP = req.headers['x-forwarded-for']
        ? req.headers['x-forwarded-for'].split(/\s*,\s*/)[0]
        : req.socket.remoteAddress;

      console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Client websocket connection initiated from \x1b[4m%s\x1b[0m:%d', new Date().toLocaleTimeString(), clientIP, ws._socket.remotePort);
      console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Total clients connected: %d", new Date().toLocaleTimeString(), wss.clients.size);

      ws.on('message', function message(data) {
        const newClient = clientIP + ':' + req.socket.remotePort;
        let dataAsString = data.toString();
        if(Array.from(dataAsString)[0] == "{"){
          var dataAsJSON = JSON.parse(dataAsString);
          let requestMessage = "";
          if(dataAsJSON.request != "minigames"){
            switch (dataAsJSON.request) {
              case "firstConnect": requestMessage = "\x1b[36mfirstConnect\x1b[0m"; break;
              case "add": requestMessage = "\x1b[32madd\x1b[0m"; break;
              case "remove": requestMessage = "\x1b[31mremove\x1b[0m"; break;
              case "rate": requestMessage = "\x1b[33mrate\x1b[0m"; break;
              case "swap": requestMessage = "\x1b[35mswap\x1b[0m"; break;
            }
            if (typeof dataAsJSON.champname === 'undefined') {
              var nameForMessage = dataAsJSON.name;
            } else {
              var nameForMessage = dataAsJSON.champname;
            }
            let message = '{"teamid":"'+dataAsJSON.teamid+'","name":"'+nameForMessage+'","request":"'+requestMessage+'"}';
            if(newClient == lastClient){
              console.log('\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), message);
            } else {
              console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Received following data from %s\n\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), newClient, new Date().toLocaleTimeString(), message);
              lastClient = newClient;
            }
          }

          if(dataAsJSON.request == "add"){
            if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
              ws.send('{"status":"InvalidTeamID"}');
            } else {
              var checkForInjection = true;
              const normalizedChampName = dataAsJSON.champname ?
                String(dataAsJSON.champname).toLowerCase().replace(/\s+/g, '') : '';

              for (var champ in validChamps) {
                if (validChamps.hasOwnProperty(champ)) {
                  const validChamp = validChamps[champ];
                  const normalizedValidName = validChamp.name.toLowerCase().replace(/\s+/g, '');
                  if(dataAsJSON.champid == champ && normalizedChampName == normalizedValidName){
                    checkForInjection = false;
                    break;
                  }
                }
              }
              if(checkForInjection){
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Code Injection Detected, Logging IP (%s)", new Date().toLocaleTimeString(), clientIP);
                ws.send('{"status":"CodeInjectionDetected"}');
                var attackMessage = `[${new Date()}] IP: ${clientIP}, Team ID: ${dataAsJSON.teamid}, Champion: ${dataAsJSON.champid}\n`;
                fs.appendFileSync(attackLogPath, attackMessage, 'utf8');
              } else {
                // HMAC-Validierung für Champion-Namen
                const hmac = crypto.createHmac('sha256', process.env.SECURITY_SALT || 'default_salt');
                hmac.update(dataAsJSON.champid + dataAsJSON.champname);
                const expectedHash = hmac.digest('hex');
                
                // Speichere Hash im WebSocket-Scope für Validierung
                ws._championHash = expectedHash;
                
                readTeamData(dataAsJSON.teamid)
                  .then((localDataAsJson) => {
                    var elementInArray = false;
                    for (var key in localDataAsJson["SuggestedBans"]) {
                      if (localDataAsJson["SuggestedBans"].hasOwnProperty(key)) {
                        if (dataAsJSON.champid == localDataAsJson["SuggestedBans"][key].id) {
                          elementInArray = true;
                          break;
                        }
                      }
                    }
                    if (elementInArray) {
                      console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Provided element already exists in local file -> skipping", new Date().toLocaleTimeString());
                      ws.send('{"status":"ElementAlreadyInArray"}');
                      return Promise.resolve({ status: 'Skipping' });
                    } else {
                      if (Object.keys(localDataAsJson).length >= 10) {
                        console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Maximum elements exceeded -> skipping", new Date().toLocaleTimeString());
                        ws.send('{"status":"MaximumElementsExceeded"}');
                        return Promise.resolve({ status: 'Skipping' });
                      } else {
                        return addToFile(dataAsJSON.teamid, dataAsJSON.champid, dataAsJSON.champname);
                      }
                    }
                  }).then((response) => {
                    if (response && response.status === 'Success') {
                      console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully added ${dataAsJSON.champname} to ${dataAsJSON.teamid}`, new Date().toLocaleTimeString());
                    } else if (response && response.status === 'Error') {
                      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Skipping add of champion`, new Date().toLocaleTimeString());
                    }
                  })
                  .catch((error) => {
                    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Unexpected error: ${error}`, new Date().toLocaleTimeString());
                  });
                ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                wss.clients.forEach(function each(client) {
                  if(client.location == dataAsJSON.teamid && client != ws){
                    client.send('{"status":"Message","message":"added %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
                  }
                });
              }
            }

          } else if(dataAsJSON.request == "remove"){
            if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
              ws.send('{"status":"InvalidTeamID"}');
            } else {
              var checkForInjection = true;
              const normalizedChampName = dataAsJSON.champname ?
                String(dataAsJSON.champname).toLowerCase().replace(/\s+/g, '') : '';

              for (var champ in validChamps) {
                if (validChamps.hasOwnProperty(champ)) {
                  const validChamp = validChamps[champ];
                  const normalizedValidName = validChamp.name.toLowerCase().replace(/\s+/g, '');
                  if(dataAsJSON.champid == champ && normalizedChampName == normalizedValidName){
                    checkForInjection = false;
                    break;
                  }
                }
              }
              if(checkForInjection){
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Code Injection Detected, Logging IP (%s)", new Date().toLocaleTimeString(), clientIP);
                ws.send('{"status":"CodeInjectionDetected"}');
                var attackMessage = `[${new Date()}] IP: ${clientIP}, Team ID: ${dataAsJSON.teamid}, Champion: ${dataAsJSON.champid}\n`;
                fs.appendFileSync(attackLogPath, attackMessage, 'utf8');
              } else {
                readTeamData(dataAsJSON.teamid)
                  .then((localDataAsJson) => {
                    var elementInArray = false;
                    for (var key in localDataAsJson["SuggestedBans"]) {
                      if (localDataAsJson["SuggestedBans"].hasOwnProperty(key)) {
                        if (dataAsJSON.champid == localDataAsJson["SuggestedBans"][key].id) {
                          elementInArray = true;
                          break;
                        }
                      }
                    }
                    if (!elementInArray) {
                      console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Provided element does not exist in local file -> skipping", new Date().toLocaleTimeString());
                      ws.send('{"status":"ElementNotInArray"}');
                      return Promise.resolve({ status: 'Skipping' });
                    } else {
                      return removeFromFile(dataAsJSON.teamid, dataAsJSON.champid, dataAsJSON.champname);
                    }
                  })
                  .then((response) => {
                    if (response && response.status === 'Success') {
                      console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully removed ${dataAsJSON.champname} from ${dataAsJSON.teamid}`, new Date().toLocaleTimeString());
                    } else if (response && response.status === 'Error') {
                      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error removing champion: ${response.status}`, new Date().toLocaleTimeString());
                    }
                  })
                  .catch((error) => {
                    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Unexpected error: ${error}`, new Date().toLocaleTimeString());
                  });
                broadcastUpdate(dataAsJSON.teamid);
                ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                wss.clients.forEach(function each(client) {
                  if(client.location == dataAsJSON.teamid && client != ws){
                    client.send('{"status":"Message","message":"removed %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
                  }
                });
              }
            }

          } else if(dataAsJSON.request == "swap"){
            if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
              ws.send('{"status":"InvalidTeamID"}');
            } else {
              swapInFile(dataAsJSON.teamid, dataAsJSON.fromID, dataAsJSON.toID)
                .then((response) => {
                  if (response.status === 'Success') {
                    console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully swapped champions in ${dataAsJSON.teamid}`, new Date().toLocaleTimeString());
                  } else {
                    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error swapping champions: ${response.status}`, new Date().toLocaleTimeString());
                  }
                })
                .catch((error) => {
                  console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Unexpected error: ${error}`);
                });
              broadcastUpdate(dataAsJSON.teamid);
              ws.send('{"status":"Success"}');
              wss.clients.forEach(function each(client) {
                if(client.location == dataAsJSON.teamid && client != ws){
                  client.send('{"status":"Message","message":"swapped %1 with %2.","champ1":"'+dataAsJSON.fromName+'","champ2":"'+dataAsJSON.toName+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
                }
              });
            }

          } else if(dataAsJSON.request == "rate"){
            if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
              ws.send('{"status":"InvalidTeamID"}');
            } else {
              updateTeamRating(dataAsJSON.teamid, dataAsJSON.hash, dataAsJSON.rating)
                .then(success => {
                  if (success) {
                    ws.send('{"status":"Success"}');
                  } else {
                    ws.send('{"status":"Error"}');
                  }
                })
                .catch(error => {
                  ws.send('{"status":"Error"}');
                });
              broadcastUpdate(dataAsJSON.teamid);
            }

          } else if(dataAsJSON.request == "firstConnect"){
            ws.location = dataAsJSON.teamid;
            var possibleColors = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
            wss.clients.forEach(function each(client) {
              if(possibleColors.includes(client.color)){
                var colorIndex = possibleColors.indexOf(client.color);
                if (colorIndex > -1) {
                  possibleColors.splice(colorIndex, 1);
                }
              }
            });
            if(possibleColors.length >= 1){
              ws.color = possibleColors[Math.floor(Math.random()*possibleColors.length)];
            } else {
              const colorList = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
              ws.color = colorList[Math.floor(Math.random()*colorList.length)];
            }
            if(dataAsJSON.name == ""){
              var possibleNames = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
              wss.clients.forEach(function each(client) {
                if(client.location == dataAsJSON.teamid){
                  if(possibleNames.includes(client.name)){
                    var index = possibleNames.indexOf(client.name);
                    if (index > -1) {
                      possibleNames.splice(index, 1);
                    }
                  }
                }
              });
              if(possibleNames.length >= 1){
                ws.name = possibleNames[Math.floor(Math.random()*possibleNames.length)];
              } else {
                const nameList = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
                ws.name = nameList[Math.floor(Math.random()*nameList.length)];
              }
            } else {
              ws.name = dataAsJSON.name;
            }
            ws.send('{"status":"FirstConnect","name":"'+ws.name+'","color":"'+ws.color+'"}');

            readTeamData(dataAsJSON.teamid)
              .then((localDataAsJson) => {
                ws.send(JSON.stringify(localDataAsJson));
              });

            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid && client != ws){
                client.send('{"status":"Message","message":"joined the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
              }
            });

          } else if(dataAsJSON.request == "lock"){
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid && client != ws){
                client.send('{"status":"Lock","message":"locked %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'","index":"'+dataAsJSON.index+'"}');
              }
            });
            return toggleChampionStatus(dataAsJSON.teamid, dataAsJSON.champid, "locked");

          } else if(dataAsJSON.request == "unlock"){
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid && client != ws){
                client.send('{"status":"Unlock","message":"unlocked %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'","index":"'+dataAsJSON.index+'"}');
              }
            });
            return toggleChampionStatus(dataAsJSON.teamid, dataAsJSON.champid, "unlocked");

          } else if(dataAsJSON.request == "minigames"){
            ws.location = dataAsJSON.roomid;
            if (!roomPlayers[dataAsJSON.roomid]) {
              roomPlayers[dataAsJSON.roomid] = [];
            }
            if (!roomSettings[dataAsJSON.roomid]) {
              roomSettings[dataAsJSON.roomid] = {};
            }
            if(!roomSettings[dataAsJSON.roomid]["Difficulty"]){
              roomSettings[dataAsJSON.roomid]["Difficulty"] = dataAsJSON.difficulty;
            }

            if(dataAsJSON.action == "generate"){
              async function loadChampionData() {
                const championDataPath = `/hdd1/clashapp/data/patch/${currentPatch}/data/en_US/champion.json`;
                try {
                    const championData = await fsPromises.readFile(championDataPath, 'utf8');
                    return JSON.parse(championData);
                } catch (error) {
                    console.error('Error reading champion data:', error);
                    return null;
                }
              }

              async function getRandomChampion() {
                const championData = await loadChampionData();
                if (!championData) {
                  console.log('Error loading champion data');
                  return null;
                }
                const championKeys = Object.keys(championData.data);
                const randomChampionKey = championKeys[Math.floor(Math.random() * championKeys.length)];
                const randomChampion = championData.data[randomChampionKey];
                return randomChampion;
              }

              async function generateRandomChampion() {
                const randomChampion = await getRandomChampion();
                if (!randomChampion) {
                    console.log('Error getting random champion data');
                    return;
                }
                const championName = randomChampion.name;
                const imagePath = `/hdd1/clashapp/data/patch/${currentPatch}/img/champion/${randomChampion.image.full}`;
                const imageBuffer = fs.readFileSync(imagePath);
                const imageBase64 = imageBuffer.toString('base64');
                const imageDataUrl = `data:image/png;base64,${imageBase64}`;

                const pixelationSettings = {
                  status: 'PixelateAndGenerate',
                  championName: btoa(championName),
                  imagePath: btoa(imageDataUrl),
                  pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"]
                };
                wss.clients.forEach(function each(client) {
                  if (client.location == dataAsJSON.roomid) {
                    client.send(JSON.stringify(pixelationSettings));
                  }
                });
              }
              generateRandomChampion();
            }

            var possibleColors = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
            wss.clients.forEach(function each(client) {
              if(possibleColors.includes(client.color)){
                var colorIndex = possibleColors.indexOf(client.color);
                if (colorIndex > -1) {
                  possibleColors.splice(colorIndex, 1);
                }
              }
            });
            if(possibleColors.length >= 1){
              ws.color = possibleColors[Math.floor(Math.random()*possibleColors.length)];
            } else {
              const colorList = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
              ws.color = colorList[Math.floor(Math.random()*colorList.length)];
            }
            if(dataAsJSON.name == ""){
              var possibleNames = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
              wss.clients.forEach(function each(client) {
                if(client.location == dataAsJSON.roomid){
                  if(possibleNames.includes(client.name)){
                    var index = possibleNames.indexOf(client.name);
                    if (index > -1) {
                      possibleNames.splice(index, 1);
                    }
                  }
                }
              });
              if(possibleNames.length >= 1){
                ws.name = possibleNames[Math.floor(Math.random()*possibleNames.length)];
              } else {
                const nameList = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
                ws.name = nameList[Math.floor(Math.random()*nameList.length)];
              }
            } else {
              ws.name = dataAsJSON.name;
            }
            ws.send('{"status":"RoomJoined","name":"'+ws.name+'","location":"'+ws.location+'","message":"(You) joined the room.","color":"'+ws.color+'","difficulty":"'+roomSettings[dataAsJSON.roomid]["Difficulty"]+'"}');
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.roomid && client != ws){
                client.send('{"status":"Message","message":"joined the room.","name":"'+ws.name+'","color":"'+ws.color+'"}');
              }
            });

            roomPlayers[dataAsJSON.roomid].push(ws.name);

            const playerListUpdate = {
              status: 'PlayerListUpdate',
              players: roomPlayers[dataAsJSON.roomid],
              colors: {}
            };

            wss.clients.forEach(function each(client) {
              if (client.location == dataAsJSON.roomid) {
                playerListUpdate.colors[client.name] = client.color;
              }
            });

            wss.clients.forEach(function each(client) {
              if (client.location == dataAsJSON.roomid) {
                client.send(JSON.stringify(playerListUpdate));
              }
            });

          } else if(dataAsJSON.request == "correctAnswer"){
            async function loadChampionData() {
              const championDataPath = `/hdd1/clashapp/data/patch/${currentPatch}/data/en_US/champion.json`;
              try {
                  const championData = await fsPromises.readFile(championDataPath, 'utf8');
                  return JSON.parse(championData);
              } catch (error) {
                  console.error('Error reading champion data:', error);
                  return null;
              }
            }
            async function getRandomChampion() {
              const championData = await loadChampionData();
              if (!championData) {
                console.log('Error loading champion data');
                return null;
              }
              const championKeys = Object.keys(championData.data);
              const randomChampionKey = championKeys[Math.floor(Math.random() * championKeys.length)];
              const randomChampion = championData.data[randomChampionKey];
              return randomChampion;
            }

            if (!correctAnswerTimers[dataAsJSON.roomid]) {
              correctAnswerTimers[dataAsJSON.roomid] = 0;
            }

            const currentTime = Date.now();

            if (currentTime - correctAnswerTimers[dataAsJSON.roomid] >= 4000) {
                correctAnswerTimers[dataAsJSON.roomid] = currentTime;

                wss.clients.forEach(function each(client) {
                    if (client.location == dataAsJSON.roomid) {
                        client.send('{"status":"Message","message":"guessed the correct answer: %1","answer":"' + dataAsJSON.answer + '","name":"' + ws.name + '","color":"'+ws.color+'","bonuspoints":"'+dataAsJSON.bonuspoints+'"}');
                    }
                });

                async function generateNewRandomChampionAndNotify() {
                    const randomChampion = await getRandomChampion();
                    if (!randomChampion) {
                        console.log('Error getting random champion data');
                        return;
                    }

                    const championName = randomChampion.name;
                    const imagePath = `/hdd1/clashapp/data/patch/${currentPatch}/img/champion/${randomChampion.image.full}`;
                    const imageBuffer = fs.readFileSync(imagePath);
                    const imageBase64 = imageBuffer.toString('base64');
                    const imageDataUrl = `data:image/png;base64,${imageBase64}`;

                    const pixelationSettings = {
                        status: 'PixelateAndGenerateNew',
                        championName: btoa(championName),
                        imagePath: btoa(imageDataUrl), 
                        pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"]
                    };
                    wss.clients.forEach(function each(client) {
                        if (client.location == dataAsJSON.roomid) {
                            client.send(JSON.stringify(pixelationSettings));
                        }
                    });
                }
                generateNewRandomChampionAndNotify();
            }

          } else if(dataAsJSON.request == "changeDifficulty"){
            const validDifficulties = ['easy', 'medium', 'hard'];
            if (validDifficulties.includes(dataAsJSON.difficulty)) {
              if (roomSettings.hasOwnProperty(dataAsJSON.roomid)) {
                if (!roomSettings[dataAsJSON.roomid].hasOwnProperty('Difficulty')) {
                    console.error(`Room ${dataAsJSON.roomid} does not have the 'Difficulty' attribute`);
                } else {
                    roomSettings[dataAsJSON.roomid]['Difficulty'] = dataAsJSON.difficulty;
                }
              } else {
                  console.error(`Room ${dataAsJSON.roomid} does not exist`);
              }
            }
          }

        } else {
          if(newClient == lastClient){
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: Data: %s', new Date().toLocaleTimeString(), data.toString());
          } else {
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Received following data from %s:%d\n\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), clientIP, ws._socket.remotePort, new Date().toLocaleTimeString(), data.toString());
            lastClient = newClient;
          }
        }
      });

      ws.send('Handshake successful: Server received client request and answered.');

      ws.on('close', function close() {
        console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Connection of client closed from %s:%d', new Date().toLocaleTimeString(), clientIP, ws._socket.remotePort);
        console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Total clients connected: %d", new Date().toLocaleTimeString(), wss.clients.size);

        const closedRoom = ws.location;

        if (roomPlayers[closedRoom]) {
            roomPlayers[closedRoom] = roomPlayers[closedRoom].filter(player => player !== ws.name);
        }

        const remainingPlayerColors = {};
        wss.clients.forEach(function each(client) {
          if (client.location === closedRoom && client !== ws) {
            remainingPlayerColors[client.name] = client.color;
          }
        });

        const playerListUpdate = {
          status: 'PlayerListUpdate',
          players: roomPlayers[closedRoom],
          colors: remainingPlayerColors
        };

        wss.clients.forEach(function each(client) {
          if(client.location == ws.location && client != ws){
            if (roomPlayers[closedRoom]) {
              client.send('{"status":"Message","message":"left the room.","name":"'+ws.name+'","color":"'+ws.color+'"}');
              client.send(JSON.stringify(playerListUpdate));
            } else {
              client.send('{"status":"Message","message":"left the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
            }
          }
        });
      });
    });
}

// FIX: getMongoDB ohne isConnected() - existiert nicht im neuen MongoDB Treiber
async function getMongoDB() {
    if (!mongoClient) {
        await initMongoDB();
    }
    try {
        // Ping um zu prüfen ob Verbindung noch steht
        await mongoClient.db('admin').command({ ping: 1 });
    } catch (err) {
        console.error("[WS-Server]: MongoDB connection lost, reconnecting...", new Date().toLocaleTimeString());
        await initMongoDB();
    }
    return mongoClient.db('clashappdb');
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// DATABASE OPERATION FUNCTIONS ////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function updateTeamRating(teamId, hash, rating) {
  return new Promise(async (resolve, reject) => {
    try {
      const db = await getMongoDB();
      const teamsCollection = db.collection('teams');
      const filter = { TeamID: teamId };
      const update = {
        $set: { [`Rating.${hash}`]: rating },
        $inc: { 'Status': 1 }
      };
      const result = await teamsCollection.updateOne(filter, update);
      if (result.modifiedCount === 1) {
        if(rating == 0){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: A client removed their rating score from %s", new Date().toLocaleTimeString(), teamId);
        } else {
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: A client rated %s with a score of %d", new Date().toLocaleTimeString(), teamId, rating);
        }
        resolve(true);
      } else {
        console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Database document for ${teamId} not found or unable to update`, new Date().toLocaleTimeString());
        resolve(false);
      }
    } catch (error) {
      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error updating team rating: ${error}`, new Date().toLocaleTimeString());
      reject(error);
    }
  });
}

function addToFile(teamId, champId, champName) {
  return new Promise((resolve, reject) => {
    getMongoDB()
      .then(db => {
        const teamsCollection = db.collection('teams');
        const filter = { TeamID: teamId };
        const update = {
          $addToSet: {
            'SuggestedBans': { id: champId, name: champName, status: "unlocked"}
          },
          $inc: { Status: 1 }
        };
        return teamsCollection.updateOne(filter, update);
      })
      .then((result) => {
        if (result.modifiedCount === 1) {
          broadcastUpdate(teamId);
          resolve({ status: 'Success', champid: champId, champname: champName });
        } else {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Team document for ${teamId} not found or unable to update`, new Date().toLocaleTimeString());
          resolve({ status: 'Error' });
        }
      })
      .catch((error) => {
        if(error == "MongoServerError: Document failed validation"){
          console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Maximum elements in array (Document Validation Error)`, new Date().toLocaleTimeString());
          resolve({ status: 'Error' });
        } else {
          console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error adding champion to team file: ${error}`, new Date().toLocaleTimeString());
          reject({ status: 'Error' });
        }
      });
  });
}

function removeFromFile(teamId, champId, champName) {
  return new Promise((resolve, reject) => {
    getMongoDB()
      .then(db => {
        const teamsCollection = db.collection('teams');
        const filter = { TeamID: teamId };
        const update = {
          $pull: { 'SuggestedBans': { id: champId } },
          $inc: { Status: 1 }
        };
        return teamsCollection.updateOne(filter, update);
      })
      .then((result) => {
        if (result.modifiedCount === 1) {
          broadcastUpdate(teamId);
          resolve({ status: 'Success', champid: champId, champname: champName });
        } else {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Team document for ${teamId} not found or unable to update`, new Date().toLocaleTimeString());
          resolve({ status: 'Error' });
        }
      })
      .catch((error) => {
        console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error removing champion from team file: ${error}`, new Date().toLocaleTimeString());
        reject({ status: 'Error' });
      });
  });
}

function swapInFile(teamId, fromId, toId) {
  return new Promise(async (resolve, reject) => {
    try {
      const db = await getMongoDB();
      const teamsCollection = db.collection('teams');
      const filter = { TeamID: teamId };
      const teamDocument = await teamsCollection.findOne(filter);
      const suggestedBans = teamDocument.SuggestedBans;
      const fromIndex = suggestedBans.findIndex(champion => champion.id === fromId);
      const toIndex = suggestedBans.findIndex(champion => champion.id === toId);
      if (fromIndex === -1 || toIndex === -1) {
        console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Champions not found in ${teamId}`, new Date().toLocaleTimeString());
        resolve({ status: 'Champions not found' });
        return;
      }
      const temp = suggestedBans[fromIndex];
      suggestedBans[fromIndex] = suggestedBans[toIndex];
      suggestedBans[toIndex] = temp;
      await teamsCollection.updateOne(filter, { $set: { SuggestedBans: suggestedBans } });
      broadcastUpdate(teamId);
      resolve({ status: 'Success' });
    } catch (error) {
      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error swapping elements in team file: ${error}`, new Date().toLocaleTimeString());
      reject({ status: 'Error' });
    }
  });
}

function readTeamData(teamId) {
  return new Promise(async (resolve, reject) => {
    try {
      const db = await getMongoDB();
      const teamsCollection = db.collection('teams');
      const filter = { TeamID: teamId };
      const teamData = await teamsCollection.findOne(filter);
      if (teamData) {
        resolve(teamData);
      } else {
        console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Team document for ${teamId} not found`, new Date().toLocaleTimeString());
        resolve([]);
      }
    } catch (error) {
      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error reading team data: ${error}`, new Date().toLocaleTimeString());
      reject(error);
    }
  });
}

function toggleChampionStatus(teamId, champId, newStatus) {
  return new Promise((resolve, reject) => {
    getMongoDB()
      .then(db => {
        const teamsCollection = db.collection('teams');
        const filter = { TeamID: teamId, 'SuggestedBans.id': champId };
        const update = {
          $set: {
            'SuggestedBans.$.status': newStatus
          }
        };
        return teamsCollection.updateOne(filter, update);
      })
      .then((result) => {
        if (result.modifiedCount === 1) {
          broadcastUpdate(teamId);
          resolve({ status: 'Success', champId, newStatus });
        } else {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Team document for ${teamId} not found or unable to update`, new Date().toLocaleTimeString());
          resolve({ status: 'Error' });
        }
      })
      .catch((error) => {
        if (error == "MongoServerError: Document failed validation") {
          console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Maximum elements in array (Document Validation Error)`, new Date().toLocaleTimeString());
          resolve({ status: 'Error' });
        } else {
          console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error updating champion status: ${error}`, new Date().toLocaleTimeString());
          reject({ status: 'Error' });
        }
      });
  });
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// BROADCAST / UTILS ///////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function broadcastUpdate(clientsTeamID) {
  readTeamData(clientsTeamID)
    .then((localDataAsJson) => {
      wss.clients.forEach(function each(client) {
        if (client.location == clientsTeamID) {
          client.send(JSON.stringify(localDataAsJson));
        }
      });
    })
    .catch((error) => {
      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error broadcasting update: ${error}`, new Date().toLocaleTimeString());
    });
}

export function broadcastAll(message){
  wss.clients.forEach(function each(client) {
    client.send(message);
  });
  console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Following message sent to all connected users: %s", new Date().toLocaleTimeString(), message);
}

function trimLogFileIfNeeded() {
  const fileSize = fs.statSync(logPath).size;
  if (fileSize > maxFileSize) {
    const fileData = fs.readFileSync(logPath, 'utf8').split('\n');
    const trimmedData = fileData.slice(Math.ceil(fileData.length / 2)).join('\n');
    fs.writeFileSync(logPath, trimmedData, 'utf8');
    console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Maximum logsize exceeded, removed first half of it.", new Date().toLocaleTimeString());
  }
}

// Redirect console output to the log file
const originalConsoleLog = console.log;
console.log = function () {
  trimLogFileIfNeeded();
  const logMessage = `${util.format.apply(null, arguments)}`;
  logStream.write(`${logMessage.replace(ansiRegex(), '')}\n`);
  originalConsoleLog.apply(console, arguments);
};

// Attach the uncaught exception handler
process.on('uncaughtException', handleCrash);
process.on('unhandledRejection', handleShutdown);

function handleCrash(error) {
  var currentTime = new Date().toLocaleTimeString();
  const crashMessage = `[${currentTime}] [Server Crash]: ${error.stack}\n`;
  fs.appendFileSync(logPath, crashMessage, 'utf8');
  process.exit(1);
}

function handleShutdown(error){
  var currentTime = new Date().toLocaleTimeString();
  const crashMessage = `[${currentTime}] [Server Shutdown]: ${error.stack}\n`;
  fs.appendFileSync(logPath, crashMessage, 'utf8');
}