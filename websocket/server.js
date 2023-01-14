import { createServer } from 'https';
import { readFileSync } from 'fs';
import { WebSocketServer } from 'ws';
import fs from 'fs';

const server = createServer({
  cert: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/fullchain.pem'),
  key: readFileSync('/etc/letsencrypt/live/dasnerdwork.net/privkey.pem')
}).listen(8081);
const wss = new WebSocketServer({ server });
const currentPatch = fs.readFileSync('/hdd2/clashapp/data/patch/version.txt', 'utf-8');
const validChamps = JSON.parse(fs.readFileSync('/hdd2/clashapp/data/patch/'+currentPatch+'/data/de_DE/champion.json', 'utf-8'))["data"];
var lastClient = "";

wss.on('connection', function connection(ws) {
  console.log('WS-Server: Client websocket connection initiated from %s:%d on %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, new Date().toLocaleString());
  console.log("WS-Server: Total clients connected: %d", wss.clients.size);
  // ws.location 
  // console.log(msg);

  ws.on('message', function message(data) {
    let newClient = ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length) + ':' + ws._socket.remotePort
    let dataAsString = data.toString();
    if(Array.from(dataAsString)[0] == "{"){        // If data is an [Object object]
      var dataAsJSON = JSON.parse(dataAsString);
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        console.log('WS-Client: %s', JSON.stringify(dataAsJSON));
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
          // throw new Error("Invalid TeamID");
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
            if (fs.existsSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
              var dataFromFile = fs.readFileSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
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
                  fs.writeFileSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(dataFromFile));
                  console.log("WS-Server: Successfully added %s to %s.json", dataAsJSON.champname, dataAsJSON.teamid);
                  broadcastUpdate(dataAsJSON.teamid);
                  ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
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
              fs.writeFileSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(fileContent), function() {
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
          // throw new Error("Invalid TeamID");
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
            if (fs.existsSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json')){
              var dataFromFile = fs.readFileSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json', 'utf-8'); // read local file
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
                fs.writeFileSync('/hdd2/clashapp/data/teams/' + dataAsJSON.teamid + '.json', JSON.stringify(newData));
                console.log("WS-Server: Successfully removed %s to %s.json", dataAsJSON.champname, dataAsJSON.teamid);
                broadcastUpdate(dataAsJSON.teamid);
                ws.send('{"status":"Success","champid":"'+dataAsJSON.champid+'","champname":"'+dataAsJSON.champname+'"}');
              }
            }
          }
        }

       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
       ////////////////////////////////////////////////// UPDATE REQUESTS ///////////////////////////////////////////////
       //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

      } else if(dataAsJSON.request == "firstConnect"){
        ws.location = dataAsJSON.teamid;
        broadcastUpdate(dataAsJSON.teamid);
        // sendUpdate(dataAsJSON.teamid);
      } else if(dataAsJSON.request == "update"){
        sendUpdate(dataAsJSON.teamid);
      }













     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
     ////////////////////////////////////////////////// ON TEXT MESSAGE ///////////////////////////////////////////////////
     //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    } else {
      if(newClient == lastClient){ // If the same client is still sending data no "Received following data from" text is necessary
        console.log('WS-Client: %s', data.toString());
      } else {
        console.log('WS-Server: Received following data from %s:%d\nWS-Client: %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, data.toString());
        lastClient = newClient;
      }
    }
  });

  ws.send('Handshake successful: Server received client request and answered. (R: Hello there. A: General Kenobi!)');

  ws.on('close', function close() {
    console.log('WS-Server: Connection of client closed from %s:%d on %s', ws._socket.remoteAddress.substring(7, ws._socket.remoteAddress.length), ws._socket.remotePort, new Date().toLocaleString());
    console.log("WS-Server: Total clients connected: %d", wss.clients.size);
    // clearInterval(interval);
  });
});


// This function broadcasts new teamdata to any client currently connected to the teams page 
// (E.g. Client 1 and 2 are connected to /123456 and Client 3 ist connected to /666666 -> Only Client 1 and 2 will receive new data)
function broadcastUpdate(clientsTeamID){ 
  wss.clients.forEach(function each(client) {
    if(client.location == clientsTeamID){
      let localTeamData = fs.readFileSync('/hdd2/clashapp/data/teams/' + clientsTeamID + '.json', 'utf-8'); // read local file
      client.send(localTeamData);
    }
  });
}

 


/**  

{
  "SuggestedBans":[
    {
      "id":"Alistar",
      "name":"Alistar"
    },
    {
      "id":"Ahri",
      "name":"Ahri"
    },
    {
      "id":"Ashe",
      "name":"Ashe"
    },
    {
      "id":"Fiora",
      "name":"Fiora"
    },
    {
      "id":"DrMundo",
      "name":"Dr. Mundo"
    },
    {
      "id":"Fiddlesticks",
      "name":"Fiddlesticks"
    },
    {
      "id":"Ekko",
      "name":"Ekko"
    },
    {
      "id":"Akali",
      "name":"Akali"
    }
  ],
  "Status":181,
  "Rating":{
    "34173cb38f07f89ddbebc2ac9128303f":"4",
    "17e62166fc8586dfa4d1bc0e1742c08b":"5"
  }
}


*/