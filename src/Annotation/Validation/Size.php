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

namespace Littler\DTO\Annotation\Validation;

use Attribute;

/**
 * 验证字段必须有和给定值 value 相匹配的尺寸/大小，对字符串而言，value 是相应的字符数目；对数值而言，value 是给定整型值；对数组而言，value 是数组长度；对文件而言，value 是相应的文件千字节数（KB）.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Size extends BaseValidation
{
    /**
     * @var string
     */
    protected mixed $rule = 'size';

    public function __construct(int $value, string $messages = '')
    {
        $this->messages = $messages;
        $this->rule = $this->rule . ':' . $value;
    }
}
