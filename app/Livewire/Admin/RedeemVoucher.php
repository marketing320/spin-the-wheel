<?php

namespace App\Livewire\Admin;

use App\Exceptions\VoucherException;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Staff-facing voucher redemption: scan (camera or hardware barcode scanner)
 * or type a code, review the prize + privacy-masked customer details, then
 * confirm to redeem. Two-step by design so a bad scan can't burn a voucher.
 */
#[Layout('components.layouts.admin', ['title' => 'Redeem Voucher'])]
class RedeemVoucher extends Component
{
    public string $codeInput = '';

    public ?string $error = null;

    /** @var array<string, mixed>|null */
    public ?array $pending = null;

    public function lookup(VoucherService $vouchers): void
    {
        $this->error = null;
        $this->pending = null;

        $code = trim($this->codeInput);
        $this->codeInput = '';

        if ($code === '') {
            return;
        }

        $voucher = $vouchers->findByCode($code);

        if (! $voucher) {
            $this->error = 'No voucher was found for that code.';

            return;
        }

        $masked = $vouchers->maskedCustomerInfo($voucher->player, $voucher->campaign);
        $reminder = filled($voucher->staff_redemption_reminder)
            ? trim($voucher->staff_redemption_reminder)
            : null;

        $this->pending = [
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'prize_name' => $voucher->prize?->name,
            'prize_image' => $voucher->prize?->imageUrl(),
            'masked_email' => $masked['email'],
            'masked_full_name' => $masked['full_name'],
            'masked_phone' => $masked['phone'],
            'expires_at' => $voucher->expires_at->toIso8601String(),
            'expires_at_human' => $voucher->expires_at->format('M j, Y g:i A'),
            'redeemable' => $voucher->isRedeemable(),
            'status' => $voucher->isRedeemed() ? 'redeemed' : ($voucher->isExpired() ? 'expired' : 'pending'),
            'staff_redemption_reminder' => $reminder,
        ];

        if ($reminder) {
            $this->dispatch(
                'voucher-reminder',
                title: 'Staff redemption reminder',
                message: $reminder,
            );
        }
    }

    public function confirmRedeem(VoucherService $vouchers): void
    {
        if (! $this->pending) {
            return;
        }

        $voucher = Voucher::find($this->pending['voucher_id']);

        try {
            $vouchers->redeem($voucher, Auth::guard('web')->user());
            $this->dispatch('admin-toast', message: "Voucher {$voucher->code} redeemed — {$voucher->prize?->name}.");
            $this->cancel();
        } catch (VoucherException $e) {
            $this->error = $e->getMessage();
            $this->pending = null;
        }
    }

    public function cancel(): void
    {
        $this->pending = null;
        $this->error = null;
        $this->codeInput = '';
    }

    public function render()
    {
        return view('livewire.admin.redeem-voucher');
    }
}
