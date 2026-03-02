<?php

namespace App\Exceptions;

use Exception;

class ErpAuthenticationException extends Exception
{
    public const USER_MESSAGE = 'ERP authentication failed. Please contact system administrator.';
}
