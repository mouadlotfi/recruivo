<?php

namespace App\Exceptions;

use RuntimeException;

class RegistrationDisabledException extends RuntimeException
{
    public static function onDemo(): self
    {
        return new self('Registrations are disabled on the demo environment.');
    }
}
