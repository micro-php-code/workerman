<?php
declare(strict_types=1);

namespace MicroPHP\Workerman;

use MicroPHP\Framework\Config\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public function config(): array
    {
        return [
            'publish' => [
                'workerman' => [
                    'from' => __DIR__ . '/config.php',
                    'to' => base_path('config/workerman.php'),
                ],
            ],
        ];
    }
}