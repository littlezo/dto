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

use BackedEnum;
use ReflectionEnum;

class PropertyEnum
{
    /**
     * 返回的类型.
     */
    public ?string $backedType = null;

    /**
     * 名称.
     */
    public ?string $className = null;

    /**
     * 枚举类 value列表.
     */
    public ?array $valueList = null;

    public static function get(string $className): ?self
    {
        // @phpstan-ignore-next-line
        if (PHP_VERSION_ID < 80100 || ! is_subclass_of($className, BackedEnum::class)) {
            return null;
        }
        $propertyEnum = new self();

        try {
            /** @phpstan-ignore-next-line */
            $rEnum = new ReflectionEnum($className);
            $propertyEnum->backedType = (string) $rEnum->getBackingType();
        } catch (\ReflectionException) {
            $propertyEnum->backedType = 'string';
        }
        $propertyEnum->className = trim($className, '\\');
        $propertyEnum->valueList = collect($className::cases())->map(fn ($v) => $v->value)->all();

        return $propertyEnum;
    }
}
