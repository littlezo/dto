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

use Hyperf\Contract\Arrayable;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\ApplicationContext;

class Mapper
{
    protected static array $jsonMapper = [];

    public static function map($json, object $object)
    {
        return static::getJsonMapper()->map($json, $object);
    }

    public static function mapDto($json, object $object)
    {
        return static::getJsonMapper()->mapDto($json, $object);
    }

    public static function copyProperties($source, object $target)
    {
        if ($source == null) {
            return null;
        }
        if ($source instanceof Arrayable) {
            return static::getJsonMapper()->map($source->toArray(), $target);
        }

        return static::getJsonMapper()->map($source, $target);
    }

    public static function mapArray($json, string $className)
    {
        if (empty($json)) {
            return [];
        }
        if ($json instanceof Arrayable) {
            return static::getJsonMapper()->mapArray($json->toArray(), [], $className);
        }

        return static::getJsonMapper()->mapArray($json, [], $className);
    }

    public static function getJsonMapper($key = 'default'): JsonMapper
    {
        if (! isset(static::$jsonMapper[$key])) {
            $jsonMapper = new JsonMapper();
            $logger = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $jsonMapper->setLogger($logger);
            // 将数组传递给映射
            $jsonMapper->bEnforceMapType = false;
            $jsonMapper->bStrictNullTypes = false;
            static::$jsonMapper[$key] = $jsonMapper;
        }

        return static::$jsonMapper[$key];
    }
}
