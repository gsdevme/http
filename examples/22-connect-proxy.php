<?php

use React\EventLoop\Factory;
use React\Socket\Server;
use React\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();
$socket = new Server(isset($argv[1]) ? $argv[1] : '0.0.0.0:0', $loop);
$connector = new Connector($loop);

$server = new \React\Http\Server($socket, function (ServerRequestInterface $request) use ($connector) {
    if ($request->getMethod() !== 'CONNECT') {
        return new Response(
            405,
            array('Content-Type' => 'text/plain', 'Allow' => 'CONNECT'),
            'This is a HTTP CONNECT (secure HTTPS) proxy'
        );
    }

    // try to connect to given target host
    return $connector->connect($request->getRequestTarget())->then(
        function (ConnectionInterface $remote) {
            // connection established => forward data
            return new Response(
                200,
                array(),
                $remote
            );
        },
        function ($e) {
            return new Response(
                502,
                array('Content-Type' => 'text/plain'),
                'Unable to connect: ' . $e->getMessage()
            );
        }
    );
});

//$server->on('error', 'printf');

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;

$loop->run();
