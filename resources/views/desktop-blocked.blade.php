<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mobile device required</title>
    <x-head-fonts />
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-screen place-items-center bg-slate-50 p-6 antialiased">
    <main class="card w-full max-w-lg text-center">
        <div class="mx-auto grid h-20 w-20 place-items-center rounded-2xl border-[3px] border-slate-900 bg-cherry-500 pixel-shadow">
            <i data-lucide="smartphone" class="h-10 w-10 text-white"></i>
        </div>
        <h1 class="mt-7 text-xl leading-relaxed">Mobile device required</h1>
        <p class="mt-4 text-base text-slate-600">Open this player page on a phone or tablet to join the queue and spin.</p>
    </main>
    <x-lucide-scripts />
</body>
</html>
