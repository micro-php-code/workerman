<?php

declare(strict_types=1);

use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use MicroPHP\Framework\Application;
use MicroPHP\Framework\Database\CycleORM;
use MicroPHP\Framework\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Psr7\Response;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;

if (! function_exists('route_dispatch')) {
    function route_dispatch(Router $router, MicroPHP\Framework\Http\ServerRequest $request): ResponseInterface
    {
        try {
            return $router->dispatch($request);
        } catch (NotFoundException $exception) {
            return new MicroPHP\Framework\Http\Response(404, [], $exception->getMessage());
        } catch (MethodNotAllowedException $exception) {
            return new MicroPHP\Framework\Http\Response(405, [], $exception->getMessage());
        }
    }
}

return [
    'callback' => static function (Worker $httpWorker, Router $router) {
        $httpWorker->eventLoop = Fiber::class;
        $httpWorker->onMessage = function (TcpConnection $connection, Request $request) use ($router) {
            try {
                $psr7Request = new ServerRequest($request->rawBuffer());

                $response = route_dispatch($router, MicroPHP\Framework\Http\ServerRequest::fromPsr7($psr7Request));
                $connection->send(new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase()));
            } finally {
                # 请求结束后清理堆信息
                CycleORM::cleanHeap();
            }
        };
        $httpWorker->onWorkerStart = function () {
            // 每一个进程都要初始化数据库
            CycleORM::init(Application::getContainer());
        };
    },
];
