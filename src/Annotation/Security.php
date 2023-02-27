<?php

declare(strict_types=1);

namespace Littler\DTO\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Littler\Constant\PropertyScope;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Security extends AbstractAnnotation
{
  public PropertyScope $scope;

  public function __construct(
    public string $name,
    public ?string $from=null,
    public $example = null,
    public bool $hidden = false
  ) {
    parent::__construct();
    $this->scope = PropertyScope::BODY();
  }
}
