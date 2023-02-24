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

use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Littler\DTO\Annotation\JSONField;
use Littler\DTO\Annotation\ModelProperty;
use Littler\DTO\Annotation\RequestBody;
use Littler\DTO\Annotation\RequestFormData;
use Littler\DTO\Annotation\RequestHeader;
use Littler\DTO\Annotation\RequestQuery;
use Littler\DTO\Annotation\Validation;
use Littler\DTO\Annotation\Validation\BaseValidation;
use Littler\DTO\ApiAnnotation;
use Littler\DTO\Exception\DtoException;
use Littler\DTO\JsonMapper;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionProperty;
use Throwable;

class ScanAnnotation extends JsonMapper
{
    private static array $scanClassArray = [];

    public function __construct(private ContainerInterface $container, private MethodDefinitionCollectorInterface $methodDefinitionCollector)
    {
    }

    /**
     * 扫描控制器中的方法.
     *
     * @throws ReflectionException
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

    public function clearScanClassArray(): void
    {
        self::$scanClassArray = [];
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
        $rc = ReflectionManager::reflectClass($className);
        $strNs = $rc->getNamespaceName();
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $isSimpleType = true;
            $phpSimpleType = null;
            $propertyClassName = null;
            $arrSimpleType = null;
            $arrClassName = null;
            $type = $this->getTypeName($reflectionProperty);
            // php简单类型
            if ($this->isSimpleType($type)) {
                $phpSimpleType = $type;
            }
            // 数组类型
            $propertyEnum = PropertyEnum::get($type);
            if ($type == 'array') {
                $docblock = $reflectionProperty->getDocComment();
                $annotations = $this->parseAnnotationsNew($rc, $reflectionProperty, $docblock);
                if (! empty($annotations)) {
                    // support "@var type description"
                    [$varType] = explode(' ', $annotations['var'][0]);
                    $varType = $this->getFullNamespace($varType, $strNs);
                    // 数组类型
                    if ($this->isArrayOfType($varType)) {
                        $isSimpleType = false;
                        $arrType = substr($varType, 0, -2);
                        // 数组的简单类型 eg: int[]  string[]
                        if ($this->isSimpleType($arrType)) {
                            $arrSimpleType = $arrType;
                        } elseif (class_exists($arrType)) {
                            $arrClassName = $arrType;
                            PropertyManager::setNotSimpleClass($className);
                            $this->scanClass($arrType);
                        }
                    }
                }
            } elseif ($propertyEnum) {
                $isSimpleType = false;
                PropertyManager::setNotSimpleClass($className);
            } elseif (class_exists($type)) {
                $this->scanClass($type);
                $isSimpleType = false;
                $propertyClassName = $type;
                PropertyManager::setNotSimpleClass($className);
            }

            $property = new Property();
            $property->phpSimpleType = $phpSimpleType;
            $property->isSimpleType = $isSimpleType;
            $property->arrSimpleType = $arrSimpleType;
            $property->arrClassName = $arrClassName ? trim($arrClassName, '\\') : null;
            $property->className = $propertyClassName ? trim($propertyClassName, '\\') : null;
            $property->enum = $propertyEnum;
            PropertyManager::setProperty($className, $fieldName, $property);
            $this->generateValidation($className, $fieldName);
            $this->propertyAliasMappingManager($className, $fieldName);
        }
    }

    /**
     * 生成验证数据.
     */
    protected function propertyAliasMappingManager(string $className, string $fieldName): void
    {
        $annotationArray = ApiAnnotation::getClassProperty($className, $fieldName);

        foreach ($annotationArray as $annotation) {
            if ($annotation instanceof JSONField) {
                if (! empty($annotation->name)) {
                    PropertyAliasMappingManager::setAliasMapping($className, $annotation->name, $fieldName);
                }
            }
        }
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
                if (class_exists(ModelProperty::class) && $annotation instanceof ModelProperty && ! empty($annotation->value)) {
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
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }

        return $type;
    }

    /**
     * 设置方法中的参数.
     *
     * @param mixed $className
     * @param mixed $methodName
     *
     * @throws ReflectionException
     */
    private function setMethodParameters($className, $methodName): void
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
                throw new DtoException("Parameter annotation [RequestQuery RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}:{$paramName}]");
            }
            if ($headerMark > 1) {
                throw new DtoException("Parameter annotation [RequestHeader] can only exist [{$className}::{$methodName}:{$paramName}]");
            }
            if ($total > 0) {
                MethodParametersManager::setContent($className, $methodName, $paramName, $methodParameters);
            }
        }
        if ($methodMark > 1) {
            throw new DtoException("Method annotation [RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}]");
        }
    }
}
