<?php

namespace Hyperf\DTO\Event;

use Hyperf\HttpServer\Router\RouteCollector;

class AfterDTOStart
{
    /**
     * @var string
     */
    public $serverConfig;

    /**
     * @var RouteCollector
     */
    public $router;

    public function __construct(array $serverConfig, $router)
    {
        $this->router = $router;
        $this->serverConfig = $serverConfig;
    }
}