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

namespace Littler\DTO\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Littler\DTO\Event\AfterDtoStart;
use Littler\DTO\Scanner\Scanner;
use Littler\Utils\Str;
use Throwable;

#[Listener]
class DtoToDotDts implements ListenerInterface
{
    public function listen(): array
    {
        return [AfterDtoStart::class];
    }

    public function process(object $event): void
    {
        try {
            if ($event instanceof AfterDtoStart) {
                foreach (Scanner::getTypeScriptType() as $class => $properties) {
                }
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    protected function headleProperty($class, $properties): void
    {
        foreach ($properties as $fieldName => $property) {
            // $fieldName = Str::snake($fieldName);
            // if (is_subclass_of($property->type, BaseObject::class)) {
            //   // $param[$fieldName] = $this->headleProperty($property->type, $param[$fieldName] ?? []);
            // }
            dump([
                "{$class}::{$fieldName}" => $property,
            ]);
        }
    }
}
