<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\RequiredPublicPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_cms_pages_are_created_and_available(): void
    {
        $this->seed(RequiredPublicPagesSeeder::class);

        foreach (['home', 'about', 'services', 'contacts', 'privacy'] as $slug) {
            $this->assertDatabaseHas('pages', ['slug' => $slug, 'is_active' => true]);
        }

        $home = Page::query()->where('slug', 'home')->firstOrFail();
        $this->assertSame('image', $home->blocks['hero']['background_type']);
        $this->assertNotEmpty($home->blocks['hero']['background_image_url']);

        $this->get('/')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/contacts')->assertOk();
        $this->get('/privacy')->assertOk();
    }

    public function test_existing_public_page_content_is_not_overwritten(): void
    {
        Page::query()->create([
            'slug' => 'about',
            'title' => 'Индивидуальный текст',
            'is_active' => false,
            'blocks' => ['paragraphs' => ['Сохранить этот текст.']],
        ]);
        Page::query()->create([
            'slug' => 'home',
            'title' => 'Моя главная',
            'is_active' => false,
            'blocks' => [
                'hero' => [
                    'background_type' => 'image',
                    'background_image_url' => '/storage/pages/custom-hero.jpg',
                ],
            ],
        ]);

        $this->seed(RequiredPublicPagesSeeder::class);

        $this->assertDatabaseHas('pages', [
            'slug' => 'about',
            'title' => 'Индивидуальный текст',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('pages', [
            'slug' => 'home',
            'title' => 'Моя главная',
            'is_active' => false,
        ]);
        $this->assertSame(
            '/storage/pages/custom-hero.jpg',
            Page::query()->where('slug', 'home')->firstOrFail()->blocks['hero']['background_image_url'],
        );
    }
}
