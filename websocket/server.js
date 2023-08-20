import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';
import ansiRegex from 'ansi-regex';
import util from 'util';
import '/hdd1/clashapp/websocket/consoleHandler.js';

// Create a write stream to the log file
const logStream = fs.createWriteStream('/hdd1/clashapp/data/logs/server.log', { flags: 'a' });
const logPath = '/hdd1/clashapp/data/logs/server.log';
const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
const roomPlayers = {}; // Initializes an object to store connected players for each room

// Attach the uncaught exception handler
process.on('uncaughtException', handleCrash);

// Redirect console output to the log file
const originalConsoleLog = console.log;
console.log = function () {
  trimLogFileIfNeeded();
  const logMessage = `${util.format.apply(null, arguments)}`;
  logStream.write(`${logMessage.replace(ansiRegex(), '')}\n`);
  originalConsoleLog.apply(console, arguments);
};

const server = createServer({
  cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
  key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
}).listen(8081);
const wss = new WebSocketServer({ server });
const currentPatch = fs.readFileSync('/hdd1/clashapp/data/patch/version.txt', 'utf-8');
const validChamps = JSON.parse(fs.readFileSync('/hdd1/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];
var lastClient = "";

console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully started the Websocket-Server!", new Date().toLocaleTimeString());

wss.on('connection', function connection(ws, req) {
  let d = new Date();
  console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Client websocket connection initiated from \x1b[4m%s\x1b[0m:%d on %s', new Date().toLocaleTimeString(), req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, d.getDate()+"/"+(d.getMonth()+1)+"/"+d.getFullYear() % 100);
  console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Total clients connected: %d", new Date().toLocaleTimeString(), wss.clients.size);

  ws.on('message', function message(data) {
    let newClient = req.headers['x-forwarded-for'].split(/\s*,\s*/)[0] + ':' + req.socket.remotePort;
    let dataAsString = data.toString();
    if(Array.from(dataAsString)[0] == "{"){        // If data is an [Object object]
      var dataAsJSON = JSON.parse(dataAsString);
      let requestMessage = "";
      if(dataAsJSON.request != "minigames"){
        switch (dataAsJSON.request) {
          case "firstConnect":
            requestMessage = "\x1b[36mfirstConnect\x1b[0m";
            break;
          case "add":
            requestMessage = "\x1b[32madd\x1b[0m";
            break;
          case "remove":
            requestMessage = "\x1b[31mremove\x1b[0m";
            break;
          case "rate":
            requestMessage = "\x1b[33mrate\x1b[0m";
            break;
          case "swap":
            requestMessage = "\x1b[35mswap\x1b[0m";
            break;
        }
        if (typeof dataAsJSON.champname === 'undefined') {
          var nameForMessage = dataAsJSON.name;
        } else {
          var nameForMessage = dataAsJSON.champname;
        }
        let message = '{"teamid":"'+dataAsJSON.teamid+'","name":"'+nameForMessage+'","request":"'+requestMessage+'"}';
        if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), message);
        } else {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Received following data from %s\n\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), newClient, new Date().toLocaleTimeString(), message);
          lastClient = newClient;
        }
      }

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////// ADD TO FILE ///////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      if(dataAsJSON.request == "add"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          var checkForInjection = true;
          for (var champ in validChamps) { // loop through every current local champ
            if (validChamps.hasOwnProperty(champ)) { // necessary js stuff
              if(dataAsJSON.champid == validChamps[champ].id && dataAsJSON.champname == validChamps[champ].name){
                checkForInjection = false;
                break; // stop looping through file
              }
            }
          }
          if(checkForInjection){ // if the var is still true (shouldn't be if id AND name found in champion.json)
            console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Code Injection Deteced, either champname or champid is invalid -> Logging IP (%s)", new Date().toLocaleTimeString(), req.headers['x-forwarded-for'].split(/\s*,\s*/)[0]); // TODO: Log and Save IP adress of attacker
            ws.send('{"status":"CodeInjectionDetected"}');
          } else {       
            if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
              var dataFromFile = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
              var localDataAsJson = JSON.parse(dataFromFile)["SuggestedBans"];
              var elementInArray = false;
              for (var key in localDataAsJson) { // loop through every current local champ
                if (localDataAsJson.hasOwnProperty(key)) { // necessary js stuff
                  if(dataAsJSON.champid == localDataAsJson[key].id){
                    elementInArray = true;
                    break;
                  }
                }
              }
              if(elementInArray){
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Provided element already exists in local file -> skipping", new Date().toLocaleTimeString());
                ws.send('{"status":"ElementAlreadyInArray"}');
              } else {
                if(Object.keys(localDataAsJson).length >= 10){
                  console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Maximum elements exceeded -> skipping", new Date().toLocaleTimeString());
                  ws.send('{"status":"MaximumElementsExceeded"}');
                } else {
                  var newChamp = {
                    id: dataAsJSON.champid,
                    name: dataAsJSON.champname
                  }
                  dataFromFile = JSON.parse(dataFromFile);
                  dataFromFile.SuggestedBans.push(newChamp);
                  dataFromFile.Status++;
                  // console.log(dataFromFile);
                  fs.writeFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(dataFromFile));
                  console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully added %s to %s.json", new Date().toLocaleTimeString(), dataAsJSON.champname, dataAsJSON.teamid);
                  broadcastUpdate(dataAsJSON.teamid);
                  ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                  wss.clients.forEach(function each(client) {
                    if(client.location == dataAsJSON.teamid && client != ws){
                      // client.send(ws.name+' added '+dataAsJSON.champname);
                      client.send('{"status":"Message","message":"added %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
                    }
                  });
                }
              }
            } else {
              var fileContent = {
                SuggestedBans: [
                  {
                    id: dataAsJSON.champid,
                    name: dataAsJSON.champname
                  }
                ],
                Status: 1
              }
              fs.writeFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(fileContent), function() {
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: File did not exists -> created %s.json", new Date().toLocaleTimeString(), dataAsJSON.teamid);
                ws.send('{"status":"FileDidNotExist"}');
              });
            }
          }
        }

       /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ///////////////////////////////////////////// REMOVE FROM FILE //////////////////////////////////////////////////
       /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "remove"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          var checkForInjection = true;
          for (var champ in validChamps) { // loop through every current local champ
            if (validChamps.hasOwnProperty(champ)) { // necessary js stuff
              if(dataAsJSON.champid == validChamps[champ].id && dataAsJSON.champname == validChamps[champ].name){
                checkForInjection = false;
                break; // stop looping through file
              }
            }
          }
          if(checkForInjection){ // if the var is still true (shouldn't be if id AND name found in champion.json)
            console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Code Injection Deteced, either champname or champid is invalid -> Logging IP (%s)", new Date().toLocaleTimeString(), req.headers['x-forwarded-for'].split(/\s*,\s*/)[0]); // TODO: Log and Save IP adress of attacker
            ws.send('{"status":"CodeInjectionDetected"}');
          } else {       
            if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
              var dataFromFile = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
              var localDataAsJson = JSON.parse(dataFromFile)["SuggestedBans"];
              var elementInArray = false;
              for (var key in localDataAsJson) { // loop through every current local champ
                if (localDataAsJson.hasOwnProperty(key)) { // necessary js stuff
                  if(dataAsJSON.champid == localDataAsJson[key].id){
                    elementInArray = true;
                    break;
                  }
                }
              }
              if(!elementInArray){
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Provided element does not exists in local file -> skipping", new Date().toLocaleTimeString());
                ws.send('{"status":"ElementNotInArray"}');
              } else {
                let newData = JSON.parse(dataFromFile);
                let elementIndex = 0;
                newData.SuggestedBans.forEach(element => { // Find object element in array
                    if(element.id == dataAsJSON.champid){
                      elementIndex = newData.SuggestedBans.indexOf(element);
                    }
                  });
                newData.SuggestedBans.splice(elementIndex, 1); // remove object from array
                newData.Status++;
                fs.writeFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(newData));
                console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully removed %s from %s.json", new Date().toLocaleTimeString(), dataAsJSON.champname, dataAsJSON.teamid);
                broadcastUpdate(dataAsJSON.teamid);
                ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                wss.clients.forEach(function each(client) {
                  if(client.location == dataAsJSON.teamid && client != ws){
                    // client.send(ws.name+' removed '+dataAsJSON.champname);
                    client.send('{"status":"Message","message":"removed %1.","champ":"'+dataAsJSON.champname+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
                  }
                });
              }
            }
          }
        }
        
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////// SWAP IN FILE //////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "swap"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
            var dataFromFile = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
            var localDataSuggestedBanArray = JSON.parse(dataFromFile);
            let fromId = 0;
            let toId= 0;
            localDataSuggestedBanArray.SuggestedBans.forEach(element => { // Find object element in array
              if(element.id == dataAsJSON.fromID){
                fromId = localDataSuggestedBanArray.SuggestedBans.indexOf(element);
              } else if(element.id == dataAsJSON.toID){
                toId = localDataSuggestedBanArray.SuggestedBans.indexOf(element);
              }
            });
            let temp = localDataSuggestedBanArray.SuggestedBans[fromId];
            localDataSuggestedBanArray.SuggestedBans[fromId] = localDataSuggestedBanArray.SuggestedBans[toId];
            localDataSuggestedBanArray.SuggestedBans[toId] = temp;
            localDataSuggestedBanArray.Status++;
            fs.writeFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(localDataSuggestedBanArray));
            console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully swapped %s with %s in %s.json", new Date().toLocaleTimeString(), dataAsJSON.fromName, dataAsJSON.toName, dataAsJSON.teamid);
            broadcastUpdate(dataAsJSON.teamid);
            ws.send('{"status":"Success"}');
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid && client != ws){
                // client.send(ws.name+' swapped '+dataAsJSON.fromName+' with '+dataAsJSON.toName);
                client.send('{"status":"Message","message":"swapped %1 with %2.","champ1":"'+dataAsJSON.fromName+'","champ2":"'+dataAsJSON.toName+'","name":"'+ws.name+'","color":"'+ws.color+'"}');
              }
            });
          }
        }
        
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////// MODIFY TEAM RATING ////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "rate"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
            var dataFromFile = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
            var newData = JSON.parse(dataFromFile);
            if(dataAsJSON.rating == 0){
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Client removed rating score of %d from %s.json", new Date().toLocaleTimeString(), newData.Rating[String(dataAsJSON.hash)], dataAsJSON.teamid);
              delete newData.Rating[String(dataAsJSON.hash)];
            } else {
              newData.Rating[String(dataAsJSON.hash)] = dataAsJSON.rating;
              console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Client rated %s.json with a score of %d", new Date().toLocaleTimeString(), dataAsJSON.teamid, dataAsJSON.rating);
            }
            fs.writeFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(newData));
            broadcastUpdate(dataAsJSON.teamid);
            ws.send('{"status":"Success"}');
          }
        }

       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ////////////////////////////////////////////////// FIRST CONNECT /////////////////////////////////////////////////
       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "firstConnect"){
        if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
          ws.location = dataAsJSON.teamid;
          var possibleColors = ["red-700","green-800","blue-800","pink-700","lime-500","cyan-600","amber-600","yellow-400","purple-700","rose-400"];
          wss.clients.forEach(function each(client) {              
            if(possibleColors.includes(client.color)){ // This removes every "already used" color from the array above
              var colorIndex = possibleColors.indexOf(client.color);
              if (colorIndex > -1) { // only splice array when item is found
                possibleColors.splice(colorIndex, 1); // 2nd parameter means remove one item only
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
            // console.log(possibleNames[Math.floor(Math.random()*possibleNames.length)]);
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid){
                if(possibleNames.includes(client.name)){ // This removes every "already used" name from the array above
                  var index = possibleNames.indexOf(client.name);
                  if (index > -1) { // only splice array when item is found
                    possibleNames.splice(index, 1); // 2nd parameter means remove one item only
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
          let localTeamData = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local teamdata and send to client
          ws.send(localTeamData);
          wss.clients.forEach(function each(client) {
            if(client.location == dataAsJSON.teamid && client != ws){
              client.send('{"status":"Message","message":"joined the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
            }
          });
        } else {
          return;
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////// MINIGAMES // ////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
      } else if(dataAsJSON.request == "minigames"){
        ws.location = dataAsJSON.roomid;

        // When a new player joins a room
        if (!roomPlayers[dataAsJSON.roomid]) {
          roomPlayers[dataAsJSON.roomid] = [];
        }

        if(dataAsJSON.name == ""){
          var possibleNames = ["Krug","Gromp","Sentinel","Brambleback","Raptor","Scuttler","Wolf","Herald","Nashor","Minion"];
          wss.clients.forEach(function each(client) {
            if(client.location == dataAsJSON.roomid){
              if(possibleNames.includes(client.name)){ // This removes every "already used" name from the array above
                var index = possibleNames.indexOf(client.name);
                if (index > -1) { // only splice array when item is found
                  possibleNames.splice(index, 1); // 2nd parameter means remove one item only
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
        ws.send('{"status":"RoomJoined","name":"'+ws.name+'","location":"'+ws.location+'","message":"joined the session."}');
        wss.clients.forEach(function each(client) {
          if(client.location == dataAsJSON.roomid && client != ws){
            client.send('{"status":"Message","message":"joined the session.","name":"'+ws.name+'"}');
          }
        });

        // Add the new player to the connected players array for the specific room
        roomPlayers[dataAsJSON.roomid].push(ws.name);

        // Send the updated player list for the specific room to all players in that room
        const playerListUpdate = {
          status: 'PlayerListUpdate',
          players: roomPlayers[dataAsJSON.roomid]
        };

        wss.clients.forEach(function each(client) {
          if (client.location == dataAsJSON.roomid) {
            client.send(JSON.stringify(playerListUpdate));
          }
        });
      }
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
     ////////////////////////////////////////////////// ON TEXT MESSAGE ///////////////////////////////////////////////////
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    } else {
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        console.log('\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: Data: %s', new Date().toLocaleTimeString(), data.toString());
      } else {
        console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Received following data from %s:%d\n\x1b[2m[%s]\x1b[0m [\x1b[36mWS-Client\x1b[0m]: %s', new Date().toLocaleTimeString(), req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, new Date().toLocaleTimeString(), data.toString());
        lastClient = newClient;
      }
    }
  });

  ws.send('Handshake successful: Server received client request and answered.');

  ws.on('close', function close() {
    console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Connection of client closed from %s:%d on %s', new Date().toLocaleTimeString(), req.headers['x-forwarded-for'].split(/\s*,\s*/)[0], ws._socket.remotePort, d.getDate()+"/"+(d.getMonth()+1)+"/"+d.getFullYear() % 100);
    console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Total clients connected: %d", new Date().toLocaleTimeString(), wss.clients.size);

    // Find the room (location) of the closing client
    const closedRoom = ws.location;

    // Remove the closed client's name from the roomPlayers object
    if (roomPlayers[closedRoom]) {
        roomPlayers[closedRoom] = roomPlayers[closedRoom].filter(player => player !== ws.name);
    }

    // Send the updated player list for the specific room to all players in that room
    const playerListUpdate = {
      status: 'PlayerListUpdate',
      players: roomPlayers[closedRoom]
    };

    wss.clients.forEach(function each(client) {
      if(client.location == ws.location && client != ws){
        if(ws.color){
          client.send('{"status":"Message","message":"left the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
        } else {
          client.send('{"status":"Message","message":"left the session.","name":"'+ws.name+'"}');
          client.send(JSON.stringify(playerListUpdate));
        }
      }
    });
  });
});


// This function broadcasts new teamdata to any client currently connected to the teams page 
// (E.g. Client 1 and 2 are connected to /123456 and Client 3 ist connected to /666666 -> Only Client 1 and 2 will receive new data)
function broadcastUpdate(clientsTeamID){ 
  wss.clients.forEach(function each(client) {
    if(client.location == clientsTeamID){
      let localTeamData = fs.readFileSync('/hdd1/clashapp/data/teams/' + clientsTeamID + '.json', 'utf-8'); // read local file
      client.send(localTeamData);
    }
  });
}

// This function broadcasts a message to any client currently connected to any teams page 
// (E.g. Client 1 and 2 are connected to /123456 and Client 3 ist connected to /666666 -> All will receive the message)
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

function handleCrash(error) {
  var currentTime = new Date().toLocaleTimeString();
  const crashMessage = `[${currentTime}] [Server Crash]: ${error.stack}\n`;
  fs.appendFileSync(logPath, crashMessage, 'utf8');

  // Terminate the application
  process.exit(1);
}