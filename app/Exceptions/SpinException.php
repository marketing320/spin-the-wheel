<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain error raised when a spin cannot proceed. Carries a machine-readable
 * reason code plus a player-safe message.
 */
class SpinException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly ?string $nextAvailableAt = null,
    ) {
        parent::__construct($message);
    }

    public static function notEligible(string $message, ?string $nextAvailableAt = null): self
    {
        return new self('not_eligible', $message, $nextAvailableAt);
    }

    public static function locked(string $message): self
    {
        return new self('spin_in_progress', $message);
    }

    public static function geofence(string $message): self
    {
        return new self('geofence_blocked', $message);
    }

    public static function noPrizes(string $message): self
    {
        return new self('no_prizes', $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'reason' => $this->reason,
            'message' => $this->getMessage(),
            'next_available_at' => $this->nextAvailableAt,
        ], fn ($v) => $v !== null);
    }
}
