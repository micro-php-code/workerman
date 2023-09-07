<?php

declare(strict_types=1);

namespace MicroPHP\Workerman;

use League\Route\Strategy\ApplicationStrategy;
use MicroPHP\Framework\Application;
use MicroPHP\Framework\Config\Config;
use MicroPHP\Framework\Http\Contract\HttpServerInterface;
use MicroPHP\Framework\Http\ServerConfig;
use MicroPHP\Framework\Http\Traits\HttpServerTrait;
use MicroPHP\Framework\Router\Router;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Psr7\Response;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;

class WorkermanHttpServer implements HttpServerInterface
{
    use HttpServerTrait;

    public function run(Router $router): void
    {
        $this->setRuntime();
        $strategy = new ApplicationStrategy();
        $strategy->setContainer(Application::getContainer());
        $router->setStrategy($strategy);
        $serverConfig = new ServerConfig();

        $httpWorker = new Worker($serverConfig->getUri(true));

        $httpWorker->count = $serverConfig->getWorkers();

        $httpWorker->onMessage = function (TcpConnection $connection, Request $request) use ($router) {
            $psr7Request = new ServerRequest($request->rawBuffer());

            $response = $this->routeDispatch($router, \MicroPHP\Framework\Http\ServerRequest::fromPsr7($psr7Request));
            $connection->send(new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase()));
        };

        Worker::runAll();
    }

    private function setRuntime(): void
    {
        $this->createRuntimeDir();
        $enableWorkermanLog = Config::get('log.enable_workerman_log');
        Worker::$logFile = base_path('runtime/logs/workerman.log');
        if (empty($enableWorkermanLog) && $this->isLinux()) {
            Worker::$logFile = '/dev/null';
        }
        Worker::$pidFile = 'runtime/' . uniqid('workerman_', true) . '.pid';
    }

    private function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }
}
