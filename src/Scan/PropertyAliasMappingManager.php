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

namespace Littler\DTO\Scan;

class PropertyAliasMappingManager
{
    protected static array $content = [];

    protected static array $aliasMappingClassname = [];

    protected static bool $isAliasMapping = false;

    public static function setAliasMapping(string $classname, string $alias, string $propertyName): void
    {
        static::$content[$alias] = $propertyName;
        static::$aliasMappingClassname[$classname] = true;
        static::$isAliasMapping = true;
    }

    public static function getAliasMapping(string $alias): ?string
    {
        return static::$content[$alias] ?? null;
    }

    public static function isAliasMappingClassname(string $classname): bool
    {
        return isset(static::$aliasMappingClassname[$classname]);
    }

    public static function isAliasMapping()
    {
        return static::$isAliasMapping;
    }
}
