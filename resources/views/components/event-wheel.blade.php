@props(['pointerId'])

<div {{ $attributes->class('spin-event-wheel') }} aria-hidden="true">
    <div id="wheel-stage" data-wheel-theme="event"></div>
    <div class="spin-event-rim"></div>
    <div id="{{ $pointerId }}" class="spin-event-pointer"></div>
    <div class="spin-event-hub">
        <img src="{{ asset('nvidia-logo-vert-rgb-blk-no-reg-for-screen.png') }}" alt="">
    </div>
</div>
