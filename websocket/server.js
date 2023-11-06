import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';
import { promises as fsPromises } from 'fs';
import ansiRegex from 'ansi-regex';
import util from 'util';
import '/hdd1/clashapp/websocket/consoleHandler.js';
import mongodb from 'mongodb';

const mongoURL = '***REMOVED***';
const logStream = fs.createWriteStream('/hdd1/clashapp/data/logs/server.log', { flags: 'a' });
const logPath = '/hdd1/clashapp/data/logs/server.log';
const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
const roomPlayers = {}; // Initializes an object to store connected players for each room
const roomSettings = {};
const correctAnswerTimers  = {};

const mongoClient = new mongodb.MongoClient(mongoURL);

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

// Start and create Websocket Server
const server = createServer({
  cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
  key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
}).listen(8081);
const wss = new WebSocketServer({ server });
const currentPatch = fs.readFileSync('/hdd1/clashapp/data/patch/version.txt', 'utf-8');
const validChamps = JSON.parse(fs.readFileSync('/hdd1/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];
var lastClient = "";

console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully started the Websocket-Server!", new Date().toLocaleTimeString());

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// DATABASE OPERATION FUNCTIONS ////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function updateTeamRating(teamId, hash, rating) {
  return new Promise(async (resolve, reject) => {
    try {
      await mongoClient.connect(); // establish database connection
      const db = mongoClient.db('clashappdb');
      const teamsCollection = db.collection('teams');
      const filter = { TeamID: teamId };
      const update = {
        $set: { [`Rating.${hash}`]: rating },
        $inc: { 'Status': 1 } // Increment Status
      };
      const result = await teamsCollection.updateOne(filter, update); // Update document
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
      console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error updating team rating: ${error}`, new Date().toLocaleTimeString());
      reject(error);
    }
  });
}

function addToFile(teamId, champId, champName) {
  return new Promise((resolve, reject) => {
    mongoClient.connect()
      .then(() => {
        const db = mongoClient.db('clashappdb');
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
      })
  });
}

function removeFromFile(teamId, champId, champName) {
  return new Promise((resolve, reject) => {
    mongoClient.connect()
      .then(() => {
        const db = mongoClient.db('clashappdb');
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
      })
  });
}

function swapInFile(teamId, fromId, toId) {
  return new Promise(async (resolve, reject) => {
    try {
      await mongoClient.connect();
      const db = mongoClient.db('clashappdb');
      const teamsCollection = db.collection('teams');
      const filter = { TeamID: teamId };
      const teamDocument = await teamsCollection.findOne(filter);
      const suggestedBans = teamDocument.SuggestedBans;
      const fromIndex = suggestedBans.findIndex(champion => champion.id === fromId);
      const toIndex = suggestedBans.findIndex(champion => champion.id === toId);
      // Check if both champions were found
      if (fromIndex === -1 || toIndex === -1) {
        console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Champions not found in ${teamId}.json`, new Date().toLocaleTimeString());
        resolve({ status: 'Champions not found' });
        return;
      }
      // Swap the elements at fromIndex and toIndex
      const temp = suggestedBans[fromIndex];
      suggestedBans[fromIndex] = suggestedBans[toIndex];
      suggestedBans[toIndex] = temp;
      // Update the document with the modified SuggestedBans array
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
      await mongoClient.connect();
      const db = mongoClient.db('clashappdb');
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
    mongoClient.connect()
      .then(() => {
        const db = mongoClient.db('clashappdb');
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
//////////////////////////////////////// WEBSOCKET SERVER OPERATIONS /////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
                  } else {
                    return addToFile(dataAsJSON.teamid, dataAsJSON.champid, dataAsJSON.champname);
                  }
                }
              }).then((response) => {
              if (response.status === 'Success') {
                console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully added ${dataAsJSON.champname} to ${dataAsJSON.teamid}.json`, new Date().toLocaleTimeString());
              } else if (response.status === 'Error') {
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
                  console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Provided element does not exists in local file -> skipping", new Date().toLocaleTimeString());
                  ws.send('{"status":"ElementNotInArray"}');
                } else {
                  return removeFromFile(dataAsJSON.teamid, dataAsJSON.champid, dataAsJSON.champname);
                }
              })
              .then((response) => {
                if (response.status === 'Success') {
                  console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully removed ${dataAsJSON.champname} from ${dataAsJSON.teamid}.json`, new Date().toLocaleTimeString());
                } else {
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
        
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////// SWAP IN FILE //////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "swap"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Forbidden teamid provided", new Date().toLocaleTimeString());
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          swapInFile(dataAsJSON.teamid, dataAsJSON.fromID, dataAsJSON.toID)
            .then((response) => {
              if (response.status === 'Success') {
                console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Successfully swapped champions in ${dataAsJSON.teamid}.json`, new Date().toLocaleTimeString());
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
        
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////// MODIFY TEAM RATING ////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
          ws.send('{"status":"Success"}');
        }

       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ////////////////////////////////////////////////// FIRST CONNECT /////////////////////////////////////////////////
       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "firstConnect"){
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

        readTeamData(dataAsJSON.teamid)
        .then((localDataAsJson) => {
          ws.send(JSON.stringify(localDataAsJson));
        });

        wss.clients.forEach(function each(client) {
          if(client.location == dataAsJSON.teamid && client != ws){
            client.send('{"status":"Message","message":"joined the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
          }
        });

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////// LOCK & UNLOCK /////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      //////////////////////////////////////////////////// MINIGAMES ///////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
      } else if(dataAsJSON.request == "minigames"){
          ws.location = dataAsJSON.roomid;
          // When a new player joins a room
          if (!roomPlayers[dataAsJSON.roomid]) {
            roomPlayers[dataAsJSON.roomid] = [];
          }
          // Initialize roomSettings if it doesn't exist for the current room
          if (!roomSettings[dataAsJSON.roomid]) {
            roomSettings[dataAsJSON.roomid] = {};
          }
          // Save room difficulty
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
              const imagePath = `/clashapp/data/patch/${currentPatch}/img/champion/${randomChampion.image.full}`;
          
              // Sending pixelation settings and image path to the new player
              const pixelationSettings = {
                status: 'PixelateAndGenerate',
                pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"],
                imagePath: Buffer.from(imagePath).toString('base64'),
                championName: Buffer.from(championName).toString('base64')
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
        ws.send('{"status":"RoomJoined","name":"'+ws.name+'","location":"'+ws.location+'","message":"(You) joined the room.","color":"'+ws.color+'","difficulty":"'+roomSettings[dataAsJSON.roomid]["Difficulty"]+'"}');
        wss.clients.forEach(function each(client) {
          if(client.location == dataAsJSON.roomid && client != ws){
            client.send('{"status":"Message","message":"joined the room.","name":"'+ws.name+'","color":"'+ws.color+'"}');
          }
        });

        // Add the new player to the connected players array for the specific room
        roomPlayers[dataAsJSON.roomid].push(ws.name);

        // Send the updated player list for the specific room to all players in that room
        const playerListUpdate = {
          status: 'PlayerListUpdate',
          players: roomPlayers[dataAsJSON.roomid],
          colors: {} // Use an object for mapping names to colors
        };

        // Collect the colors of all players in the room using a mapping
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

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////// CORRECT ANSWER  ////////////////////////////////////////////////////
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
          // Initialize a timestamp for this room
          correctAnswerTimers[dataAsJSON.roomid] = 0;
        }
    
        const currentTime = Date.now();
    
        if (currentTime - correctAnswerTimers[dataAsJSON.roomid] >= 4000) {
            // Record the current timestamp
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
                const imagePath = `/clashapp/data/patch/${currentPatch}/img/champion/${randomChampion.image.full}`;
    
                // Sending pixelation settings and image path to the new player
                const pixelationSettings = {
                    status: 'PixelateAndGenerateNew',
                    pixelationDifficulty: roomSettings[dataAsJSON.roomid]["Difficulty"],
                    imagePath: Buffer.from(imagePath).toString('base64'),
                    championName: Buffer.from(championName).toString('base64')
                };
                wss.clients.forEach(function each(client) {
                    if (client.location == dataAsJSON.roomid) {
                        client.send(JSON.stringify(pixelationSettings));
                    }
                });
            }
            generateNewRandomChampionAndNotify();
          }

     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
     ///////////////////////////////////////////////// CHANGE DIFFICULTY///////////////////////////////////////////////////
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

    // Collect the colors of the remaining players in the room using a mapping
    const remainingPlayerColors = {};
    wss.clients.forEach(function each(client) {
      if (client.location === closedRoom && client !== ws) {
        remainingPlayerColors[client.name] = client.color;
      }
    });

    // Send the updated player list for the specific room to all players in that room
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


// This function broadcasts new teamdata to any client currently connected to the teams page 
// (E.g. Client 1 and 2 are connected to /123456 and Client 3 ist connected to /666666 -> Only Client 1 and 2 will receive new data)
function broadcastUpdate(clientsTeamID) {
  const teamsCollection = mongoClient.db('clashappdb').collection('teams');

  readTeamData(clientsTeamID)
    .then((localDataAsJson) => {
      wss.clients.forEach(function each(client) {
        if (client.location == clientsTeamID) {
          client.send(JSON.stringify(localDataAsJson));
        }
      });
    })
    .catch((error) => {
      // Handle any errors that occur when fetching data from MongoDB
      console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mWS-Server\x1b[0m]: Error broadcasting update: ${error}`, new Date().toLocaleTimeString());
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
  process.exit(1);
}