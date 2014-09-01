/**
 * @file main.js
 */

// global modules

require('sugar');
Q = require('q');
Twit = require('twit');
exec = require('child_process').exec;
fs = require('fs');

var Streamer = require('./streamer.js'),
    streamer = null,
    name = (process.argv.length > 2)
        ? process.argv[2]
        : null;

if (name !== null) {
    streamer = new Streamer(name);
    streamer.open();
} else {
    console.error('undefined arguments.');
    process.exit(9);
}

process.on('uncaughtException', function(err) {
    console.log(err.toString());
    fs.writeFileSync(__dirname + '/../log/stderr.txt', err.toString() + '\n', {flag:'a'});
    process.exit(0);
});
