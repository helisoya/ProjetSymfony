<?php

namespace App\Validator;

use DateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EventDateValidator extends ConstraintValidator
{
    public function validate(mixed $value, DateConstraint|Constraint $constraint): void
    {
        $startField = $constraint->getStartField();
        $getterStartField = 'get' . ucfirst($startField);

        if (
            $value->$getterStartField() !== null &&
            $value->$getterStartField() < new DateTime('+7day')
        ) {

            $this->context
                ->buildViolation('La date doit être dans le futur.')
                ->atPath((string)$startField)
                ->addViolation();
        }
    }
}
