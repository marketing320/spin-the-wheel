<x-layouts.app title="Spin the Wheel" js-entry="resources/js/spin/spin-page.js">
    @php
        $config = [
            'csrf' => csrf_token(),
            'segments' => $segments,
            'geofenceEnabled' => $geofenceEnabled,
            'eligibility' => $eligibility,
            'spinInProgress' => $spinInProgress,
            'queue' => $queue,
            'soundUrl' => asset('sfx/spinning-wheel2.mp3'),
            'routes' => [
                'eligibility' => route('spin.eligibility'),
                'geofence' => route('spin.geofence'),
                'queue' => route('spin.queue'),
                'start' => route('spin.start'),
                'active' => route('spin.active'),
                'complete' => route('spin.complete', ['spin' => 'SPIN_ID']),
                'result' => url('/result/SPIN_ID'),
            ],
        ];
    @endphp

    <script type="application/json" id="spin-config">{!! json_encode($config) !!}</script>

    <div class="flex w-full max-w-lg flex-col items-center" data-spin-root>
        {{-- Status banner --}}
        <div id="status-banner" class="mb-3 w-full text-center">
            @unless ($eligibility['eligible'])
                <div class="rounded-xl border-2 border-amber-300 bg-amber-50 px-4 py-3 text-base font-bold text-amber-800">
                    {{ $eligibility['message'] }}
                </div>
            @endunless
        </div>

        {{-- Live prize display — updates to whatever is under the pointer --}}
        <div id="pointer-prize" class="mb-4 flex w-full max-w-sm items-center justify-center gap-3 rounded-2xl border-[3px] border-slate-900 bg-white px-4 py-3 pixel-shadow">
            <span id="pointer-prize-chip" class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-lg border-2 border-slate-900" style="background:#0e75bc">
                <img id="pointer-prize-image" src="" alt="" class="hidden h-full w-full object-cover">
                <i id="pointer-prize-icon" data-lucide="gift" class="h-6 w-6 text-white"></i>
            </span>
            <span id="pointer-prize-name" class="font-display text-lg font-bold leading-tight text-slate-900">—</span>
        </div>

        {{-- Wheel stage — dominant, near full-width on mobile --}}
        <div class="relative mx-auto aspect-square w-[min(96vw,34rem)]">
            {{-- Pointer (points down into the wheel) --}}
            <div id="wheel-pointer" class="absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-1 origin-top">
                <div class="h-0 w-0 border-l-[20px] border-r-[20px] border-t-[36px] border-l-transparent border-r-transparent border-t-cherry-500 drop-shadow-[3px_3px_0_rgba(15,23,42,1)]"></div>
            </div>
            {{-- Three.js / canvas mount --}}
            <div id="wheel-stage" class="h-full w-full"></div>
            {{-- Center hub --}}
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-20 w-20 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow">
                <img src="{{ asset('logo-black.png') }}" alt="Logo" class="h-15 w-15 object-contain">
            </div>
        </div>

        {{-- Spin button --}}
        <button id="spin-button"
                class="pressable-control btn-primary mt-6 w-full py-4 text-xl @if ($eligibility['eligible'] && ! $spinInProgress) animate-pulse-glow @endif"
                @unless ($eligibility['eligible']) disabled @endunless>
            <span data-label-idle class="inline-flex items-center gap-2"><i data-lucide="sparkles" class="h-6 w-6"></i> <span data-label-idle-text>SPIN!</span></span>
            <span data-label-spinning class="hidden">Spinning…</span>
        </button>

        <p id="spin-hint" class="mt-3 min-h-6 text-center text-sm font-bold text-slate-600">
            @if ($queue['queued'])
                There are {{ $queue['ahead'] }} people in front of you. Please wait…
            @elseif ($spinInProgress)
                Another player is spinning…
            @endif
        </p>
    </div>

    {{-- Result modal --}}
    <div id="result-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-5 backdrop-blur">
        <div class="card w-full max-w-sm text-center">
            <div class="font-display text-sm font-bold uppercase tracking-widest text-brand-600">You won</div>
            <img id="result-prize-image" src="" alt="" class="mx-auto mt-4 hidden h-32 w-32 rounded-2xl border-2 border-slate-900 object-cover">
            <div id="result-prize-name" class="mt-3 font-display text-2xl font-bold leading-tight text-slate-900 [word-break:break-word]">—</div>
            <div id="result-rarity" class="mt-3 flex justify-center"></div>
            <p id="result-message" class="mt-4 text-sm text-slate-600"></p>

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
