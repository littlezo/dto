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

use Hyperf\Utils\ApplicationContext;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Littler\DTO\Exception\DtoException;
use Littler\DTO\Scanner\PropertyManager;
use Littler\DTO\Scanner\ValidationManager;

class ValidationDto
{
    public static bool $isValidationCustomAttributes = false;

    private ?ValidatorFactoryInterface $validationFactory = null;

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        if ($container->has(ValidatorFactoryInterface::class)) {
            $this->validationFactory = $container->get(ValidatorFactoryInterface::class);
        }
    }

    public function validate(string $className, $data): void
    {
        if ($this->validationFactory == null) {
            return;
        }
        $this->validateResolved($className, $data);
    }

    private function validateResolved(string $className, $data): void
    {
        if (! is_array($data)) {
            throw new DtoException('Class:' . $className . ' data must be object or array');
        }

        $validArr = ValidationManager::getData($className);
        // dump($validArr);
        if (empty($validArr)) {
            return;
        }
        $validator = $this->validationFactory->make(
            $data,
            $validArr['rule'],
            $validArr['messages'] ?? [],
            static::$isValidationCustomAttributes ? ($validArr['attributes'] ?? []) : []
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        // 递归校验判断
        $notSimplePropertyArr = PropertyManager::getPropertyAndNotSimpleType($className);
        foreach ($notSimplePropertyArr as $fieldName => $property) {
            if (! empty($data[$fieldName])) {
                if ($property->isClassArray()) {
                    foreach ($data[$fieldName] as $item) {
                        $this->validateResolved($property->arrClassName, $item);
                    }
                } elseif ($property->className != null) {
                    $this->validateResolved($property->className, $data[$fieldName]);
                }
            }
        }
    }
}
