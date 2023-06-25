import readline from 'readline';
import { exec } from 'child_process';
import { broadcastAll } from '/hdd1/clashapp/websocket/server.js';
import fs from 'fs';

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
      exec('ls -A /hdd1/clashapp/data/player/', (lsError, lsStdout) => {
        if (lsError) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing ls command: ${lsError.message}`, new Date().toLocaleTimeString());
          return;
        }
        if (lsStdout.trim() === '') {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mPlayer folder already empty!\x1b[0m', new Date().toLocaleTimeString());
          return;
        }
        exec('rm /hdd1/clashapp/data/player/*', (error, stdout) => {
          if (error) {
            console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing command: ${error.message}`, new Date().toLocaleTimeString());
            return;
          } else {
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mSuccessfully cleared all player and match .json files\x1b[0m', new Date().toLocaleTimeString());
          }
        });
      });
    } else if (input.trim().toLowerCase() === 'clear matches') {
      exec('ls -A /hdd1/clashapp/data/matches/', (lsError, lsStdout) => {
        if (lsError) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing ls command: ${lsError.message}`, new Date().toLocaleTimeString());
          return;
        }
        if (lsStdout.trim() === '') {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mMatch folder already empty!\x1b[0m', new Date().toLocaleTimeString());
          return;
        }
        exec('rm /hdd1/clashapp/data/matches/*', (error, stdout) => {
          if (error) {
            console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing command: ${error.message}`, new Date().toLocaleTimeString());
            return;
          } else {
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mSuccessfully cleared all match .json files\x1b[0m', new Date().toLocaleTimeString());
          }
        });
      });
    } else if (input.trim().toLowerCase() === 'clear all') {
      exec('ls -A /hdd1/clashapp/data/player/', (playerLsError, playerLsStdout) => {
        if (playerLsError) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing ls command for players folder: ${playerLsError.message}`, new Date().toLocaleTimeString());
          return;
        }
        if (playerLsStdout.trim() === '') {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mPlayer folder already empty!\x1b[0m', new Date().toLocaleTimeString());
        } else {
          exec('rm /hdd1/clashapp/data/player/*', (playerRmError, playerRmStdout) => {
            if (playerRmError) {
              console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing rm command for players folder: ${playerRmError.message}`, new Date().toLocaleTimeString());
              return;
            }
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mSuccessfully cleared all player .json files\x1b[0m', new Date().toLocaleTimeString());
          });
        }
      });
      exec('ls -A /hdd1/clashapp/data/matches/', (matchLsError, matchLsStdout) => {
        if (matchLsError) {
          console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing ls command for matches folder: ${matchLsError.message}`, new Date().toLocaleTimeString());
          return;
        }
        if (matchLsStdout.trim() === '') {
          console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mMatch folder already empty!\x1b[0m', new Date().toLocaleTimeString());
        } else {
          exec('rm /hdd1/clashapp/data/matches/*', (matchRmError, matchRmStdout) => {
            if (matchRmError) {
              console.log(`\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: Error executing rm command for matches folder: ${matchRmError.message}`, new Date().toLocaleTimeString());
              return;
            }
            console.log('\x1b[2m[%s]\x1b[0m [\x1b[35mLocal\x1b[0m]: \x1b[1;32mSuccessfully cleared all match .json files\x1b[0m', new Date().toLocaleTimeString());
          });
        }
      });
    } else if (input.trim().toLowerCase() === 'status') {
      exec('screen -list', (error, stdout) => {
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