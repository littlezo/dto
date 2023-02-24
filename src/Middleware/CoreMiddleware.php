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

namespace Littler\DTO\Middleware;

use FastRoute\Dispatcher;
use Hyperf\Context\Context;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\HttpServer\CoreMiddleware as HttpCoreMiddleware;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Server\Exception\ServerException;
use InvalidArgumentException;
use Littler\Annotation\Definition;
use Littler\DTO\Mapper;
use Littler\DTO\Scan\MethodParametersManager;
use Littler\DTO\Scan\PropertyAliasMappingManager;
use Littler\DTO\ValidationDto;
use Littler\Enum\BaseEnum;
use Littler\Response\BaseResponse;
use Littler\Response\ErrorResponse;
use Littler\Response\Response;
use Littler\Response\StreamResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Definition(values: [HttpCoreMiddleware::class])]
class CoreMiddleware extends HttpCoreMiddleware
{
    public function dispatch(ServerRequestInterface $request): ServerRequestInterface
    {
        return parent::dispatch($request);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Context::set(ServerRequestInterface::class, $request);

        $dispatched = $request->getAttribute(Dispatched::class);
        assert($dispatched instanceof Dispatched);

        if (! $dispatched instanceof Dispatched) {
            throw new ServerException(sprintf('调度对象不是[%s]对象', Dispatched::class));
        }
        $response = match ($dispatched->status) {
            Dispatcher::NOT_FOUND => $this->handleNotFound($request),
            Dispatcher::METHOD_NOT_ALLOWED => $this->handleMethodNotAllowed($dispatched->params, $request),
            Dispatcher::FOUND => $this->handleFound($dispatched, $request),
            default => null,
        };

        if (! $response instanceof ResponseInterface) {
            $response = $this->transferToResponse($response, $request);
        }

        return $response;
    }

    protected function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        $response = new ErrorResponse();
        $response->code = BaseEnum::NOT_FOUND;
        $response->message = '非法请求';
        $response->withStatus(404);

        return $this->transferToResponse($response, $request);
    }

    protected function handleMethodNotAllowed(array $methods, ServerRequestInterface $request): ResponseInterface
    {
        $response = new ErrorResponse();
        $response->code = BaseEnum::METHOD_NOT_ALLOW;
        $response->message = t('little.allow_method', [
            'method' => implode(',', $methods),
        ]);
        $response->withStatus(405);

        return $this->transferToResponse($response, $request);
    }

    /**
     * Transfer the non-standard response content to a standard response object.
     *
     * @param Response|array|Arrayable|Jsonable|string|null $response
     */
    protected function transferToResponse($response, ServerRequestInterface $request): ResponseInterface
    {
        switch ($response) {
            case $response instanceof Response:
                $response = $response->send();

                break;
            case is_array($response):
            case $response instanceof Arrayable:
            case $response instanceof Jsonable:
                $response = new BaseResponse($response);
                $response = $response->send();

                break;
            case is_object($response):
                $response = new BaseResponse((array) $response);
                $response = $response->send();

                break;
            default:
                $response = new StreamResponse();
                $response->data = (string) $response;
                $response = $response->send();

                break;
        }

        return $response;
    }

    protected function parseMethodParameters(string $controller, string $action, array $arguments): array
    {
        $definitions = $this->getMethodDefinitionCollector()
            ->getParameters($controller, $action);

        return $this->getInjections($definitions, "{$controller}::{$action}", $arguments);
    }

    /**
     * @param ReflectionType[] $definitions
     */
    private function getInjections(array $definitions, string $callableName, array $arguments): array
    {
        $injections = [];
        foreach ($definitions ?? [] as $pos => $definition) {
            $value = $arguments[$pos] ?? $arguments[$definition->getMeta('name')] ?? null;
            if ($value === null) {
                if ($definition->getMeta('defaultValueAvailable')) {
                    $injections[] = $definition->getMeta('defaultValue');
                } elseif ($this->container->has($definition->getName())) {
                    $obj = $this->container->get($definition->getName());
                    $injections[] = $this->validateAndMap(
                        $callableName,
                        $definition->getMeta('name'),
                        $definition->getName(),
                        $obj
                    );
                } elseif ($definition->allowsNull()) {
                    $injections[] = null;
                } else {
                    throw new InvalidArgumentException(sprintf(
                        '参数%s（属于%s）不应为空',
                        $definition->getMeta('name'),
                        $callableName
                    ));
                }
            } else {
                $injections[] = $this->getNormalizer()->denormalize($value, $definition->getName());
            }
        }

        return $injections;
    }

    /**
     * @param string $callableName 'App\Controller\DemoController::index'
     */
    private function validateAndMap(string $callableName, string $paramName, string $className, mixed $obj): mixed
    {
        [$controllerName, $methodName] = explode('::', $callableName);
        $methodParameter = MethodParametersManager::getMethodParameter($controllerName, $methodName, $paramName);
        if ($methodParameter == null) {
            return $obj;
        }
        $validationDTO = $this->container->get(ValidationDto::class);
        /** @var ServerRequestInterface $request */
        $request = Context::get(ServerRequestInterface::class);
        $param = [];
        if ($methodParameter->isRequestBody()) {
            $param = $request->getParsedBody();
        } elseif ($methodParameter->isRequestQuery()) {
            $param = $request->getQueryParams();
            $route = $request->getAttribute(Dispatched::class);
            if ($route) {
                $param = array_replace($param, (array) $route->params);
            }
        } elseif ($methodParameter->isRequestFormData()) {
            $param = $request->getParsedBody();
        } elseif ($methodParameter->isRequestHeader()) {
            $param = array_map(fn ($value): string => $value[0], $request->getHeaders());
        }
        if (empty($param)) {
            $param = [];
        }
        dump($param);
        // validate
        if ($methodParameter->isValid()) {
            $validationDTO->validate($className, $param);
        }
        if (PropertyAliasMappingManager::isAliasMapping()) {
            return Mapper::mapDto($param, make($className));
        }

        return Mapper::map($param, make($className));
    }
}
