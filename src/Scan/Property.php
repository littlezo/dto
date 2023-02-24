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

class Property
{
    /**
     * 是否为简单类型.
     */
    public bool $isSimpleType = true;

    /**
     * PHP简单类型.
     *
     * @var string|null 'string' 'boolean' 'bool' 'integer' 'int' 'double' 'float' 'array' 'object'
     */
    public ?string $phpSimpleType = null;

    /**
     * 普通类名称.
     */
    public ?string $className = null;

    /**
     * 数组 中 复杂 类的名称.
     */
    public ?string $arrClassName = null;

    /**
     * 数组 中 简单类型  eg: int[]  string[].
     */
    public ?string $arrSimpleType = null;

    /**
     * 枚举类.
     */
    public ?PropertyEnum $enum = null;

    public function isSimpleArray(): bool
    {
        return (bool) ($this->isSimpleType && $this->phpSimpleType == 'array');
    }

    public function isSimpleTypeArray(): bool
    {
        return (bool) (! $this->isSimpleType && $this->phpSimpleType == 'array' && $this->arrSimpleType != null);
    }

    public function isClassArray(): bool
    {
        return (bool) (! $this->isSimpleType && $this->phpSimpleType == 'array' && $this->arrClassName != null);
    }
}
