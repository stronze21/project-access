<div id="complaint-image-lightbox"
     class="fixed inset-0 z-[120] hidden items-center justify-center p-4 sm:p-8"
     aria-hidden="true">
    <div class="absolute inset-0 bg-black/80" data-lightbox-close="1"></div>

    <div class="relative z-10 w-full max-w-5xl">
        <button type="button"
                data-lightbox-close="1"
                class="mb-2 inline-flex items-center rounded-lg bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-white">
            Close
        </button>
        <div class="overflow-hidden rounded-xl bg-black shadow-2xl">
            <img id="complaint-image-lightbox-img"
                 src=""
                 alt="Complaint photo preview"
                 class="max-h-[80vh] w-full object-contain">
        </div>
    </div>
</div>

@once
    <script>
        (() => {
            const lightbox = document.getElementById('complaint-image-lightbox');
            const image = document.getElementById('complaint-image-lightbox-img');
            if (!lightbox || !image || lightbox.dataset.bound === '1') {
                return;
            }

            lightbox.dataset.bound = '1';

            const openLightbox = (src, altText = 'Complaint photo preview') => {
                image.src = src;
                image.alt = altText;
                lightbox.classList.remove('hidden');
                lightbox.classList.add('flex');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            const closeLightbox = () => {
                lightbox.classList.add('hidden');
                lightbox.classList.remove('flex');
                lightbox.setAttribute('aria-hidden', 'true');
                image.removeAttribute('src');
                document.body.classList.remove('overflow-hidden');
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-complaint-lightbox-src]');
                if (trigger) {
                    event.preventDefault();
                    openLightbox(
                        trigger.getAttribute('data-complaint-lightbox-src'),
                        trigger.getAttribute('data-complaint-lightbox-alt') || 'Complaint photo preview'
                    );
                    return;
                }

                if (event.target.closest('[data-lightbox-close="1"]')) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !lightbox.classList.contains('hidden')) {
                    closeLightbox();
                }
            });
        })();
    </script>
@endonce
