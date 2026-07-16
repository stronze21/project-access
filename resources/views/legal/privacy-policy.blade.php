<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <article class="mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <header class="mb-7 border-b border-slate-200 pb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Privacy Notice and Consent</h1>
            </header>

            <div class="space-y-8 text-sm leading-7 text-slate-700">
                <section lang="en" aria-labelledby="privacy-notice-en">
                    <h2 id="privacy-notice-en" class="text-xl font-bold text-slate-900">English</h2>
                    <div class="mt-5 space-y-5">
                        <p>The City Government of Alaminos, Pangasinan is a branch of government that administers its jurisdiction and represents its community. As a government, we aim to serve our citizens through health, public safety, education, and other programs that fulfill and comply with the rules and standards of RA 7160, or the Local Government Code. For this purpose, we need to collect and record details about your family that contain personal and sensitive information so that we can better understand the circumstances of every family and of our beloved City.</p>

                        <p>As the <strong>personal information controller</strong>, we assure you that your data or information will be processed in accordance with the law and only for the following purposes:</p>

                        <ul class="list-disc space-y-2 pl-6">
                            <li>Providing health, public safety, education, and other programs that fulfill and comply with the rules and standards of RA 7160, or the Local Government Code; and</li>
                            <li>Collecting and processing relevant data to improve our services.</li>
                        </ul>

                        <p>When it is necessary to share your personal data or information with another government agency or private organization, we will ensure that such sharing complies with the provisions of the Data Privacy Act of 2012.</p>

                        <p>As the <strong>data subject, or the owner of the personal and sensitive personal information</strong>, you have the rights provided under the Data Privacy Act of 2012.</p>

                        <p>Your agreement also signifies your consent for us to include and use your data in the Barangay Health Worker Information System (BHWIS) for the Alaminos Smart City Program: Project ACCESS (Alaminos City Citizens' E-Services Solutions).</p>
                    </div>
                </section>

                <hr class="border-slate-200">

                <section lang="fil" aria-labelledby="privacy-notice-fil">
                    <h2 id="privacy-notice-fil" class="text-xl font-bold text-slate-900">Filipino</h2>
                    <div class="mt-5 space-y-5">
                <p>Ang Pamahalaang Lungsod ng Alaminos, Pangasinan ay isang sangay ng pamahalaan na tagapangasiwa sa kanyang nasasakupan at kumakatawan sa kanyang pamayanan. Bilang isang pamahalaan, adhikain naming mapaglingkuran ang aming mga mamamayan sa pamamagitan ng serbisyong pangkalusugan, pangkapayapaan, pang-edukasyon at iba pang programa na tumutupad at sumasang-ayon sa mga alituntunin at pamantayan ng RA 7160 o Local Government Code. Dahil dito, kinakailangan naming makuha at mai-rekord ang detalye ng inyong pamilya na naglalaman ng mga personal at sensitibong datos o impormasyon upang lalo naming maintindihan ang sitwasyon ng bawat pamilya at ng ating minamahal na Lungsod.</p>

                <p>Bilang <strong>personal information controller</strong>, sinisiguro namin na ang pagproseso ng inyong mga datos o impormasyon ay naaayon sa batas at sa mga sumusunod na kadahilanan lamang:</p>

                <ul class="list-disc space-y-2 pl-6">
                    <li>Pagbigay ng mga serbisyong pangkalusugan, pangkapayapaan, pang-edukasyon at iba pang programa na tumutupad at sumasang-ayon sa mga alituntunin at pamantayan ng RA 7160 o Local Government Code; at</li>
                    <li>Paglikom at pagproseso ng mga kaukulang datos para mapabuti ang aming mga serbisyo.</li>
                </ul>

                <p>Sa mga pagkakataong kinakailangan naming ibahagi ang inyong personal na datos o impormasyon sa ibang ahensiya ng Gobyerno o pribadong organisasyon, amin pong sisiguraduhin na ang pagbabahagi ay naaayon lamang sa mga probisyon ng Data Privacy Act of 2012.</p>

                <p>Bilang <strong>data subject o nagmamay-ari ng personal at sensitive personal information</strong>, ikaw ay may mga karapatang nakapaloob sa Data Privacy Act of 2012.</p>

                <p>Ang inyong pagsang-ayon ay nagpapahiwatig rin ng inyong pahintulot sa amin na ipaloob at gamitin ang inyong mga datos sa Barangay Health Worker Information System (BHWIS) para sa Alaminos Smart City Program: Project ACCESS (Alaminos City Citizens' E-Services Solutions).</p>
                    </div>
                </section>

                <section aria-labelledby="consent-options" class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                    <h2 id="consent-options" class="text-base font-bold text-slate-900">Consent / Pahintulot</h2>
                    <div class="mt-3 space-y-2 font-semibold text-slate-800">
                        <p>&#9633; I agree / Sumasang-ayon</p>
                        <p>&#9633; I do not agree / Di Sumasang-ayon</p>
                    </div>
                </section>
            </div>

            <nav aria-label="Legal and support links" class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6 text-sm">
                <a href="{{ route('legal.terms') }}" class="font-semibold text-indigo-700">Terms of Use</a>
                <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700">Support</a>
                <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700">Account deletion</a>
            </nav>
        </article>
    </div>
</x-guest-layout>
