<?php

namespace App\Exceptions;

use RuntimeException;

class CheckInFailed extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
    }

    public static function roomNotFound(): self
    {
        return new self('Room not found.', 404);
    }

    public static function childNotFound(): self
    {
        return new self('Child not found.', 404);
    }

    public static function childIdRequired(): self
    {
        return new self('The child id field is required.', 422);
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('This child is already checked into a room.', 409);
    }

    public static function checkInNotFound(): self
    {
        return new self('Check-in not found.', 404);
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('This child is already checked out.', 409);
    }
}
