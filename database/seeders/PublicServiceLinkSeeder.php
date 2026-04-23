<?php

namespace Database\Seeders;

use App\Models\PublicServiceLink;
use Illuminate\Database\Seeder;

class PublicServiceLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $links = [
            [
                'title' => 'Business Permit Application',
                'slug' => 'business-permit-and-licensing',
                'service_type' => 'business-permit',
                'description' => 'Apply for a new business permit or submit your business permit application through the integrated online form.',
                'url' => 'https://docs.google.com/forms/d/e/1FAIpQLSeP2vTENyU9xUfY-O8iFwckru-g28LKYtNWnVxwdh_kMey3_g/viewform?fbzx=6914469997176896678',
                'icon' => 'briefcase',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Civil Registry Documents',
                'slug' => 'civil-registry-documents',
                'service_type' => 'civil-registry',
                'description' => 'Request birth, marriage, and death record assistance through the city civil registry portal.',
                'url' => 'https://civilregistry.alaminoscity.gov.ph',
                'icon' => 'document-text',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Local Tax Payments',
                'slug' => 'local-tax-payments',
                'service_type' => 'tax-payment',
                'description' => 'Pay real property taxes, local fees, and permit-related assessments online.',
                'url' => 'https://taxes.alaminoscity.gov.ph',
                'icon' => 'banknotes',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Financial Aid and Social Support',
                'slug' => 'financial-aid-and-social-support',
                'service_type' => 'financial-aid',
                'description' => 'Browse assistance programs and linked online application channels for social welfare requests.',
                'url' => 'https://socialservices.alaminoscity.gov.ph',
                'icon' => 'heart',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'title' => 'Alaminos City Website',
                'slug' => 'alaminos-city-website',
                'service_type' => 'general',
                'description' => 'Visit the official Alaminos City website for city news, services, announcements, and government information.',
                'url' => 'https://www.alaminoscity.gov.ph/index.html',
                'icon' => 'globe-alt',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($links as $link) {
            PublicServiceLink::updateOrCreate(
                ['slug' => $link['slug']],
                $link
            );
        }
    }
}
