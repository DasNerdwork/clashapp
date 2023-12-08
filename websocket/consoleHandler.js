import readline from 'readline';
import { exec } from 'child_process';
import { broadcastAll } from '/hdd1/clashapp/websocket/server.js';
import fs from 'fs';
import mongodb from 'mongodb';

// Create a write stream to the log file
const mongoURL = '***REMOVED***';

// Create a write stream to the log file
const logStream = fs.createWriteStream('/hdd1/clashapp/data/logs/server.log', { flags: 'a' });

// Redirect console output to the log file
const originalConsoleLog = console.log;
console.log = function () {
  originalConsoleLog.apply(console, arguments);
};

// Create a readline interface for reading input from the console (ws join)
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });
  
const mongoClient = new mongodb.MongoClient(mongoURL);

async function deleteAllMatches(close = false) {
  try {
    // Connect to the MongoDB server
    await mongoClient.connect();

    // Select the database and collection
    const db = mongoClient.db('clashappdb'); // Replace with your database name
    const collection = db.collection('matches');

    // Delete all documents in the "matches" collection
    const result = await collection.deleteMany({});

    console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Deleted ${result.deletedCount} documents from the 'matches' collection.`, new Date().toLocaleTimeString());
  } catch (err) {
    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error deleting documents: ${err}`, new Date().toLocaleTimeString());
  } finally {
    if(close){
      // Close the MongoDB connection
      mongoClient.close();
    }
  }
}

async function deleteAllPlayers(close = false) {
  try {
    // Connect to the MongoDB server
    await mongoClient.connect();

    // Select the database and collection
    const db = mongoClient.db('clashappdb'); // Replace with your database name
    const collection = db.collection('players');

    // Delete all documents in the "matches" collection
    const result = await collection.deleteMany({});

    console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Deleted ${result.deletedCount} documents from the 'players' collection.`, new Date().toLocaleTimeString());
  } catch (err) {
    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error deleting documents: ${err}`, new Date().toLocaleTimeString());
  } finally {
    if(close){
      // Close the MongoDB connection
      mongoClient.close();
    }
  }
}

async function deleteAllTeams(close = false) {
  try {
    // Connect to the MongoDB server
    await mongoClient.connect();

    // Select the database and collection
    const db = mongoClient.db('clashappdb'); // Replace with your database name
    const collection = db.collection('teams');

    // Delete all documents in the "matches" collection
    const result = await collection.deleteMany({});

    console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Deleted ${result.deletedCount} documents from the 'teams' collection.`, new Date().toLocaleTimeString());
  } catch (err) {
    console.error(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error deleting documents: ${err}`, new Date().toLocaleTimeString());
  } finally {
    if(close){
      // Close the MongoDB connection
      mongoClient.close();
    }
  }
}

// Listen for input from the console
rl.on('line', (input) => {
    logStream.write('['+new Date().toLocaleTimeString()+'] [User-Input]: '+input+'\n');
    if (input.trim().toLowerCase() === 'stop') {
      console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Stopping the WebSocket server...', new Date().toLocaleTimeString());
      process.exit();
    } else if (input.trim().toLowerCase() === 'clear') {
      process.stdout.write('\x1Bc');
    } else if (input.trim().match(/^say\s(.*)$/i)) {
      broadcastAll(input.trim().match(/^say\s(.*)$/i)[1]);
    } else if (input.trim().toLowerCase() === 'clear players' || input.trim().toLowerCase() === 'clear player') {
      deleteAllPlayers();
    } else if (input.trim().toLowerCase() === 'clear matches') {
      deleteAllMatches();
    } else if (input.trim().toLowerCase() === 'clear teams') {
      deleteAllTeams();
    } else if (input.trim().toLowerCase() === 'clear all') {
      deleteAllPlayers(true);
      deleteAllMatches(true);
      deleteAllTeams(true);
    } else if (input.trim().toLowerCase() === 'status') {
      exec('pm2 list', (error, stdout) => {
        if (error) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing command: ${error.message}`, new Date().toLocaleTimeString());
          return;
        }
        if (stdout.includes('WS-Server')) {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mWebSocket-Server is up and running\x1b[0m', new Date().toLocaleTimeString());
        } else {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[0;31mWebSocket-Server seems to have stopped and is not running\x1b[0m', new Date().toLocaleTimeString());
        }
        if (stdout.includes('tailwindWatcher')) {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mTailwind-Watcher is up and running\x1b[0m', new Date().toLocaleTimeString());
        } else {
          console.log('\x1b[0;31mTailwind-Watcher seems to have stopped and is not running\x1b[0m', new Date().toLocaleTimeString());
        }
      });
      exec('systemctl is-active nginx', (error, stdout) => {
        if (error) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing command: ${error.message}`, new Date().toLocaleTimeString());
          return;
        }
      
        if (stdout.trim() === 'active') {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mNginx-Webserver is up and running\x1b[0m', new Date().toLocaleTimeString());
        } else {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[0;31mNginx-Webserver is not active or not running\x1b[0m', new Date().toLocaleTimeString());
        }
      });
    } else {
      console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[0;33mInvalid parameter \'%s\' given. Try stop|clear|status\x1b[0m', new Date().toLocaleTimeString(), input.trim().toLowerCase());
    }
  });