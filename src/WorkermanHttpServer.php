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
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class WorkermanHttpServer implements HttpServerInterface
{
    use HttpServerTrait;

    public function run(Router $router, OutputInterface $output): void
    {
        $this->setRuntime();
        $serverConfig = new ServerConfig();

        $httpWorker = new Worker($serverConfig->getUri(true));
        $config = Config::get('workerman', []);
        $httpWorker->count = $serverConfig->getWorkers();
        $config['callback']($httpWorker, $router, $output);
        Worker::runAll();
    }

    private function setRuntime(): void
    {
        $this->createRuntimeDir();
        if ($this->isLinux()) {
            Worker::$logFile = '/dev/null';
        }
        Worker::$pidFile = 'runtime/' . uniqid('microphp_', true) . '.pid';
    }

    private function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }
}
