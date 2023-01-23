import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';

const server = createServer({
  cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
  key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
}).listen(8081);
const wss = new WebSocketServer({ server });
const currentPatch = fs.readFileSync('/hdd1/clashapp/data/patch/version.txt', 'utf-8');
const validChamps = JSON.parse(fs.readFileSync('/hdd1/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];
var lastClient = "";

wss.on('connection', function connection(ws) {
  console.log('WS-Server: Client websocket connection initiated from %s:%d on %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, new Date().toLocaleString());
  console.log("WS-Server: Total clients connected: %d", wss.clients.size);

  ws.on('message', function message(data) {
    let newClient = ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length) + ':' + ws._socket.remotePort
    let dataAsString = data.toString();
    if(Array.from(dataAsString)[0] == "{"){        // If data is an [Object object]
      var dataAsJSON = JSON.parse(dataAsString);
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        console.log('WS-Client: Data: %s', JSON.stringify(dataAsJSON));
      } else {
        console.log('WS-Server: Received following data from %s\nWS-Client: %s', newClient, JSON.stringify(dataAsJSON));
        lastClient = newClient;
      }

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////// ADD TO FILE ///////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      if(dataAsJSON.request == "add"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("WS-Server: Forbidden teamid provided");
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
            console.log("WS-Server: Code Injection Deteced, either champname or champid is invalid -> Logging IP"); // TODO: Log and Save IP adress of attacker
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
                console.log("WS-Server: Provided element already exists in local file -> skipping");
                ws.send('{"status":"ElementAlreadyInArray"}');
              } else {
                if(Object.keys(localDataAsJson).length >= 10){
                  console.log("WS-Server: Maximum elements exceeded -> skipping");
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
                  console.log("WS-Server: Successfully added %s to %s.json", dataAsJSON.champname, dataAsJSON.teamid);
                  broadcastUpdate(dataAsJSON.teamid);
                  ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                  wss.clients.forEach(function each(client) {
                    if(client.location == dataAsJSON.teamid && client != ws){
                      // client.send(ws.name+' added '+dataAsJSON.champname);
                      client.send('{"status":"Message","message":"added '+dataAsJSON.champname+'.","name":"'+ws.name+'","color":"'+ws.color+'"}');
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
                console.log("WS-Server: File did not exists -> created %s.json", dataAsJSON.teamid);
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
          console.log("WS-Server: Forbidden teamid provided");
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
            console.log("WS-Server: Code Injection Deteced, either champname or champid is invalid -> Logging IP"); // TODO: Log and Save IP adress of attacker
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
                console.log("WS-Server: Provided element does not exists in local file -> skipping");
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
                console.log("WS-Server: Successfully removed %s from %s.json", dataAsJSON.champname, dataAsJSON.teamid);
                broadcastUpdate(dataAsJSON.teamid);
                ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
                wss.clients.forEach(function each(client) {
                  if(client.location == dataAsJSON.teamid && client != ws){
                    // client.send(ws.name+' removed '+dataAsJSON.champname);
                    client.send('{"status":"Message","message":"removed '+dataAsJSON.champname+'.","name":"'+ws.name+'","color":"'+ws.color+'"}');
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
          console.log("WS-Server: Forbidden teamid provided");
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
            console.log("WS-Server: Successfully swapped %s with %s in %s.json", dataAsJSON.fromName, dataAsJSON.toName, dataAsJSON.teamid);
            broadcastUpdate(dataAsJSON.teamid);
            ws.send('{"status":"Success"}');
            wss.clients.forEach(function each(client) {
              if(client.location == dataAsJSON.teamid && client != ws){
                // client.send(ws.name+' swapped '+dataAsJSON.fromName+' with '+dataAsJSON.toName);
                client.send('{"status":"Message","message":"swapped '+dataAsJSON.fromName+' with '+dataAsJSON.toName+'.","name":"'+ws.name+'","color":"'+ws.color+'"}');
              }
            });
          }
        }
        
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////// MODIFY TEAM RATING ////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "rate"){
        if(dataAsJSON.teamid == "" || dataAsJSON.teamid == "/"){
          console.log("WS-Server: Forbidden teamid provided");
          ws.send('{"status":"InvalidTeamID"}');
        } else {
          if (fs.existsSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
            var dataFromFile = fs.readFileSync('/hdd1/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
            var newData = JSON.parse(dataFromFile);
            if(dataAsJSON.rating == 0){
              console.log("WS-Server: Client removed rating score of %d from %s.json", newData.Rating[String(dataAsJSON.hash)], dataAsJSON.teamid);
              delete newData.Rating[String(dataAsJSON.hash)];
            } else {
              newData.Rating[String(dataAsJSON.hash)] = dataAsJSON.rating;
              console.log("WS-Server: Client rated %s.json with a score of %d", dataAsJSON.teamid, dataAsJSON.rating);
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
      } 

     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
     ////////////////////////////////////////////////// ON TEXT MESSAGE ///////////////////////////////////////////////////
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    } else {
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        console.log('WS-Client: Data: %s', data.toString());
      } else {
        console.log('WS-Server: Received following data from %s:%d\nWS-Client: %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, data.toString());
        lastClient = newClient;
      }
    }
  });

  ws.send('Handshake successful: Server received client request and answered.');

  ws.on('close', function close() {
    console.log('WS-Server: Connection of client closed from %s:%d on %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, new Date().toLocaleString());
    console.log("WS-Server: Total clients connected: %d", wss.clients.size);
    wss.clients.forEach(function each(client) {
      if(client.location == ws.location && client != ws){
        client.send('{"status":"Message","message":"left the session.","name":"'+ws.name+'","color":"'+ws.color+'"}');
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