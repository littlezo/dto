<?php

declare(strict_types=1);

/**
 * #logic 做事不讲究逻辑，再努力也只是重复犯错
 * ## 何为相思：不删不聊不打扰，可否具体点：曾爱过。何为遗憾：你来我往皆过客，可否具体点：再无你。
 * ## 摔倒一次可以怪路不平鞋不正，在同一个地方摔倒两次，只能怪自己和自己和解，无不是一个不错的选择。
 * @version 1.0.0
 * @author @小小只^v^ <littlezov@qq.com>  littlezov@qq.com
 * @link     https://github.com/littlezo
 * @document https://github.com/littlezo/wiki
 * @license  https://github.com/littlezo/MozillaPublicLicense/blob/main/LICENSE
 *
 */

namespace Littler\DTO\Router;

use Hyperf\JsonRpc\TcpServer;
use Hyperf\Rpc\Protocol;
use Hyperf\RpcServer\Router\DispatcherFactory;
use Psr\Container\ContainerInterface;

class TcpRouter
{
    private TcpServer $tcpServer;

    private $protocol;

    public function __construct(ContainerInterface $container)
    {
        $this->tcpServer = $container->get(TcpServer::class);
    }

    public function getRouter($serverName)
    {
        $data = make(DispatcherFactory::class, [
            'pathGenerator' => $this->getProtocol()
                ->getPathGenerator(),
        ]);

        return $data->getRouter($serverName);
    }

    protected function getProtocol(): Protocol
    {
        $getResponseBuilder = fn () => $this->protocol;

        return $getResponseBuilder->call($this->tcpServer);
    }
}
