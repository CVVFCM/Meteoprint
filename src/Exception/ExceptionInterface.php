<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Marker interface implemented by every exception thrown by this project.
 *
 * Custom exceptions must extend a native PHP exception (\RuntimeException,
 * \InvalidArgumentException, ...) and implement this interface, so callers can
 * catch any project-originated error with a single catch (ExceptionInterface).
 */
interface ExceptionInterface extends \Throwable
{
}
