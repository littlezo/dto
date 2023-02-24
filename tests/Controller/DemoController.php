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

namespace HyperfTest\DTO\Controller;

use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use HyperfTest\DTO\Request\DemoBodyRequest;
use HyperfTest\DTO\Request\DemoFormData;
use HyperfTest\DTO\Request\DemoQuery;
use HyperfTest\DTO\Response\Activity;
use HyperfTest\DTO\Response\ActivityPage;

#[Controller(prefix: '/demo')]
class DemoController
{
    #[PutMapping(path: 'add')]
    public function add(#[RequestBody] #[Valid] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query): Activity
    {
        dump($request);
        var_dump($query);

        return new Activity();
    }

    /* #[PostMapping(path: 'fromData')]
     public function fromData(#[RequestFormData] DemoFormData $formData): array
     {
         return [];
     }

     #[GetMapping(path: 'find/{id}/and/{in}')]
     public function find(int $id, float $in): array
     {
         return ['$id' => $id, '$in' => $in];
     }

     #[GetMapping(path: 'page')]
     public function page(#[RequestQuery] PageQuery $pageQuery): ActivityPage
     {
         return new ActivityPage();
     }

     #[PutMapping(path: 'update/{id}')]
     public function update(int $id): int
     {
         return $id;
     }

     #[DeleteMapping(path: 'delete/{id}')]
     public function delete(int $id): int
     {
         return $id;
     }

     #[PatchMapping(path: 'patch/{id}')]
     public function patch(int $id): int
     {
         return 55;
     }*/
}
