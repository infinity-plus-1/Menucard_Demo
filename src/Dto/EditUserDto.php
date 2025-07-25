<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: User::class)]
class EditUserDto
{
    #[Map(target: 'email', if: 'strlen')]
    public ?string $email = NULL;

    #[Map(target: 'forename', if: 'strlen')]
    public ?string $forename = NULL;

    #[Map(target: 'surname', if: 'strlen')]
    public ?string $surname = NULL;

    #[Map(target: 'street', if: 'strlen')]
    public ?string $street = NULL;

    #[Map(target: 'sn', if: 'strlen')]
    public ?string $sn = NULL;

    #[Map(target: 'zipcode', if: 'strlen')]
    public ?string $zipcode = NULL;

    #[Map(target: 'city', if: 'strlen')]
    public ?string $city = NULL;
}