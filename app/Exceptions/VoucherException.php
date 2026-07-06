<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain error raised when a voucher lookup or redemption cannot proceed.
 */
class VoucherException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $message = 'No voucher was found for that code.'): self
    {
        return new self('not_found', $message);
    }

    public static function alreadyRedeemed(string $message = 'This voucher has already been redeemed.'): self
    {
        return new self('already_redeemed', $message);
    }

    public static function expired(string $message = 'This voucher has expired.'): self
    {
        return new self('expired', $message);
    }

    public static function notRedeemable(string $message = 'This voucher cannot be redeemed.'): self
    {
        return new self('not_redeemable', $message);
    }
}
