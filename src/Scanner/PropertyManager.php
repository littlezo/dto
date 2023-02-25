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

namespace Littler\DTO\Scanner;

class PropertyManager
{
    protected static array $content = [];

    protected static array $notSimpleClass = [];

    public static function getAll(): array
    {
        return [static::$content, static::$notSimpleClass];
    }

    public static function setNotSimpleClass($className): void
    {
        $className = trim((string) $className, '\\');
        static::$notSimpleClass[$className] = true;
    }

    /**
     * 设置类中字段的属性.
     */
    public static function setProperty(string $className, string $fieldName, ScanProperty $property): void
    {
        $className = trim($className, '\\');
        if (isset(static::$content[$className][$fieldName])) {
            return;
        }
        static::$content[$className][$fieldName] = $property;
    }

    /**
     * 获取类中字段的属性.
     *
     * @param $className
     * @param $fieldName
     */
    public static function getProperty(string $className, ?string $fieldName = null): ScanProperty|array|null
    {
        $className = trim($className, '\\');
        if (empty($fieldName)) {
            return static::$content[$className] ?? [];
        }

        if (! isset(static::$content[$className][$fieldName])) {
            return null;
        }

        return static::$content[$className][$fieldName];
    }

    public static function getPropertyByType($className, $type, bool $isSimpleType): array
    {
        $className = trim((string) $className, '\\');
        if (! isset(static::$content[$className])) {
            return [];
        }
        $data = [];
        foreach (static::$content[$className] as $fieldName => $propertyArr) {
            /** @var ScanProperty $property */
            foreach ($propertyArr as $property) {
                if (
                    $property->type == $type
                    && $property->isSimpleType == $isSimpleType
                ) {
                    $data[$fieldName] = $property;
                }
            }
        }

        return $data;
    }

    /**
     * @param $className
     *
     * @return ScanProperty[]
     */
    public static function getPropertyAndNotSimpleType($className): array
    {
        $className = trim((string) $className, '\\');
        if (! isset(static::$notSimpleClass[$className])) {
            return [];
        }
        $data = [];
        foreach (static::$content[$className] as $fieldName => $property) {
            if ($property->isSimpleType == false) {
                $data[$fieldName] = $property;
            }
        }

        return $data;
    }
}
