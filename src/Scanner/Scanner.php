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

use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Littler\Abstract\BaseEnum;
use Littler\Constant\PropertyScope;
use Littler\DTO\Annotation as DTOA;
use Littler\DTO\Annotation\ArrayType;
use Littler\DTO\Annotation\ModelProperty;
use Littler\DTO\Annotation\RequestBody;
use Littler\DTO\Annotation\RequestFormData;
use Littler\DTO\Annotation\RequestHeader;
use Littler\DTO\Annotation\RequestQuery;
use Littler\DTO\Annotation\Validation;
use Littler\DTO\Annotation\Validation\BaseValidation;
use Littler\DTO\ApiAnnotation;
use Littler\DTO\Exception\DtoException;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionProperty;
use Throwable;

class Scanner
{
    private static array $scanClassArray = [];

    public function __construct(
        private ContainerInterface $container,
        private MethodDefinitionCollectorInterface $methodDefinitionCollector
    ) {
    }

    public function clearScanClassArray(): void
    {
        self::$scanClassArray = [];
    }

    /**
     * 扫描控制器中的方法.
     */
    public function scan(string $className, string $methodName): void
    {
        $this->setMethodParameters($className, $methodName);
        $definitionArr = $this->methodDefinitionCollector->getParameters($className, $methodName);
        $definitionArr[] = $this->methodDefinitionCollector->getReturnType($className, $methodName);
        foreach ($definitionArr as $definition) {
            $parameterClassName = $definition->getName();
            if ($this->container->has($parameterClassName)) {
                $this->scanClass($parameterClassName);
            }
        }
    }

    /**
     * 扫描类.
     */
    public function scanClass(string $className): void
    {
        if (in_array($className, self::$scanClassArray)) {
            return;
        }
        self::$scanClassArray[] = $className;
        $reflectionClass = ReflectionManager::reflectClass($className);
        foreach ($reflectionClass->getProperties() ?? [] as $reflectionProperty) {
            $type = $reflectionProperty->getType();
            $fieldName = $reflectionProperty->getName();
            $isSimpleType = true;

            $propertyClass = $type->getName();
            if ($type->isBuiltin()) {    // 内建类型
                if ($propertyClass == 'array') { // 数组类型特殊处理
                    $attributes = $reflectionProperty->getAttributes(ArrayType::class);
                    if (! empty($attributes)) {
                        $propertyClass = $attributes[0]->newInstance()->value;
                        if (class_exists($propertyClass)) {
                            $isSimpleType = false;
                            $this->scanClass($propertyClass);
                        }
                    }
                }
            } else {
                if (! is_subclass_of($propertyClass, BaseEnum::class)) {
                    $this->scanClass($propertyClass);
                    $isSimpleType = false;
                }
            }
            $property = new ScanProperty();
            $property->type = $type->getName();
            $property->isSimpleType = $isSimpleType;
            $property->className = $propertyClass ? trim((string) $propertyClass, '\\') : null;
            $property->scope = $this->getPropertyScope($reflectionProperty);
            PropertyManager::setProperty($className, $fieldName, $property);
            $this->generateValidation($className, $fieldName);
        }
    }

    protected function getPropertyScope(ReflectionProperty $reflectionProperty): PropertyScope
    {
        $annotation = $reflectionProperty->getAttributes(DTOA\Property::class)[0] ?? null;
        if (! $annotation instanceof ReflectionAttribute) {
            $annotation = $reflectionProperty->getAttributes(DTOA\Attribute::class)[0] ?? null;
        }
        if (! $annotation instanceof ReflectionAttribute) {
            $annotation = $reflectionProperty->getAttributes(DTOA\Header::class)[0] ?? null;
        }
        if (! $annotation instanceof ReflectionAttribute) {
            $annotation = $reflectionProperty->getAttributes(DTOA\PathProperty::class)[0] ?? null;
        }
        if (! $annotation instanceof ReflectionAttribute) {
            $annotation = $reflectionProperty->getAttributes(DTOA\FileProperty::class)[0] ?? null;
        }
        if (! $annotation instanceof ReflectionAttribute) {
            $annotation = $reflectionProperty->getAttributes(DTOA\HeaderProperty::class)[0] ?? null;
        }
        if (! $annotation instanceof ReflectionAttribute) {
            return PropertyScope::BODY();
        }

        /** @var DTOA\Property $property */
        $property = $annotation->newInstance();

        return $property->scope;
    }

    /**
     * 生成验证数据.
     */
    protected function generateValidation(string $className, string $fieldName): void
    {
        /** @var BaseValidation[] $validation */
        $validationArr = [];
        $annotationArray = ApiAnnotation::getClassProperty($className, $fieldName);

        foreach ($annotationArray as $annotation) {
            if ($annotation instanceof BaseValidation) {
                $validationArr[] = $annotation;
            }
        }
        $ruleArray = [];
        foreach ($validationArr as $validation) {
            if (empty($validation->getRule())) {
                continue;
            }
            $ruleArray[] = $validation->getRule();
            if (empty($validation->messages)) {
                continue;
            }
            [$messagesRule] = explode(':', (string) $validation->getRule());
            $key = $fieldName . '.' . $messagesRule;
            ValidationManager::setMessages($className, $key, $validation->messages);
        }
        if (! empty($ruleArray)) {
            ValidationManager::setRule($className, $fieldName, $ruleArray);
            foreach ($annotationArray as $annotation) {
                if (class_exists(
                    ModelProperty::class
                ) && $annotation instanceof ModelProperty && ! empty($annotation->value)) {
                    ValidationManager::setAttributes($className, $fieldName, $annotation->value);
                }
            }
        }
    }

    /**
     * 获取PHP类型.
     */
    protected function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()
                ->getName();
        } catch (Throwable) {
            $type = 'string';
        }

        return $type;
    }

    protected function isSimpleType($type): bool
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object';
    }

    /**
     * 设置方法中的参数.
     */
    private function setMethodParameters(mixed $className, mixed $methodName): void
    {
        // 获取方法的反射对象
        $ref = ReflectionManager::reflectMethod($className, $methodName);
        // 获取方法上指定名称的全部注解
        $attributes = $ref->getParameters();
        $methodMark = 0;
        $headerMark = 0;
        $total = 0;
        foreach ($attributes as $attribute) {
            $methodParameters = new MethodParameter();
            $paramName = $attribute->getName();
            $methodMark = 0;
            $mark = 0;
            if ($attribute->getAttributes(RequestQuery::class)) {
                $methodParameters->setIsRequestQuery(true);
                ++$mark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestFormData::class)) {
                $methodParameters->setIsRequestFormData(true);
                ++$mark;
                ++$methodMark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestBody::class)) {
                $methodParameters->setIsRequestBody(true);
                ++$mark;
                ++$methodMark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestHeader::class)) {
                $methodParameters->setIsRequestHeader(true);
                ++$headerMark;
                ++$total;
            }
            if ($attribute->getAttributes(Validation::class)) {
                $methodParameters->setIsValid(true);
            }
            if ($mark > 1) {
                throw new DtoException(
                    "Parameter annotation [RequestQuery RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}:{$paramName}]"
                );
            }
            if ($headerMark > 1) {
                throw new DtoException(
                    "Parameter annotation [RequestHeader] can only exist [{$className}::{$methodName}:{$paramName}]"
                );
            }
            if ($total > 0) {
                MethodParametersManager::setContent($className, $methodName, $paramName, $methodParameters);
            }
        }
        if ($methodMark > 1) {
            throw new DtoException(
                "Method annotation [RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}]"
            );
        }
    }
}
