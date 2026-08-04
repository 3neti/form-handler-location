<?php

declare(strict_types=1);

namespace LBHurtado\FormHandlerLocation\Exceptions;

use RuntimeException;

final class LocationEvidenceUnavailable extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
