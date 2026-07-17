<?php

namespace App\Services;

use App\Exceptions\VoucherException;
use App\Models\Campaign;
use App\Models\FormField;
use App\Models\Player;
use App\Models\SpinResult;
use App\Models\User;
use App\Models\Voucher;
use App\Support\PrivacyMask;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Generates and redeems vouchers for voucher-type prizes. Redemption always
 * happens on the staff side (App\Livewire\Admin\RedeemVoucher) after the
 * player has already won and been shown their code/QR/barcode.
 */
class VoucherService
{
    /**
     * Create the voucher for a completed spin result, if (and only if) its
     * prize is voucher-typed. Idempotent — safe to call more than once for
     * the same result.
     */
    public function generateForResult(SpinResult $result): ?Voucher
    {
        $prize = $result->prize;

        if (! $prize || ! $prize->isVoucher()) {
            return null;
        }

        $existing = Voucher::where('spin_result_id', $result->id)->first();
        if ($existing) {
            return $existing;
        }

        $hours = (int) ($prize->voucher_expiry_hours ?: Settings::get('redemption.voucher_expiry_hours', 24));

        return Voucher::create([
            'spin_result_id' => $result->id,
            'campaign_id' => $result->campaign_id,
            'player_id' => $result->player_id,
            'prize_id' => $prize->id,
            'code' => $this->generateUniqueCode(),
            'staff_redemption_reminder' => $prize->staff_redemption_reminder,
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->addHours(max(1, $hours)),
        ]);
    }

    public function findByCode(string $code): ?Voucher
    {
        $code = mb_strtoupper(trim($code));

        return Voucher::with(['prize', 'player', 'campaign'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Redeem a voucher on behalf of a staff member. Locks the row so two
     * cashiers can't redeem the same code at once.
     *
     * @throws VoucherException when the voucher is not currently redeemable.
     */
    public function redeem(Voucher $voucher, User $staff): Voucher
    {
        return DB::transaction(function () use ($voucher, $staff) {
            $locked = Voucher::whereKey($voucher->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isRedeemable()) {
                $this->throwForUnredeemable($locked ?? $voucher);
            }

            $locked->forceFill([
                'status' => Voucher::STATUS_REDEEMED,
                'redeemed_at' => now(),
                'redeemed_by' => $staff->id,
            ])->save();

            return $locked;
        });
    }

    /**
     * @throws VoucherException always.
     */
    protected function throwForUnredeemable(Voucher $voucher): never
    {
        if ($voucher->isRedeemed()) {
            throw VoucherException::alreadyRedeemed();
        }

        if ($voucher->isExpired()) {
            throw VoucherException::expired();
        }

        throw VoucherException::notRedeemable();
    }

    /**
     * Privacy-masked customer details for the staff redemption screen: email
     * and full name are always available; phone only appears if the
     * campaign's registration form has a field typed "phone".
     *
     * @return array{email: ?string, full_name: ?string, phone: ?string}
     */
    public function maskedCustomerInfo(Player $player, Campaign $campaign): array
    {
        return [
            'email' => PrivacyMask::reveal3($player->email),
            'full_name' => PrivacyMask::reveal3($player->display_name),
            'phone' => PrivacyMask::reveal3($this->findPhone($player, $campaign)),
        ];
    }

    protected function findPhone(Player $player, Campaign $campaign): ?string
    {
        $phoneField = FormField::where('campaign_id', $campaign->id)
            ->where('field_type', 'phone')
            ->first();

        if (! $phoneField) {
            return null;
        }

        $response = $player->formResponses()
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->first();

        $value = data_get($response?->responses, $phoneField->field_key);

        return is_string($value) ? $value : null;
    }

    protected function generateUniqueCode(): string
    {
        // Excludes visually ambiguous characters (0/O, 1/I/L).
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $alphabet[random_int(0, mb_strlen($alphabet) - 1)];
            }
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }
}
