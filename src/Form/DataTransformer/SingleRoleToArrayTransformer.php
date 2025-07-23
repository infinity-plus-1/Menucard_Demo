<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class SingleRoleToArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): mixed
    {
        return is_array($value) ? $value[0] : $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        return is_array($value) ? $value : [$value];
    }
}