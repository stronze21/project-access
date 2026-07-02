<?php

namespace App\Services;

use App\Models\ComplaintAttachment;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Throwable;

class AttachmentVirusScanner
{
    /**
     * @return array{status: string, message: string}
     */
    public function scan(UploadedFile $file): array
    {
        $command = config('complaints.virus_scan.command');

        if (empty($command)) {
            return [
                'status' => ComplaintAttachment::SCAN_CLEAN,
                'message' => 'Virus scan skipped (scanner not configured).',
            ];
        }

        try {
            $process = Process::fromShellCommandline($command.' '.escapeshellarg($file->getRealPath() ?: ''));
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'status' => ComplaintAttachment::SCAN_CLEAN,
                    'message' => 'File scan completed with no threats found.',
                ];
            }

            return [
                'status' => ComplaintAttachment::SCAN_INFECTED,
                'message' => trim($process->getErrorOutput().' '.$process->getOutput()) ?: 'Potentially unsafe file detected.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => ComplaintAttachment::SCAN_FAILED,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
