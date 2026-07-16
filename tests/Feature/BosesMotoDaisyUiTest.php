<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BosesMotoDaisyUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_complaint_board_renders_daisyui_components(): void
    {
        $this->get(route('complaints.public.index'))
            ->assertOk()
            ->assertSee('card', false)
            ->assertSee('btn btn-primary', false)
            ->assertSee('select select-bordered', false);
    }

    public function test_representative_bosesmoto_surfaces_use_daisyui_components(): void
    {
        $views = [
            'resources/views/layouts/public.blade.php',
            'resources/views/complaints/partials/public-index-content.blade.php',
            'resources/views/complaints/manage/index.blade.php',
            'resources/views/complaints/manage/show.blade.php',
            'resources/views/complaints/executive-dashboard.blade.php',
            'resources/views/complaints/references/manage.blade.php',
            'resources/views/sentiments/index.blade.php',
            'resources/views/sentiments/partials/post-card.blade.php',
            'resources/views/sentiments/partials/comment-tree.blade.php',
            'resources/views/polls/index.blade.php',
            'resources/views/polls/create.blade.php',
            'resources/views/polls/show.blade.php',
            'resources/views/dashboards/role-dashboard.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents(base_path($view));

            $this->assertMatchesRegularExpression(
                '/\b(card|btn|badge|alert|input-bordered|select-bordered|textarea-bordered|table-zebra|tabs-boxed)\b/',
                $contents,
                "{$view} does not use a DaisyUI component."
            );
        }
    }

    public function test_bosesmoto_views_use_theme_tokens_instead_of_fixed_neutral_colors(): void
    {
        $paths = [
            base_path('resources/views/complaints'),
            base_path('resources/views/sentiments'),
            base_path('resources/views/polls'),
        ];

        foreach ($paths as $path) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    '/\b(bg-white|bg-gray-|text-gray-|border-gray-|bg-slate-|text-slate-|border-slate-|ring-slate-|divide-slate-)/',
                    $contents,
                    "{$file->getPathname()} still uses a fixed neutral color."
                );
            }
        }
    }
}
