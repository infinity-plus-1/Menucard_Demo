<?php

namespace App\Enum;

enum CuisinesEnum: string {

    case ITALIAN = 'Italian';
    case CHINESE = 'Chinese';
    case THAI = 'Thai';
    case VIETNAMESE = 'Vietnamese';
    case FRENCH = 'French';
    case SPANISH = 'Spanish';
    case GERMAN = 'German';


    public function en_uk(): string
    {
        return match($this)
        {
            CuisinesEnum::ITALIAN => 'Italian',
            CuisinesEnum::CHINESE => 'Chinese',
            CuisinesEnum::THAI => 'Thai',
            CuisinesEnum::VIETNAMESE => 'Vietnamese',
            CuisinesEnum::FRENCH => 'French',
            CuisinesEnum::SPANISH => 'Spanish',
            CuisinesEnum::GERMAN => 'German',
        };
    }
}