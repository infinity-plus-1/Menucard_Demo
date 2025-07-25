<?php

namespace App\Enum;

enum DishSizesEnum: string
{
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';

    public function en_uk(): string
    {
        return match ($this) {
            DishSizesEnum::XS => 'XS',
            DishSizesEnum::S => 'S',
            DishSizesEnum::M => 'M',
            DishSizesEnum::L => 'L',
            DishSizesEnum::XL => 'XL',
            DishSizesEnum::XXL => 'XXL',
        };
    }
}