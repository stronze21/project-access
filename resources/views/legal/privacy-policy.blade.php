<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <div class="mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7 border-b border-slate-200 pb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Privacy Notice / Paunawa sa Privacy</h1>
                <p class="mt-2 text-sm text-slate-600">Effective and last updated: July 16, 2026 / May bisa at huling binago: Hulyo 16, 2026</p>
                <p class="mt-4 text-sm leading-6 text-slate-700">This notice explains how personal data is handled in the ProjectAccess web and mobile applications. It should be read together with the <a href="{{ route('legal.terms') }}" class="font-semibold text-indigo-700 underline">Terms of Use</a>.</p>
            </div>

            <div class="space-y-8 text-sm leading-6 text-slate-700">
                <section lang="en" aria-labelledby="privacy-en">
                    <h2 id="privacy-en" class="text-xl font-bold text-slate-900">English</h2>

                    <div class="mt-5 space-y-5">
                        <section>
                            <h3 class="text-base font-semibold text-slate-900">1. Personal Information Controller</h3>
                            <p class="mt-2">ProjectAccess and the authorized barangay and City Government offices that provide services through the platform process resident information for local public-service functions. Questions or requests may be submitted through the <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700 underline">Support page</a> or to the appropriate barangay or city office.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">2. Personal Data We Process</h3>
                            <p class="mt-2">Depending on the service used, we may process resident identifiers; name, birth date, contact and address details; profile and household information; eligibility and aid-distribution records; service requests and status history; grievance or complaint reports; emergency/SOS location and details; uploaded photos, documents, or signatures; device notification tokens; and security, usage, or diagnostic information.</p>
                            <p class="mt-2">For mobile account activation, Resident ID, last name, and birth date are used to match an existing resident record. The selected six-digit MPIN is protected using one-way hashing rather than stored as readable text.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">3. Purposes and Lawful Bases</h3>
                            <p class="mt-2">We process only data reasonably needed to verify identity; activate, authenticate, and protect accounts; deliver requested barangay or city services; administer aid and eligibility programs; process reports, grievances, and emergency requests; send service, safety, and emergency notifications; maintain accurate official and audit records; prevent fraud or misuse; improve reliability; and comply with legal or public-authority obligations.</p>
                            <p class="mt-2">Depending on the activity, processing may rely on consent, performance of a requested service, a legal obligation, the constitutional or statutory mandate of a public authority, protection of vital interests, or legitimate interests that do not override the resident's fundamental rights. Consent is not presented as the sole basis where another basis applies.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">4. Sharing and Disclosure</h3>
                            <p class="mt-2">Personal data may be accessed by authorized barangay or city personnel and by contracted hosting, security, communications, or technology providers only when necessary for the purposes above and subject to confidentiality and security controls. Data may also be disclosed when required by law, a lawful order, an emergency, or an authorized inter-agency public-service arrangement. We do not sell resident personal data.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">5. Retention, Disposal, and Security</h3>
                            <p class="mt-2">Records are retained only for the applicable operational, audit, archival, and legal periods. When retention is no longer necessary and deletion is legally permitted, records are securely deleted, anonymized, or disposed of. ProjectAccess uses access controls, authenticated sessions, protected credentials, secure API tokens, role-based permissions, logging, and limited staff access. No system is completely risk-free; suspected unauthorized access should be reported promptly through Support.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">6. Your Data-Privacy Rights</h3>
                            <p class="mt-2">Subject to the Data Privacy Act of 2012 and other applicable law, you may exercise the rights to be informed, object, access, rectify, erase or block, obtain data portability where applicable, claim damages, and file a complaint with the National Privacy Commission. You may also withdraw consent for processing that relies on consent. Withdrawal does not affect processing already lawfully completed and does not require deletion of official records that must be retained by law or for a valid public-service purpose.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">7. Account and Data Requests</h3>
                            <p class="mt-2">You may request correction or privacy assistance through Support. You may request deletion review through the in-app Settings screen or the <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700 underline">Account Deletion page</a>. Requests may require identity verification. Some transaction, audit, emergency, fraud-prevention, archival, or other official records may be retained when required or permitted by law.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">8. Notice and Consent During Activation</h3>
                            <p class="mt-2">Before a mobile resident account is activated, the app presents this notice in English and Filipino. The acknowledgment and consent controls remain disabled until the notice has been scrolled to the end. Activation proceeds only after the user separately confirms that the notice was read and accepts the Terms of Use and consent-based processing described in the notice.</p>
                        </section>
                    </div>
                </section>

                <hr class="border-slate-200">

                <section lang="fil" aria-labelledby="privacy-fil">
                    <h2 id="privacy-fil" class="text-xl font-bold text-slate-900">Filipino</h2>

                    <div class="mt-5 space-y-5">
                        <section>
                            <h3 class="text-base font-semibold text-slate-900">1. Tagapamahala ng Personal na Impormasyon</h3>
                            <p class="mt-2">Ang ProjectAccess at ang mga awtorisadong tanggapan ng barangay at Pamahalaang Lungsod na nagbibigay ng serbisyo sa platform ay nagpoproseso ng impormasyon ng residente para sa mga tungkuling pampublikong serbisyo. Ang tanong o kahilingan ay maaaring ipadala sa <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700 underline">Support page</a> o sa kaukulang tanggapan ng barangay o lungsod.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">2. Personal na Datos na Pinoproseso</h3>
                            <p class="mt-2">Depende sa serbisyong ginagamit, maaari naming iproseso ang resident identifier; pangalan, petsa ng kapanganakan, contact at address details; profile at household information; eligibility at tala ng ayuda; service request at status history; reklamo; lokasyon at detalye ng emergency/SOS; in-upload na larawan, dokumento, o lagda; device notification token; at security, usage, o diagnostic information.</p>
                            <p class="mt-2">Sa mobile account activation, ginagamit ang Resident ID, apelyido, at petsa ng kapanganakan upang maitugma ang dati nang resident record. Ang anim-na-digit na MPIN ay pinoprotektahan sa pamamagitan ng one-way hashing at hindi itinatago bilang nababasang teksto.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">3. Mga Layunin at Legal na Batayan</h3>
                            <p class="mt-2">Pinoproseso lamang ang datos na makatwirang kailangan upang mapatunayan ang pagkakakilanlan; ma-activate, ma-authenticate, at maprotektahan ang account; maibigay ang hiniling na serbisyo ng barangay o lungsod; mapangasiwaan ang ayuda at eligibility program; maproseso ang report, reklamo, at emergency request; makapagpadala ng service, safety, at emergency notification; mapanatili ang tumpak na opisyal at audit record; maiwasan ang pandaraya o maling paggamit; mapahusay ang serbisyo; at matupad ang obligasyong legal o pampamahalaan.</p>
                            <p class="mt-2">Depende sa gawain, maaaring ibatay ang processing sa pahintulot, pagbibigay ng hiniling na serbisyo, legal na obligasyon, konstitusyonal o statutory mandate ng pampublikong awtoridad, proteksiyon ng buhay at kaligtasan, o lehitimong interes na hindi nangingibabaw sa pangunahing karapatan ng residente. Hindi itinuturing na tanging batayan ang consent kung may ibang naaangkop na legal na batayan.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">4. Pagbabahagi at Paglalantad</h3>
                            <p class="mt-2">Maaaring ma-access ang personal na datos ng awtorisadong kawani ng barangay o lungsod at ng kinontratang hosting, security, communications, o technology provider kung kailangan lamang para sa mga layunin sa itaas at may confidentiality at security controls. Maaari ring ibahagi ang datos kung hinihingi ng batas, lawful order, emergency, o awtorisadong inter-agency public-service arrangement. Hindi namin ibinebenta ang personal na datos ng residente.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">5. Retention, Pagtatapon, at Seguridad</h3>
                            <p class="mt-2">Itinatago lamang ang rekord ayon sa naaangkop na operational, audit, archival, at legal na panahon. Kapag hindi na kailangan at pinahihintulutan na ng batas ang deletion, ligtas itong binubura, ginagawang anonymous, o itinatapon. Gumagamit ang ProjectAccess ng access controls, authenticated sessions, protected credentials, secure API tokens, role-based permissions, logging, at limitadong staff access. Walang sistemang ganap na walang panganib; agad na i-report sa Support ang pinaghihinalaang unauthorized access.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">6. Iyong mga Karapatan sa Data Privacy</h3>
                            <p class="mt-2">Alinsunod sa Data Privacy Act of 2012 at iba pang naaangkop na batas, maaari mong gamitin ang karapatang mabigyan ng impormasyon, tumutol, ma-access, maitama, mabura o ma-block ang datos, data portability kung naaangkop, humingi ng danyos, at magsampa ng reklamo sa National Privacy Commission. Maaari mo ring bawiin ang pahintulot para sa processing na nakabatay sa consent. Hindi nito pinapawalang-bisa ang processing na dati nang legal na ginawa at hindi nito inaatasang burahin ang opisyal na rekord na kailangang panatilihin ng batas o ng wastong layuning pampublikong serbisyo.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">7. Mga Kahilingan Tungkol sa Account at Datos</h3>
                            <p class="mt-2">Maaaring humiling ng pagwawasto o privacy assistance sa Support. Ang deletion review ay maaaring hilingin sa Settings ng app o sa <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700 underline">Account Deletion page</a>. Maaaring kailanganin ang identity verification. Ang ilang transaction, audit, emergency, fraud-prevention, archival, o iba pang opisyal na rekord ay maaaring panatilihin kung hinihingi o pinahihintulutan ng batas.</p>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-slate-900">8. Paunawa at Pahintulot sa Activation</h3>
                            <p class="mt-2">Bago ma-activate ang mobile resident account, ipinapakita ng app ang paunawang ito sa English at Filipino. Naka-disable ang acknowledgment at consent controls hanggang ma-scroll ang paunawa sa pinakadulo. Magpapatuloy lamang ang activation matapos hiwalay na kumpirmahin ng user na nabasa ang notice at tinatanggap ang Terms of Use at ang consent-based processing na inilarawan dito.</p>
                        </section>
                    </div>
                </section>
            </div>

            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6 text-sm">
                <a href="{{ route('legal.terms') }}" class="font-semibold text-indigo-700">Terms of Use</a>
                <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700">Support</a>
                <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700">Account deletion</a>
            </div>
        </div>
    </div>
</x-guest-layout>
