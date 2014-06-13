<?php

// This example demonstrates how to connect a datagram client to a given remote
// address and send/receive some data.
//
// Resolving hostnames requires the "react/dns" component in the "require"
// keys in your composer.json, so make sure you have it installed like this:
//
// "require": {
//    "react/dns": "0.3.*"
// }
//
// This example allows you to set the remote UDP server address (which defaults
// to a non-functional value). Make sure to start up your server first
// (for example by running the server.php example) and adjust the following
// server address:

$address = 'default.com:1234';

require_once __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$dnsFactory = new React\Dns\Resolver\Factory();
$resolver = $dnsFactory->createCached('8.8.8.8', $loop);

$factory = new Datagram\Factory($loop, $resolver);

$factory->createClient($address)->then(function (Datagram\Socket $client) use ($loop) {
    $client->send('first');

    $client->on('message', function($message, $serverAddress, $client) {
        echo 'received "' . $message . '" from ' . $serverAddress. PHP_EOL;
    });

    $client->on('error', function($error, $client) {
        echo 'error: ' . $error->getMessage() . PHP_EOL;
    });

    $n = 0;
    $tid = $loop->addPeriodicTimer(2.0, function() use ($client, &$n) {
        $client->send('tick' . ++$n);
    });

    // read input from STDIN and forward everything to server
    $loop->addReadStream(STDIN, function () use ($client, $loop, $tid) {
        $msg = fgets(STDIN, 2000);
        if ($msg === false) {
            // EOF => flush client and stop perodic sending and waiting for input
            $client->end();
            $loop->cancelTimer($tid);
            $loop->removeReadStream(STDIN);
        } else {
            $client->send(trim($msg));
        }
    });
}, function($error) {
    echo 'ERROR: ' . $error->getMessage() . PHP_EOL;
});

$loop->run();
