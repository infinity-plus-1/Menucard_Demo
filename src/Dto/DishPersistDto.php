<?php

namespace App\Dto;

use App\Entity\Dish;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints;

#[Map(target: Dish::class)]
class DishPersistDto
{
    public function __construct(
        #[Constraints\NotBlank]
        public readonly ?int $id = NULL,
        #[Constraints\NotBlank]
        public readonly ?string $name = NULL,
        public readonly ?string $description = NULL,
        #[Constraints\NotBlank]
        public readonly ?float $price = NULL,
        #[Constraints\NotBlank]
        public readonly ?float $totalPrice = NULL,
        public readonly ?string $img = NULL,
        #[Constraints\NotBlank]
        public readonly ?int $company = NULL,
        public readonly ?string $size = NULL,
        #[Constraints\NotBlank]
        public readonly ?string $category = NULL,
        #[Constraints\NotBlank]
        public readonly ?string $type = NULL,
        public readonly ?array $extras = NULL,
        public readonly ?array $extrasGroups = NULL,
    ) {}
    
}