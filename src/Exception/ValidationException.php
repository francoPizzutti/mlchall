<?php

namespace App\Exception;

use Exception;
use Throwable;

class ValidationException extends Exception implements Throwable
{
    private const MESSAGE = 'Failed trying to validate %s class.';
    
    public function __construct(string $className) 
    {
        parent::__construct(sprintf(self::MESSAGE, $className));
    }
}