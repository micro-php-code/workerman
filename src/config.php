<?php
declare(strict_types=1);

use MicroPHP\Framework\Router\Router;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Psr7\Response;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;

return [
    'callback' => function(Worker $httpWorker,  Router $router, OutputInterface $output) {
        $httpWorker->eventLoop = \Workerman\Events\Fiber::class;
        $httpWorker->onMessage = function (TcpConnection $connection, Request $request) use ($router, $output) {
            $psr7Request = new ServerRequest($request->rawBuffer());

            $response = $this->routeDispatch($router, \MicroPHP\Framework\Http\ServerRequest::fromPsr7($psr7Request));
            $connection->send(new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase()));
        };
    }
];