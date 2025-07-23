<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidRoleValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $constraint \App\Validator\ValidRole */

        if (null === $value || $value === '' || $value === '0') {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
        // You can also check against allowed values:
        $allowedRoles = ['ROLE_CONSUMER', 'ROLE_COMPANY'];

        $filtered = [];

        if (!is_array($value)) $value = [$value];

        $filtered = array_intersect($value, $allowedRoles);

        if ($filtered === []) {
            $this->context->buildViolation('No role selected.')
                ->addViolation();
        }
    }
}
