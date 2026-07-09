<?php

namespace App\Livewire\Admin;

use App\Services\MobileAppReleaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class AppReleaseManager extends Component
{
    use Toast;
    use WithFileUploads;

    public string $name = '';
    public string $description = '';
    public string $versionName = '';
    public string $versionCode = '';
    public string $releaseNotes = '';
    public string $featuresText = '';
    public string $sourceProjectPath = MobileAppReleaseService::SOURCE_PROJECT_PATH;

    public $apk;

    public ?string $currentApkName = null;
    public ?string $currentApkSizeLabel = null;
    public ?string $currentApkUploadedAt = null;
    public bool $hasApk = false;

    public function mount(MobileAppReleaseService $releases): void
    {
        $this->loadRelease($releases);
    }

    public function saveDetails(MobileAppReleaseService $releases): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1200'],
            'versionName' => ['required', 'string', 'max:40'],
            'versionCode' => ['required', 'string', 'max:40'],
            'releaseNotes' => ['nullable', 'string', 'max:2000'],
            'featuresText' => ['required', 'string', 'max:2000'],
            'sourceProjectPath' => ['required', 'string', 'max:500'],
        ]);

        $releases->saveDetails([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'version_name' => $validated['versionName'],
            'version_code' => $validated['versionCode'],
            'release_notes' => $validated['releaseNotes'] ?? '',
            'features' => $validated['featuresText'],
            'source_project_path' => $validated['sourceProjectPath'],
        ]);

        $this->loadRelease($releases);
        $this->success('App release details saved.');
    }

    public function uploadApk(MobileAppReleaseService $releases): void
    {
        $this->validate([
            'apk' => ['required', 'file', 'max:204800'],
        ]);

        $extension = strtolower((string) $this->apk->getClientOriginalExtension());

        if ($extension !== 'apk') {
            $this->addError('apk', 'Please upload an Android .apk file.');

            return;
        }

        $previousPath = $releases->release()['apk_path'] ?? null;
        $version = Str::slug(str_replace('.', '-', $this->versionName ?: 'latest'));
        $filename = 'projectaccess-' . $version . '-' . now()->format('YmdHis') . '.apk';
        $path = $this->apk->storeAs('mobile-apps', $filename, 'public');

        if (filled($previousPath) && $previousPath !== $path && Storage::disk('public')->exists($previousPath)) {
            Storage::disk('public')->delete($previousPath);
        }

        $releases->saveApk(
            $path,
            $this->apk->getClientOriginalName(),
            (int) $this->apk->getSize()
        );

        $this->apk = null;
        $this->loadRelease($releases);
        $this->success('APK uploaded and marked as the latest release.');
    }

    public function removeApk(MobileAppReleaseService $releases): void
    {
        $path = $releases->release()['apk_path'] ?? null;

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $releases->clearApk();
        $this->loadRelease($releases);
        $this->success('Current APK removed.');
    }

    public function render()
    {
        return view('livewire.admin.app-release-manager');
    }

    private function loadRelease(MobileAppReleaseService $releases): void
    {
        $release = $releases->release();

        $this->name = $release['name'];
        $this->description = $release['description'];
        $this->versionName = $release['version_name'];
        $this->versionCode = $release['version_code'];
        $this->releaseNotes = $release['release_notes'];
        $this->featuresText = implode(PHP_EOL, $release['features']);
        $this->sourceProjectPath = $release['source_project_path'];
        $this->currentApkName = $release['apk_original_name'];
        $this->currentApkSizeLabel = $release['apk_size_label'];
        $this->currentApkUploadedAt = $release['apk_uploaded_at'];
        $this->hasApk = $release['has_apk'];
    }
}
