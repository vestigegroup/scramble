<?php

namespace Dedoc\Scramble\Support\Type;

use Dedoc\Scramble\Support\Generator\Types\TypeAttributes;

abstract class AbstractType implements Type
{
    use TypeAttributes;

    public function getPropertyFetchType(string $propertyName): Type
    {
        return new UnknownType('Cannot find property fetch type.');
    }

    public function isInstanceOf(string $className)
    {
        return false;
    }
}
