<?php

namespace App\Exceptions;

use Exception;

/**
 * Business-rule failure for material transfer checkout; message is safe to show to the user.
 */
class MaterialTransferValidationException extends Exception {}
