<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * Renders a voucher's redemption code as a QR code or Code128 barcode, on the
 * fly (nothing is stored on disk — both are cheap to regenerate from the
 * code string). Both encode the exact same value.
 */
class VoucherCodeController extends Controller
{
    public function __construct(protected VoucherService $vouchers) {}

    public function qr(string $code): Response
    {
        $voucher = $this->vouchers->findByCode($code);
        abort_if(! $voucher, 404);

        $result = (new Builder(writer: new SvgWriter()))->build(
            data: $voucher->code,
            size: 320,
            margin: 12,
        );

        return response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
    }

    public function barcode(string $code): Response
    {
        $voucher = $this->vouchers->findByCode($code);
        abort_if(! $voucher, 404);

        $generator = new BarcodeGeneratorSVG();
        $svg = $generator->getBarcode($voucher->code, $generator::TYPE_CODE_128, 2.4, 70);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
