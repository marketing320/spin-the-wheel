<x-layouts.app title="Spin the Wheel" js-entry="resources/js/spin/spin-page.js">
    @php
        $config = [
            'csrf' => csrf_token(),
            'segments' => $segments,
            'geofenceEnabled' => $geofenceEnabled,
            'eligibility' => $eligibility,
            'spinInProgress' => $spinInProgress,
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

    <div class="flex w-full max-w-md flex-col items-center" data-spin-root>
        {{-- Status banner --}}
        <div id="status-banner" class="mb-4 w-full text-center">
            @unless ($eligibility['eligible'])
                <div class="glass rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                    {{ $eligibility['message'] }}
                </div>
            @endunless
        </div>

        {{-- Wheel stage --}}
        <div class="relative aspect-square w-full max-w-sm">
            {{-- Pointer --}}
            <div class="absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-1">
                <div class="h-0 w-0 border-l-[16px] border-r-[16px] border-t-[28px] border-l-transparent border-r-transparent border-t-white drop-shadow-lg"></div>
            </div>
            {{-- Three.js / canvas mount --}}
            <div id="wheel-stage" class="h-full w-full"></div>
            {{-- Center hub --}}
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-16 w-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-slate-900 text-2xl shadow-xl ring-4 ring-white/10">🎡</div>
        </div>

        {{-- Spin button --}}
        <button id="spin-button"
                class="btn-primary mt-8 w-full text-lg"
                @unless ($eligibility['eligible'] && ! $spinInProgress) disabled @endunless>
            <span data-label-idle>🎯 SPIN THE WHEEL</span>
            <span data-label-spinning class="hidden">Spinning…</span>
        </button>

        <p id="spin-hint" class="mt-3 h-5 text-center text-xs text-slate-400">
            @if ($spinInProgress) Another player is spinning right now… @endif
        </p>
    </div>

    {{-- Result modal --}}
    <div id="result-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-5 backdrop-blur">
        <div class="card w-full max-w-sm text-center">
            <div class="text-sm font-semibold uppercase tracking-widest text-slate-400">You won</div>
            <div id="result-prize-name" class="mt-2 text-3xl font-black text-white">—</div>
            <div id="result-rarity" class="mt-3 flex justify-center"></div>
            <p id="result-message" class="mt-4 text-sm text-slate-300"></p>
            <a id="result-link" href="#" class="btn-primary mt-6 w-full">View my prize →</a>
        </div>
    </div>
</x-layouts.app>
