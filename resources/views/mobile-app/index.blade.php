@extends('layouts.public')

@section('content')
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="flex flex-col justify-center px-5 py-8 sm:px-8 lg:px-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Android app</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950 sm:text-4xl">{{ $release['name'] }}</h1>
                    <p class="mt-4 text-base leading-7 text-slate-700">{{ $release['description'] }}</p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @if ($release['has_apk'])
                            <a href="{{ route('mobile-app.download') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                                <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                                Download Latest APK
                            </a>
                        @else
                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-md bg-slate-300 px-5 py-3 text-sm font-semibold text-slate-600">
                                APK Coming Soon
                            </button>
                        @endif

                        <div class="text-sm text-slate-600">
                            Version {{ $release['version_name'] }}
                            <span class="text-slate-400">|</span>
                            Build {{ $release['version_code'] }}
                            @if ($release['apk_size_label'])
                                <span class="text-slate-400">|</span>
                                {{ $release['apk_size_label'] }}
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-slate-900 px-5 py-8 sm:px-8 lg:px-10">
                <div class="mx-auto w-full max-w-xs">
                    <div class="rounded-[2rem] border-[10px] border-slate-800 bg-slate-950 p-2 shadow-2xl">
                        <div class="overflow-hidden rounded-[1.45rem] bg-slate-100">
                            <div class="bg-blue-700 px-4 py-5 text-white">
                                <div class="mx-auto mb-4 h-1 w-16 rounded-full bg-white/70"></div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-100">ProjectAccess</p>
                                <h2 class="mt-2 text-xl font-bold leading-tight">Resident Services</h2>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="rounded-lg bg-white p-3 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-slate-900">Announcements</span>
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">New</span>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-600">Read verified updates from local offices.</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-emerald-50 p-3">
                                        <div class="h-8 w-8 rounded-md bg-emerald-600"></div>
                                        <p class="mt-3 text-xs font-semibold text-emerald-900">Requests</p>
                                    </div>
                                    <div class="rounded-lg bg-blue-50 p-3">
                                        <div class="h-8 w-8 rounded-md bg-blue-600"></div>
                                        <p class="mt-3 text-xs font-semibold text-blue-900">Programs</p>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-white p-3 shadow-sm">
                                    <div class="h-2 w-24 rounded bg-slate-200"></div>
                                    <div class="mt-3 h-2 w-full rounded bg-slate-200"></div>
                                    <div class="mt-2 h-2 w-2/3 rounded bg-slate-200"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-xl font-semibold text-slate-950">Features</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($release['features'] as $feature)
                    <div class="flex gap-3 rounded-lg border border-slate-200 p-4">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-sm leading-6 text-slate-700">{{ $feature }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-950">Latest Release</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-slate-500">Version</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $release['version_name'] }} ({{ $release['version_code'] }})</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Uploaded</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $release['apk_uploaded_at'] ?: 'Not yet uploaded' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Release Notes</dt>
                    <dd class="mt-1 leading-6 text-slate-700">{{ $release['release_notes'] }}</dd>
                </div>
            </dl>
        </aside>
    </div>
@endsection
