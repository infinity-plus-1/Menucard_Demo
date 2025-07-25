<?php

namespace App\Dto;

use App\Entity\Company;
use App\Enum\CuisinesEnum;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: Company::class)]
class CompanyDto
{
    public function __construct(
        public readonly ?int $id = null,
        #[Constraints\NotBlank]
        public readonly ?string $name = null,
        #[Constraints\NotBlank]
        #[Constraints\Choice(callback: [CuisinesEnum::class, 'values'])]
        public readonly ?CuisinesEnum $type = null,
        #[Constraints\NotBlank]
        public readonly ?string $zip = null,
        #[Constraints\NotBlank]
        public readonly ?string $city = null,
        #[Constraints\NotBlank]
        public readonly ?string $street = null,
        #[Constraints\NotBlank]
        public readonly ?string $sn = null,
        #[Constraints\NotBlank]
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $website = null,
        #[Constraints\NotBlank]
        public readonly ?string $tax = null,
        public readonly ?string $logo = NULL,
        public readonly ?array $dishes = NULL,
        public readonly ?array $deliveryZips = NULL,
    ) {}
}