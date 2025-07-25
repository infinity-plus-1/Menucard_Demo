<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
class PasswordValidator extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(allowNull: false),
            new Assert\Length(min: 8, max: 20),
            new Assert\NotCompromisedPassword(),
            new Assert\Type('string'),
            new Assert\Regex (
                pattern: '/^(?=.*[A-Z]).(?=.*[!\-_*+?#@%&]).(?=.*[a-z]).(?=.*[0-9]).{8,20}$/',
                message: 'The password must contain at least: 1 Uppercase character A-Z, 1 Lowercase character a-z, ' .
                    '1 Number character 0-9, 1 Special character !-_*+?#@%&, 8-20 characters in total'
            ),
        ];
    }
}