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

namespace Littler\DTO;

use Closure;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeServerStart;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Littler\DTO\Event\AfterDtoStart;
use Littler\DTO\Router\TcpRouter;
use Littler\DTO\Scan\ScanAnnotation;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class BeforeServerListener implements ListenerInterface
{
    public function listen(): array
    {
        return [BeforeServerStart::class, MainCoroutineServerStart::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof BeforeServerStart) {
            $serverName = $event->serverName;
        } else {
            /** @var MainCoroutineServerStart $event */
            $serverName = $event->name;
        }

        $container = ApplicationContext::getContainer();
        $config = $container->get(ConfigInterface::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $scanAnnotation = $container->get(ScanAnnotation::class);

        $serverConfig = collect($config->get('server.servers'))
            ->where('name', $serverName)
            ->first();
        if (isset($serverConfig['callbacks']['receive'][0]) && Str::contains(
            $serverConfig['callbacks']['receive'][0],
            'TcpServer'
        )) {
            $tcpRouter = $container->get(TcpRouter::class);
            $router = $tcpRouter->getRouter($serverName);
        } else {
            $router = $container->get(DispatcherFactory::class)->getRouter($serverName);
        }
        $data = $router->getData();
        array_walk_recursive($data, function ($item) use ($scanAnnotation): void {
            if ($item instanceof Handler && ! ($item->callback instanceof Closure)) {
                $prepareHandler = $this->prepareHandler($item->callback);
                if (count($prepareHandler) > 1) {
                    [$controller, $action] = $prepareHandler;
                    $scanAnnotation->scan($controller, $action);
                }
            }
        });
        $eventDispatcher->dispatch(new AfterDtoStart($serverConfig, $router));
        $scanAnnotation->clearScanClassArray();
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                return explode('@', $handler);
            }

            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }

        throw new RuntimeException('Handler not exist.');
    }
}
