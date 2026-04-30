<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Support Requests
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Resident</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            @forelse ($requests as $request)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $request->reference_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900">{{ $request->resident_name ?: $request->resident?->full_name ?: 'Unknown resident' }}</div>
                                        <div class="text-xs text-slate-500">{{ $request->email ?: 'No email' }}</div>
                                        <div class="text-xs text-slate-500">{{ $request->contact_number ?: 'No contact number' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ str($request->category)->replace('-', ' ')->title() }}</td>
                                    <td class="min-w-72 px-4 py-3">
                                        <div class="font-medium text-slate-900">{{ $request->subject }}</div>
                                        <div class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $request->message }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ str($request->status)->replace('-', ' ')->title() }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $request->submitted_at?->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('support-requests.update', $request) }}" class="flex min-w-[24rem] flex-col gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex gap-2">
                                                <select name="status" class="w-36 rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    @foreach (['received', 'reviewing', 'resolved', 'closed'] as $status)
                                                        <option value="{{ $status }}" @selected($request->status === $status)>{{ str($status)->title() }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">Update</button>
                                            </div>
                                            <textarea name="admin_notes" rows="2" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Internal notes">{{ $request->admin_notes }}</textarea>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">No support requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
