<?php

namespace Flytedan\DanxLaravel\Exceptions;

use Exception;
use Monolog\Level;

/**
 * This is a generic ValidationError that expects to render a client side message to the User.
 * Used for handling data validation exceptions, such as incorrect user input, etc.
 */
class ValidationError extends Exception
{
	public static int $level = (int)Level::Warning;

	public function isClientSafe()
	{
		return true;
	}
}
