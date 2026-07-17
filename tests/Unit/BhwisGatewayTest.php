<?php

namespace Tests\Unit;

use App\Exceptions\BhwisUnavailableException;
use App\Repositories\BhwisRepository;
use App\Services\Bhwis\BhwisGateway;
use Mockery;
use Tests\TestCase;

class BhwisGatewayTest extends TestCase
{
    public function test_optional_related_query_failures_do_not_discard_a_verified_personal_record(): void
    {
        $repository = Mockery::mock(BhwisRepository::class);
        $repository->shouldReceive('getFamilyMembersByPin')->once()
            ->andThrow(new BhwisUnavailableException('Family lookup failed.'));
        $repository->shouldReceive('getBhwAssignments')->once()->andReturn([]);
        $repository->shouldReceive('getCivilStatus')->once()
            ->andThrow(new BhwisUnavailableException('Reference lookup failed.'));
        $repository->shouldReceive('getSourceIncomeType')->once()->andReturn([]);
        $repository->shouldReceive('getEducationalAttainment')->once()->andReturn([]);

        $personal = [
            'PIN' => '26-45287',
            'Lastname' => 'David',
            'Firstname' => 'Christian Joseph',
            'Birthdate' => '2003-08-09',
        ];

        $records = (new BhwisGateway($repository))->linkedRecords('26-45287', $personal);

        $this->assertSame([$personal], $records['personal']);
        $this->assertSame([], $records['family_members']);
        $this->assertSame([], $records['civil_status']);
    }
}
