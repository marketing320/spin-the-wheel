<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mobile device required</title>
    <x-head-fonts />
    @vite('resources/css/app.css')
</head>
<body class="event-theme grid min-h-screen place-items-center p-6 antialiased">
    <main class="card w-full max-w-lg text-center">
        <div class="event-icon mx-auto grid h-20 w-20 place-items-center rounded-2xl">
            <i data-lucide="smartphone" class="h-10 w-10"></i>
        </div>
        <h1 class="mt-7 text-xl leading-relaxed">Mobile device required</h1>
        <p class="mt-4 text-base text-slate-600">Open this player page on a phone or tablet to spin.</p>
    </main>
    <x-lucide-scripts />
</body>
</html>
