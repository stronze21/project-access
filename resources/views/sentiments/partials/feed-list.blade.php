<div class="space-y-4">
    @forelse ($posts as $post)
        @include('sentiments.partials.post-card', ['post' => $post])
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <p class="text-base font-semibold text-slate-800">No posts found</p>
            <p class="mt-1 text-sm text-slate-500">Try a different search or filter.</p>
        </div>
    @endforelse

    <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
        {{ $posts->links() }}
    </div>
</div>

