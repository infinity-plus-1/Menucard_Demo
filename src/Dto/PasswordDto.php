<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: User::class)]
class PasswordDto
{
    #[Map(target: 'password', if: 'strlen')]
    public ?string $password = NULL;

    public ?string $oldPassword = NULL;
}