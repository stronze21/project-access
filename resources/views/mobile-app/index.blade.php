@extends('layouts.mobile-app')

@section('content')
    <section class="relative overflow-hidden bg-white dark:bg-slate-950">
        <div class="absolute inset-x-0 bottom-0 h-28 bg-[#f5f8fb] dark:bg-slate-900"></div>
        <div class="relative mx-auto grid min-h-[calc(100vh-5rem)] max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:py-14">
            <div class="max-w-2xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#1fa99a]/20 bg-[#e7faf6] px-3 py-1 text-sm font-semibold text-[#0c7464] dark:border-teal-300/25 dark:bg-teal-300/10 dark:text-teal-200">
                    <span class="h-2 w-2 rounded-full bg-[#0f9f84]"></span>
                    Resident services for <span data-device-platform-label>your device</span>
                </div>

                <h1 class="text-4xl font-extrabold leading-tight text-slate-950 dark:text-white sm:text-5xl lg:text-6xl">{{ $release['name'] }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-700 dark:text-slate-200">{{ $release['description'] }}</p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @if ($release['has_apk'])
                            <a href="{{ route('mobile-app.download') }}" data-device-platforms="android" class="inline-flex items-center justify-center rounded-md bg-[#0f9f84] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#0b826d]">
                                <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                                Download Latest APK
                            </a>
                        @else
                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-md bg-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                APK Coming Soon
                            </button>
                        @endif

                        <button
                            type="button"
                            id="resident-portal-install"
                            data-portal-url="{{ url('/resident-portal') }}"
                            data-device-platforms="ios,android"
                            class="inline-flex items-center justify-center rounded-md bg-[#23689b] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1b527a] focus:outline-none focus:ring-2 focus:ring-[#23689b] focus:ring-offset-2 dark:focus:ring-offset-slate-950"
                        >
                            <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="1.5" y="1.5" width="21" height="21" rx="5" fill="#0A84FF" stroke="white" stroke-opacity=".45" />
                                <path d="M8.1 16.8 13.6 7.2M10.15 13.25h7.15M6.7 13.25h1.8M15.05 9.7l3 5.2" stroke="white" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span data-install-label>Install Resident Portal</span>
                        </button>

                        <div class="text-sm font-medium text-slate-600 dark:text-slate-200">
                            Version {{ $release['version_name'] }}
                            <span class="text-slate-400">|</span>
                            Build {{ $release['version_code'] }}
                            @if ($release['apk_size_label'])
                                <span class="text-slate-400">|</span>
                                {{ $release['apk_size_label'] }}
                            @endif
                        </div>
                </div>

                <div class="mt-10 grid max-w-xl grid-cols-3 gap-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-2xl font-bold text-[#0c7464]">{{ count($release['features']) }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-300">Core modules</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-2xl font-bold text-[#23689b] dark:text-sky-300">APK</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-300">Android install</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-2xl font-bold text-[#0c7464]">24/7</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-300">Access ready</p>
                    </div>
                </div>
            </div>

            <div class="relative flex justify-center lg:justify-end">
                <div class="absolute right-4 top-6 hidden h-44 w-44 rounded-full bg-[#1fa99a]/10 lg:block"></div>
                <div class="absolute bottom-10 left-8 hidden h-32 w-32 rounded-full bg-[#23689b]/10 lg:block"></div>

                <div class="relative w-full max-w-[23rem]">
                    <div class="mx-auto aspect-[9/18.5] w-full rounded-[2.5rem] border-[12px] border-[#172338] bg-[#0e1728] p-2 shadow-2xl shadow-slate-900/25">
                        <div class="relative h-full overflow-hidden rounded-[1.75rem] bg-[#f4f8fb] dark:bg-slate-950">
                            <div class="absolute left-1/2 top-3 z-10 h-1.5 w-16 -translate-x-1/2 rounded-full bg-white/80"></div>
                            <div class="bg-[#23689b] px-5 pb-6 pt-10 text-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white">
                                        <img src="{{ asset('logo1.png') }}" alt="Access logo" class="h-9 w-9 object-contain">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-wide text-white/75">ProjectAccess</p>
                                        <p class="truncate text-lg font-bold">Resident Services</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-slate-900">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-bold text-slate-950 dark:text-white">Announcements</span>
                                        <span class="rounded-full bg-[#f7c476]/25 px-2.5 py-1 text-xs font-bold text-[#8a5b13] dark:bg-amber-300/20 dark:text-amber-200">New</span>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-200">Verified city updates and notices.</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-[#e7faf6] p-4 dark:bg-teal-300/10">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#0f9f84] text-white">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2Z" />
                                            </svg>
                                        </div>
                                        <p class="mt-4 text-xs font-bold text-[#075c50] dark:text-teal-100">Requests</p>
                                    </div>
                                    <div class="rounded-lg bg-[#eaf3fb] p-4 dark:bg-sky-300/10">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#23689b] text-white">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8M5 5h14v14H5z" />
                                            </svg>
                                        </div>
                                        <p class="mt-4 text-xs font-bold text-[#17486d] dark:text-sky-100">Programs</p>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-slate-900">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-200">Service tracking</span>
                                        <span class="h-2 w-2 rounded-full bg-[#0f9f84]"></span>
                                    </div>
                                    <div class="h-2 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
                                    <div class="mt-2 h-2 w-4/5 rounded bg-slate-200 dark:bg-slate-700"></div>
                                    <div class="mt-2 h-2 w-2/3 rounded bg-slate-200 dark:bg-slate-700"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 lg:col-span-2">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-[#0c7464] dark:text-teal-200">Built for residents</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">Features</h2>
                </div>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($release['features'] as $feature)
                    <div class="flex min-h-24 gap-3 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/70">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#d9f8ef] text-[#0c7464] dark:bg-teal-300/15 dark:text-teal-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m5 13 4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium leading-6 text-slate-700 dark:text-slate-200">{{ $feature }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm font-semibold uppercase tracking-wide text-[#23689b] dark:text-sky-300">Download info</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">Latest Release</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-300">Version</dt>
                    <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $release['version_name'] }} ({{ $release['version_code'] }})</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-300">Uploaded</dt>
                    <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $release['apk_uploaded_at'] ?: 'Not yet uploaded' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-300">Release Notes</dt>
                    <dd class="mt-1 leading-6 text-slate-700 dark:text-slate-200">{{ $release['release_notes'] }}</dd>
                </div>
            </dl>

            @if ($release['has_apk'])
                <a href="{{ route('mobile-app.download') }}" data-device-platforms="android" class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-[#0f9f84] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#0b826d]">
                    Download APK
                </a>
            @endif
        </aside>
    </section>

    <dialog id="resident-portal-install-dialog" class="w-[min(92vw,30rem)] rounded-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 dark:bg-slate-900 dark:text-white">
        <div class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-[#23689b] dark:text-sky-300">Resident Portal</p>
                    <h2 class="mt-1 text-2xl font-bold">Add ACCESS to your Home Screen</h2>
                </div>
                <button type="button" data-install-dialog-close class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Close install instructions">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </div>
            <div data-install-message class="mt-5 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-200"></div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" data-install-dialog-close class="rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-600">Close</button>
                <a href="{{ url('/resident-portal') }}" class="rounded-md bg-[#23689b] px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#1b527a]">Open Resident Portal</a>
            </div>
        </div>
    </dialog>
@endsection
