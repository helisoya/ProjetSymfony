<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class DateConstraint extends Constraint
{
    public const DEFAULT_START_FIELD = 'startDatetime';

    public string $startField;

    public function __construct($startField = null)
    {
        parent::__construct();

        $this->startField = $startField ?? self::DEFAULT_START_FIELD;
    }

    public function validatedBy(): string
    {
        return EventDateValidator::class;
    }

    public function getStartField()
    {
        return $this->startField;
    }
}
