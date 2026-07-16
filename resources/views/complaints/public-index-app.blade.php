<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-base-content/90 leading-tight">
            Public Complaints
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8">
            @include('complaints.partials.public-index-content')
        </div>
    </div>
</x-app-layout>
