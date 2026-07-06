<?php

namespace App\Support;

/**
 * Masks a raw string for display to staff during voucher redemption: the
 * first three characters stay visible, everything after is replaced with
 * asterisks (one asterisk per remaining character, spaces included).
 *
 * Distinct from Player::maskedEmail() (which keeps the domain readable for
 * the public live-view screen) — this is a stricter mask for a different
 * audience (cashiers looking up a stranger's booking), so it hides the whole
 * remainder of the string rather than just the local-part of an email.
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
