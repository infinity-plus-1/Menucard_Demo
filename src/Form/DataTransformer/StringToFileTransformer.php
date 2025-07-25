<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

class StringToFileTransformer implements DataTransformerInterface
{
    public function transform(mixed $path): ?File
    {
        return (is_string($path) && $path !== '') ? new File($path) : NULL;
    }

    public function reverseTransform(mixed $file): string
    {
        return ($file instanceof File && $file) ? $file->getPath() : '';
    }
}