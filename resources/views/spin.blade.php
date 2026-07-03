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
                <div class="rounded-xl border-2 border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
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
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-16 w-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-2 border-slate-900 bg-white shadow-xl"><i data-lucide="ferris-wheel" class="h-7 w-7 text-cherry-500"></i></div>
        </div>

        {{-- Spin button --}}
        <button id="spin-button"
                class="btn-primary mt-8 w-full text-lg"
                @unless ($eligibility['eligible'] && ! $spinInProgress) disabled @endunless>
            <span data-label-idle class="inline-flex items-center gap-2"><i data-lucide="sparkles" class="h-5 w-5"></i> SPIN THE WHEEL</span>
            <span data-label-spinning class="hidden">Spinning…</span>
        </button>

        <p id="spin-hint" class="mt-3 h-5 text-center text-xs text-slate-400">
            @if ($spinInProgress) Another player is spinning right now… @endif
        </p>
    </div>

    {{-- Result modal --}}
    <div id="result-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-5 backdrop-blur">
        <div class="card w-full max-w-sm text-center">
            <div class="font-display text-sm font-bold uppercase tracking-widest text-brand-600">You won</div>
            <img id="result-prize-image" src="" alt="" class="mx-auto mt-4 hidden h-28 w-28 rounded-2xl border-2 border-slate-900 object-cover">
            <div id="result-prize-name" class="mt-2 font-display text-3xl font-bold text-slate-900">—</div>
            <div id="result-rarity" class="mt-3 flex justify-center"></div>
            <p id="result-message" class="mt-4 text-sm text-slate-600"></p>
            <a id="result-link" href="#" class="btn-primary mt-6 w-full">View my prize →</a>
        </div>
    </div>
</x-layouts.app>
