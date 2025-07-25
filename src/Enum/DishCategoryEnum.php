<?php

namespace App\Enum;

enum DishCategoryEnum: string
{
    case APPETIZER = 'Appetizer';
    case MAIN_COURSE = 'Main course';
    case DESSERT = 'Dessert';
    case DRINKS = 'Drinks';

    public function en_uk(): string
    {
        return match ($this) {
            DishCategoryEnum::APPETIZER => 'Appetizer',
            DishCategoryEnum::MAIN_COURSE => 'Main course',
            DishCategoryEnum::DESSERT => 'Dessert',
            DishCategoryEnum::DRINKS => 'Drinks',
        };
    }

    public function getOrder(): int
    {
        return match ($this) {
            DishCategoryEnum::APPETIZER => 0,
            DishCategoryEnum::MAIN_COURSE => 1,
            DishCategoryEnum::DESSERT => 2,
            DishCategoryEnum::DRINKS => 3,
        }; 
    }
}