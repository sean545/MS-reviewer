<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Every reading for your master's degree, shelved by course and ready when you are.">

        <title>{{ config('app.name') }} — A quiet place to study</title>
        <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-forest font-sans text-cream antialiased">
        <div class="flex min-h-dvh flex-col lg:flex-row">
            {{-- Left: brand, headline and calls to action --}}
            <section class="relative flex flex-1 flex-col justify-between gap-12 overflow-hidden px-6 py-8 sm:px-12 lg:px-16 lg:py-11">
                <div aria-hidden="true" class="pointer-events-none absolute -right-36 -bottom-36 size-[420px] rounded-full border border-cream/15"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -right-16 -bottom-16 size-[260px] rounded-full border border-cream/15"></div>

                <header class="relative flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-lg font-extrabold tracking-tight">
                        <span class="flex size-9 items-center justify-center rounded-[10px] bg-gold">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="#1f4d36" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7z"/></svg>
                        </span>
                        {{ config('app.name') }}
                    </a>

                    @auth
                        <a href="{{ route('filament.learn.pages.dashboard') }}" class="rounded-full border border-cream/35 px-[18px] py-2.5 font-semibold transition hover:bg-cream/10">My dashboard</a>
                    @else
                        <a href="{{ route('filament.learn.auth.login') }}" class="rounded-full border border-cream/35 px-[18px] py-2.5 font-semibold transition hover:bg-cream/10">Sign in</a>
                    @endauth
                </header>

                <div class="relative flex flex-col gap-6">
                    <span class="self-start rounded-full bg-gold px-3.5 py-1.5 text-sm font-semibold text-forest">For master's students</span>
                    <h1 class="text-5xl leading-[0.98] font-extrabold tracking-[-0.05em] sm:text-7xl xl:text-[80px]">
                        A quiet place<br>to <span class="text-gold">study.</span>
                    </h1>
                    <p class="max-w-md text-lg leading-relaxed text-mist">Every reading for your degree, shelved by course and ready when you are.</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route('filament.learn.pages.dashboard') }}" class="rounded-full bg-cream px-7 py-4 font-bold text-forest transition hover:bg-white">Continue reviewing</a>
                        @else
                            <a href="{{ route('filament.learn.auth.register') }}" class="rounded-full bg-cream px-7 py-4 font-bold text-forest transition hover:bg-white">Begin reviewing</a>
                        @endauth
                        <a href="{{ route('filament.admin.auth.login') }}" class="px-3 py-4 font-semibold transition hover:text-gold">For faculty →</a>
                    </div>
                </div>

                <div class="relative flex flex-wrap gap-7 text-sm font-medium text-mist">
                    <span><span class="mr-1.5 font-extrabold text-gold">01</span>Read</span>
                    <span><span class="mr-1.5 font-extrabold text-gold">02</span>Annotate</span>
                    <span><span class="mr-1.5 font-extrabold text-gold">03</span>Search</span>
                </div>
            </section>

            {{-- Right: bookshelf illustration --}}
            <section aria-hidden="true" class="relative flex min-h-[420px] flex-1 items-end justify-center overflow-hidden bg-cream px-6 pt-20 pb-16 lg:pb-24">
                <div class="absolute top-10 right-6 hidden w-[170px] rotate-[5deg] rounded border border-[#e2dccb] bg-white p-[18px] sm:block lg:top-[72px] lg:right-[72px]">
                    <div class="mx-auto -mt-[26px] mb-3 h-3.5 w-14 -rotate-3 bg-gold/70"></div>
                    <p class="font-hand text-2xl leading-tight text-forest">Lasso → some weights become 0!<br><span class="text-xl text-[#6b5a1e]">see p. 18</span></p>
                </div>

                <div class="relative flex origin-bottom scale-[0.7] items-end gap-2 border-b-[16px] border-forest-ink px-7 sm:scale-90 xl:scale-100">
                    <div class="flex h-[360px] w-[58px] items-center justify-center rounded-t bg-forest"><span class="rotate-180 text-[15px] font-bold tracking-wide text-gold [writing-mode:vertical-rl]">DS 230 · Statistical Learning</span></div>
                    <div class="flex h-[300px] w-11 items-center justify-center rounded-t bg-[#c9b77a]"><span class="rotate-180 text-sm font-bold text-forest-ink [writing-mode:vertical-rl]">DS 210</span></div>
                    <div class="flex h-[400px] w-[70px] items-center justify-center rounded-t bg-[#2e6b4c]"><span class="rotate-180 text-base font-bold tracking-wide text-cream [writing-mode:vertical-rl]">DS 201 · Foundations</span></div>
                    <div class="flex h-[270px] w-12 items-center justify-center rounded-t bg-[#d9d1b4]"><span class="rotate-180 text-sm font-bold text-forest-ink [writing-mode:vertical-rl]">DS 220</span></div>
                    <div class="flex h-[330px] w-[54px] items-center justify-center rounded-t bg-forest-dark"><span class="rotate-180 text-sm font-bold text-gold [writing-mode:vertical-rl]">DS 299 · Seminar</span></div>
                    <div class="ml-3.5 h-[300px] w-[62px] origin-bottom-left -rotate-10 rounded-t bg-[#8a7a45]"></div>
                    <div class="absolute -right-[86px] bottom-0 flex w-[70px] flex-col items-center">
                        <div class="flex items-end gap-0.5">
                            <div class="h-[54px] w-[22px] -rotate-20 rounded-tl-[22px] rounded-br-[22px] bg-[#2e6b4c]"></div>
                            <div class="h-[70px] w-[22px] rounded-tr-[22px] rounded-bl-[22px] bg-forest"></div>
                            <div class="h-[50px] w-[22px] rotate-[18deg] rounded-tl-[22px] rounded-br-[22px] bg-[#2e6b4c]"></div>
                        </div>
                        <div class="h-[58px] w-16 rounded-t rounded-b-[14px] bg-[#c56b45]"></div>
                    </div>
                </div>
            </section>
        </div>
    </body>
</html>
