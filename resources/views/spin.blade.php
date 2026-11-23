<x-layouts.app title="Spin the Wheel" js-entry="resources/js/spin/spin-page.js" theme="spin-event">
    @php
        $config = [
            'csrf' => csrf_token(),
            'segments' => $segments,
            'geofenceEnabled' => $geofenceEnabled,
            'eligibility' => $eligibility,
            'soundUrl' => asset('sfx/spinning-wheel2.mp3'),
            'routes' => [
                'eligibility' => route('spin.eligibility'),
                'geofence' => route('spin.geofence'),
                'start' => route('spin.start'),
                'active' => route('spin.active'),
                'complete' => route('spin.complete', ['spin' => 'SPIN_ID']),
                'result' => url('/result/SPIN_ID'),
            ],
        ];
    @endphp

    <script type="application/json" id="spin-config">{!! json_encode($config) !!}</script>

    <div class="spin-event-layout" data-spin-root>
        <div class="spin-event-intro">
            <p class="event-partners">Brightstar × NVIDIA</p>
            <h1>Spin the wheel</h1>
        </div>

        {{-- Status banner --}}
        <div id="status-banner" role="status">
            @unless ($eligibility['eligible'])
                <div class="rounded-xl border-2 border-amber-300 bg-amber-50 px-4 py-3 text-base font-bold text-amber-800">
                    {{ $eligibility['message'] }}
                </div>
            @endunless
        </div>

        {{-- Live prize display — updates to whatever is under the pointer --}}
        <div id="pointer-prize" class="spin-event-prize">
            <span id="pointer-prize-chip" class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-md">
                <img id="pointer-prize-image" src="" alt="" class="hidden h-full w-full object-cover">
                <i id="pointer-prize-icon" data-lucide="gift" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="spin-event-prize-caption">At the pointer</p>
                <p id="pointer-prize-name">{{ $segments[0]['label'] ?? 'No prizes available' }}</p>
            </div>
        </div>

        <x-event-wheel pointer-id="wheel-pointer" />
        @if ($segments)
            <ul class="sr-only" aria-label="Wheel prizes">
                @foreach (array_unique(array_column($segments, 'label')) as $label)
                    <li>{{ $label }}</li>
                @endforeach
            </ul>
        @endif

        <div class="spin-event-action">
            <button id="spin-button" type="button" class="btn-primary"
                    @disabled(! $eligibility['eligible'])>
                <span data-label-idle class="items-center gap-2"><i data-lucide="rotate-cw" class="h-5 w-5" aria-hidden="true"></i> Spin the wheel</span>
                <span data-label-spinning class="hidden">Spinning…</span>
            </button>
            <p id="spin-hint" role="status"></p>
        </div>
    </div>

    {{-- Location verification modal — shown immediately when the active
         campaign requires geofencing and dismissed only after a server-side
         geofence check passes. --}}
    <div id="location-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="location-modal-title"
         aria-describedby="location-modal-message"
         aria-hidden="{{ $geofenceEnabled ? 'false' : 'true' }}"
         class="fixed inset-0 z-[70] {{ $geofenceEnabled ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/50 p-5 backdrop-blur-sm">
        <div class="card w-full max-w-sm text-center" tabindex="-1">
            <div class="relative mx-auto h-40 w-36" aria-hidden="true">
                <div class="animate-location-pointer absolute inset-x-0 top-0 flex justify-center">
                    <img src="{{ asset('img/loc_pointer.png') }}" alt="" class="pixelated h-32 w-auto object-contain">
                </div>
                <div class="animate-location-shadow absolute bottom-2 left-1/2 h-2 w-16 -translate-x-1/2 rounded-full bg-slate-900/20"></div>
            </div>

            <div id="location-modal-title" class="mt-2 font-display text-lg font-bold text-slate-900">Verifying your location</div>
            <p id="location-modal-message" class="mt-3 text-sm text-slate-600">Please allow location access while we confirm that you’re at the event.</p>

            <div class="mt-5 flex items-end justify-center gap-2" aria-hidden="true">
                <span class="location-loading-dot h-2.5 w-2.5 rounded-full bg-[var(--event-green)]"></span>
                <span class="location-loading-dot h-2.5 w-2.5 rounded-full bg-[var(--event-green)]"></span>
                <span class="location-loading-dot h-2.5 w-2.5 rounded-full bg-[var(--event-green)]"></span>
            </div>
            <span class="sr-only" role="status" aria-live="polite">Checking your current location.</span>
        </div>
    </div>

    {{-- Reusable runtime error modal. Expected eligibility verdicts continue
         to use the status banner above the wheel. --}}
    <div id="error-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="error-modal-title"
         aria-describedby="error-modal-message"
         aria-hidden="true"
         class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-900/50 p-5 backdrop-blur-sm">
        <div class="card w-full max-w-sm text-center" tabindex="-1">
            <span class="event-icon mx-auto grid h-14 w-14 place-items-center rounded-lg">
                <i data-lucide="triangle-alert" class="h-7 w-7"></i>
            </span>
            <div id="error-modal-title" class="mt-5 font-display text-lg font-bold text-slate-900">Something went wrong</div>
            <p id="error-modal-message" class="mt-3 text-sm leading-relaxed text-slate-600">Please try again.</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button id="error-modal-close" type="button" class="btn-ghost w-full !px-3">Close</button>
                <button id="error-modal-retry" type="button" class="btn-primary w-full !px-3">
                    <i data-lucide="rotate-cw" class="h-4 w-4"></i>
                    Try Again
                </button>
            </div>
        </div>
    </div>

    {{-- Result modal --}}
    <div id="result-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-5 backdrop-blur">
        <div class="card w-full max-w-sm text-center">
            <div class="font-display text-sm font-bold uppercase tracking-widest text-brand-600">You won</div>
            <img id="result-prize-image" src="" alt="" class="mx-auto mt-4 hidden h-32 w-32 rounded-2xl border-2 border-slate-900 object-cover">
            <div id="result-prize-name" class="mt-3 font-display text-2xl font-bold leading-tight text-slate-900 [word-break:break-word]">—</div>
            <div id="result-rarity" class="mt-3 flex justify-center"></div>

            {{-- Voucher prizes only: code + QR/barcode + a live countdown to expiry. --}}
            <div id="result-voucher" class="mt-4 hidden rounded-xl border-2 border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center justify-center gap-3">
                    <img id="result-voucher-qr" src="" alt="QR code" class="h-24 w-24 rounded-lg border-2 border-slate-900 bg-white p-1">
                    <img id="result-voucher-barcode" src="" alt="Barcode" class="h-16 max-w-[9rem] flex-1 rounded-lg border-2 border-slate-900 bg-white p-1">
                </div>
                <div id="result-voucher-code" class="mt-3 font-display text-sm font-bold tracking-widest text-slate-900">—</div>
                <div class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Expires in</div>
                <div id="result-voucher-countdown" class="font-display text-2xl font-bold text-cherry-600">00:00:00</div>
                <p class="mt-2 text-xs text-slate-500">Show this to staff to redeem.</p>
            </div>

            <a id="result-link" href="#" class="btn-primary mt-6 w-full">View prize →</a>
        </div>
    </div>
</x-layouts.app>
