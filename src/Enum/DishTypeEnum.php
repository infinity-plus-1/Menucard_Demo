<?php

namespace App\Enum;

enum DishTypeEnum: string
{
    case SALAD = 'Salad';
    case SOUP = 'Soup';
    case BREAD = 'Bread';
    case ANTIPASTO = 'Antipasto';
    case GENERAL_APPETIZER = 'General Appetizer';
    case MEAT_PORK = 'Pork';
    case MEAT_BEEF = 'Beef';
    case STEAK = 'Steak';
    case FISH = 'Fish';
    case SEAFOOD = 'Seafood';
    case PASTA = 'Pasta';
    case PIZZA = 'Pizza';
    case BURGER = 'Burger';
    case GENERAL_MAIN_COURSE = 'General main course';
    case ICE_CREAM = 'Ice cream';
    case CAKE = 'Cake';
    case SWEETS = 'Sweets';
    case GENERAL_DESSERT = 'General dessert';

    public function en_uk(): string
    {
        return match ($this) {
            DishTypeEnum::SALAD => 'Salad',
            DishTypeEnum::SOUP => 'Soup',
            DishTypeEnum::BREAD => 'Bread',
            DishTypeEnum::ANTIPASTO => 'Antipasto',
            DishTypeEnum::GENERAL_APPETIZER => 'General appetizer',
            DishTypeEnum::MEAT_PORK => 'Pork',
            DishTypeEnum::MEAT_BEEF => 'Beef',
            DishTypeEnum::STEAK => 'Steak',
            DishTypeEnum::FISH => 'Fish',
            DishTypeEnum::SEAFOOD => 'Seafood',
            DishTypeEnum::PASTA => 'Pasta',
            DishTypeEnum::PIZZA => 'Pizza',
            DishTypeEnum::BURGER => 'Burger',
            DishTypeEnum::GENERAL_MAIN_COURSE => 'General main course',
            DishTypeEnum::ICE_CREAM => 'Ice cream',
            DishTypeEnum::CAKE => 'Cake',
            DishTypeEnum::SWEETS => 'Sweets',
            DishTypeEnum::GENERAL_DESSERT => 'General dessert',
        };
    }
}