<?php

namespace App\Support;

/**
 * Masks a raw string for display to staff during voucher redemption: the
 * first three characters stay visible, everything after is replaced with
 * asterisks (one asterisk per remaining character, spaces included).
 */
class PrivacyMask
{
    public static function reveal3(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $visible = mb_substr($value, 0, 3);
        $remaining = max(0, mb_strlen($value) - 3);

        return $remaining > 0 ? $visible.str_repeat('*', $remaining) : $visible;
    }
}
