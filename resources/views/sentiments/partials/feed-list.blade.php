<div class="space-y-4">
    @forelse ($posts as $post)
        @include('sentiments.partials.post-card', ['post' => $post])
    @empty
        <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 p-10 text-center shadow-sm card">
            <p class="text-base font-semibold text-base-content">No posts found</p>
            <p class="mt-1 text-sm text-base-content/60">Try a different search or filter.</p>
        </div>
    @endforelse

    <div class="rounded-xl bg-base-100 px-4 py-3 shadow-sm">
        {{ $posts->links() }}
    </div>
</div>

