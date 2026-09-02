<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CivicBridge Brochure Preview</title>
    <meta name="description" content="1080p brochure preview for screenshots and presentation.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #0f172a;
            --slate: #334155;
            --teal: #0f9f84;
            --sky: #23689b;
            --bg: #eef5f8;
        }
        body {
            background:
                radial-gradient(circle at top left, rgba(35,104,155,.12), transparent 26%),
                radial-gradient(circle at 85% 15%, rgba(15,159,132,.12), transparent 24%),
                linear-gradient(180deg, #f7fbfd 0%, var(--bg) 100%);
        }
        .glass {
            backdrop-filter: blur(18px);
            background: rgba(255,255,255,.78);
        }
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased">
    @php
        $metrics = [
            ['label' => 'Core modules', 'value' => '20+'],
            ['label' => 'Mobile support', 'value' => 'Offline-ready'],
            ['label' => 'Audience', 'value' => 'Cities / Municipalities'],
        ];
        $featureBlocks = [
            ['title' => 'Resident data', 'text' => 'Profiles, households, identity cards, QR codes, photos, and signatures.'],
            ['title' => 'Ayuda operations', 'text' => 'Eligibility, releases, batches, verification, and tracking.'],
            ['title' => 'Civic services', 'text' => 'Announcements, public services, grievances, emergencies, support, and scholarships.'],
            ['title' => 'Mobile app', 'text' => 'Digital ID, notifications, profile self-service, and offline access.'],
        ];
    @endphp

    <main class="mx-auto flex min-h-screen max-w-[1920px] items-center justify-center px-8 py-8">
        <section class="glass w-full overflow-hidden rounded-[2rem] border border-white/60 shadow-[0_35px_90px_rgba(15,23,42,.16)]">
            <div class="grid min-h-[1080px] grid-rows-[auto_1fr_auto]">
                <header class="border-b border-slate-200/70 px-12 py-8">
                    <div class="flex items-center justify-between gap-8">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.4em] text-teal-700">Brochure Preview</p>
                            <h1 class="mt-3 text-5xl font-black tracking-tight text-[var(--ink)]">CivicBridge</h1>
                            <p class="mt-3 max-w-3xl text-lg leading-8 text-[var(--slate)]">
                                A modern, city-neutral resident services platform for ayuda distribution, digital ID, and mobile self-service.
                            </p>
                        </div>
                        <div class="rounded-3xl bg-slate-950 px-6 py-4 text-white shadow-lg">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-teal-200">Presentation route</p>
                            <p class="mt-2 text-2xl font-bold">1080p ready</p>
                            <p class="text-sm text-slate-300">Use browser screenshots at 1920 x 1080</p>
                        </div>
                    </div>
                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                        @foreach ($metrics as $metric)
                            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                                <p class="mt-1 text-2xl font-extrabold text-slate-950">{{ $metric['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </header>

                <div class="grid gap-0 lg:grid-cols-[1.02fr_0.98fr]">
                    <section class="border-b border-slate-200/70 px-12 py-10 lg:border-b-0 lg:border-r">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-700">What it includes</p>
                                <h2 class="mt-2 text-3xl font-bold text-[var(--ink)]">Designed to present the full platform clearly</h2>
                            </div>
                            <div class="rounded-2xl bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">Generic for any LGU</div>
                        </div>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            @foreach ($featureBlocks as $block)
                                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="h-3 w-3 rounded-full bg-teal-500"></span>
                                        <h3 class="text-xl font-bold text-slate-950">{{ $block['title'] }}</h3>
                                    </div>
                                    <p class="mt-4 text-base leading-7 text-slate-600">{{ $block['text'] }}</p>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-8 rounded-[1.75rem] bg-slate-950 p-8 text-white shadow-2xl">
                            <p class="text-sm font-bold uppercase tracking-[0.3em] text-teal-200">Key message</p>
                            <p class="mt-4 text-2xl font-semibold leading-10">
                                One platform for staff operations, one mobile experience for residents, and one adaptable story for city or municipal presentations.
                            </p>
                        </div>
                    </section>

                    <aside class="bg-[linear-gradient(180deg,#f8fcfe_0%,#edf7f7_100%)] px-12 py-10">
                        <p class="text-sm font-bold uppercase tracking-[0.28em] text-teal-700">Mobile app spotlight</p>
                        <h2 class="mt-2 text-3xl font-bold text-[var(--ink)]">Resident experience that looks great in screenshots</h2>
                        <div class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl">
                            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-sky-900 px-6 py-5 text-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/12 text-xl font-black">C</div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-200">CivicBridge Mobile</p>
                                        <p class="text-lg font-bold">Resident Dashboard</p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid gap-4 p-6 sm:grid-cols-2">
                                <div class="rounded-2xl bg-sky-50 p-4">
                                    <p class="text-sm font-semibold text-sky-800">Digital ID</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Flip card, QR, photo, and resident details.</p>
                                </div>
                                <div class="rounded-2xl bg-teal-50 p-4">
                                    <p class="text-sm font-semibold text-teal-800">Ayuda</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Track status, history, and upcoming distributions.</p>
                                </div>
                                <div class="rounded-2xl bg-slate-100 p-4">
                                    <p class="text-sm font-semibold text-slate-800">Updates</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Announcements, notifications, and service alerts.</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-4">
                                    <p class="text-sm font-semibold text-emerald-800">Offline ready</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Cached data and sync support when connectivity returns.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-700">Screenshot route</p>
                            <p class="mt-3 text-base leading-7 text-slate-600">
                                Open this page in a browser at 1920 x 1080 and capture it as a full-screen presentation image.
                            </p>
                            <p class="mt-3 rounded-2xl bg-slate-950 px-4 py-3 font-mono text-sm text-teal-200">
                                /brochure/1080p
                            </p>
                        </div>
                    </aside>
                </div>

                <footer class="border-t border-slate-200/70 px-12 py-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-500">CivicBridge brochure preview for screenshots, demos, and presentations.</p>
                        <p class="text-sm font-semibold text-slate-700">Use the route above for 1080p captures.</p>
                    </div>
                </footer>
            </div>
        </section>
    </main>
</body>
</html>
