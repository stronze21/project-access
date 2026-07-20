<?php

namespace App\Observers;

use App\Models\Resident;
use App\Services\ResidentMediaStagingService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

class ResidentObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly ResidentMediaStagingService $mediaStagingService
    ) {}

    public function created(Resident $resident): void
    {
        try {
            $this->mediaStagingService->attachTo($resident);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
