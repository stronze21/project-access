<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-base-content/90 leading-tight">
                Submit Anonymous Complaint
            </h2>
            <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 badge badge-sm">
                Anonymous Mode
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8">
            @include('complaints.partials.create-anonymous-content')
        </div>
    </div>
</x-app-layout>
