#!/bin/bash

# MongoDB Cleanup Script
# Verwendet die Credentials aus den FastCGI Parametern

mongosh 'mongodb://clashapp:F-))#pp!dat7g&MO@dasnerdwork.net:17171/clashappdb?directConnection=true&authSource=clashappdb&tls=true&tlsCAFile=/etc/ssl/mongo/mongodb-ca.crt' --eval "
use clashappdb;
print('Deleting player data...');
const result = db.players.deleteMany({});
print('Deleted ' + result.deletedCount + ' players');

print('Deleting match data...');
const matchResult = db.matches.deleteMany({});
print('Deleted ' + matchResult.deletedCount + ' matches');

print('Deleting team data...');
const teamResult = db.teams.deleteMany({});
print('Deleted ' + teamResult.deletedCount + ' teams');

print('Cleanup completed!');
"