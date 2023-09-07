<?php

declare(strict_types=1);

namespace MicroPHP\Workerman;

use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use MicroPHP\Framework\Application;
use MicroPHP\Framework\Http\Contract\ServerInterface;
use MicroPHP\Framework\Http\ServerConfig;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Psr7\Response;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;

class WorkermanHttpServer implements ServerInterface
{
    public function run(Router $router): void
    {
        $strategy = new ApplicationStrategy();
        $strategy->setContainer(Application::getContainer());
        $router->setStrategy($strategy);
        $serverConfig = new ServerConfig();

        $httpWorker = new Worker($serverConfig->getUri(true));

        $httpWorker->count = $serverConfig->getWorkers();

        $httpWorker->onMessage = function (TcpConnection $connection, Request $request) use ($router) {
            $psr7Request = new ServerRequest($request->rawBuffer());
            $serverRequest = \MicroPHP\Framework\Http\ServerRequest::fromPsr7($psr7Request);

            $response = $router->dispatch($serverRequest);
            $connection->send(new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase()));
        };

        Worker::runAll();
    }
}
