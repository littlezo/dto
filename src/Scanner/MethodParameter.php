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

class MethodParameter
{
    private bool $isRequestBody = false;

    private bool $isRequestFormData = false;

    private bool $isRequestQuery = false;

    private bool $isRequestHeader = false;

    private bool $isValid = false;

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    public function isRequestBody(): bool
    {
        return $this->isRequestBody;
    }

    public function setIsRequestBody(bool $isRequestBody): self
    {
        $this->isRequestBody = $isRequestBody;

        return $this;
    }

    public function isRequestFormData(): bool
    {
        return $this->isRequestFormData;
    }

    public function setIsRequestFormData(bool $isRequestFormData): self
    {
        $this->isRequestFormData = $isRequestFormData;

        return $this;
    }

    public function isRequestQuery(): bool
    {
        return $this->isRequestQuery;
    }

    public function setIsRequestQuery(bool $isRequestQuery): self
    {
        $this->isRequestQuery = $isRequestQuery;

        return $this;
    }

    public function isRequestHeader(): bool
    {
        return $this->isRequestHeader;
    }

    public function setIsRequestHeader(bool $isRequestHeader): void
    {
        $this->isRequestHeader = $isRequestHeader;
    }
}
