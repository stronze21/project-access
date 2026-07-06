<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Public Complaint Detail
            </h2>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                {{ auth()->user()->role }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @include('complaints.partials.public-show-content')
        </div>
    </div>
</x-app-layout>
