<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobilePrivateComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_complaint_list_requires_a_resident_and_only_returns_their_complaints(): void
    {
        $this->getJson('/api/mobile/complaints')->assertUnauthorized();

        $citizen = $this->citizen();
        $otherCitizen = $this->citizen();
        $category = ComplaintCategory::create(['name' => 'Public Safety', 'is_active' => true]);
        $this->complaint($citizen, $category, 'My complaint', 'BMT-MOBILE-OWN');
        $this->complaint($otherCitizen, $category, 'Other complaint', 'BMT-MOBILE-OTHER');

        Sanctum::actingAs($citizen);

        $this->getJson('/api/mobile/complaints')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My complaint');
    }

    public function test_mobile_resident_submission_is_always_private(): void
    {
        $citizen = $this->citizen();
        $category = ComplaintCategory::create(['name' => 'Roads', 'is_active' => true]);
        Sanctum::actingAs($citizen);

        $this->postJson('/api/mobile/complaints', [
            'title' => 'Road damage',
            'short_summary' => 'Road damage requires inspection.',
            'description' => 'A section of road has significant surface damage.',
            'category_id' => $category->id,
            'visibility' => Complaint::VISIBILITY_PUBLIC_NAMED,
            'latitude' => 16.1554,
            'longitude' => 119.9812,
        ])->assertCreated();

        $this->assertDatabaseHas('complaints', [
            'submitted_by_user_id' => $citizen->id,
            'title' => 'Road damage',
            'visibility' => Complaint::VISIBILITY_PRIVATE,
            'is_anonymous_submission' => false,
        ]);
    }

    private function citizen(): User
    {
        Role::findOrCreate('citizen', 'web');
        $user = User::factory()->create();
        $user->assignRole('citizen');

        return $user;
    }

    private function complaint(User $user, ComplaintCategory $category, string $title, string $reference): Complaint
    {
        return Complaint::create([
            'reference_code' => $reference,
            'title' => $title,
            'short_summary' => $title,
            'description' => $title,
            'category_id' => $category->id,
            'submitted_by_user_id' => $user->id,
            'visibility' => Complaint::VISIBILITY_PRIVATE,
            'status' => Complaint::STATUS_RECEIVED,
        ]);
    }
}
