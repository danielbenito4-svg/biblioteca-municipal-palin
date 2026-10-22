<?php
declare(strict_types=1);
final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Revisa los datos del formulario.');
    }
}
