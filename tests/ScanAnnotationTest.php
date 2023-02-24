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

namespace HyperfTest\DTO;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\DTO\Scan\PropertyManager;
use Hyperf\DTO\Scan\ScanAnnotation;
use Hyperf\Utils\Reflection\ClassInvoker;
use HyperfTest\DTO\Controller\DemoController;
use HyperfTest\DTO\Request\Address;
use HyperfTest\DTO\Request\DemoBodyRequest;
use HyperfTest\DTO\Request\User;
use HyperfTest\DTO\Response\Activity;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class ScanAnnotationTest extends TestCase
{
    public function testScan(): void
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->with(MethodDefinitionCollectorInterface::class)->andReturn(new MethodDefinitionCollector());

        $scanAnnotation = new ScanAnnotation($container, $container->get(MethodDefinitionCollectorInterface::class));

        /** @var ScanAnnotation $scanAnnotation */
        $scanAnnotation = new ClassInvoker($scanAnnotation);

        $scanAnnotation->scan(DemoController::class, 'add');

        $property = PropertyManager::getProperty(DemoBodyRequest::class, 'int');
        $this->assertSame('int', $property->phpSimpleType);
        $this->assertNull($property->className);
        $this->assertTrue($property->isSimpleType);

        $property = PropertyManager::getProperty(DemoBodyRequest::class, 'string');
        $this->assertSame('string', $property->phpSimpleType);
        $this->assertNull($property->className);
        $this->assertTrue($property->isSimpleType);

        $property = PropertyManager::getProperty(DemoBodyRequest::class, 'arrClass');
        $this->assertSame('array', $property->phpSimpleType);
        $this->assertSame(Address::class, trim((string) $property->arrClassName, '\\'));
        $this->assertFalse($property->isSimpleType);
        $this->assertNull($property->arrSimpleType);

        $property = PropertyManager::getProperty(DemoBodyRequest::class, 'arrInt');
        $this->assertSame('array', $property->phpSimpleType);
        $this->assertNull($property->arrClassName);
        $this->assertNull($property->className);
        $this->assertFalse($property->isSimpleType);
        $this->assertSame('int', $property->arrSimpleType);

        $property = PropertyManager::getProperty(Address::class, 'user');
        $this->assertNull($property->phpSimpleType);
        $this->assertSame(User::class, trim((string) $property->className, '\\'));
        $this->assertFalse($property->isSimpleType);

        // return
        $property = PropertyManager::getProperty(Activity::class, 'id');
        $this->assertSame('string', $property->phpSimpleType);
        $this->assertNull($property->className);
        $this->assertTrue($property->isSimpleType);
    }

    protected function tearDown(): void
    {
        m::close();
        AnnotationCollector::clear();
    }
}
