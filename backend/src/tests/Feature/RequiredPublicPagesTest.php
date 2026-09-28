<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\RequiredPublicPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_public_pages_are_created_and_available(): void
    {
        $this->seed(RequiredPublicPagesSeeder::class);

        $this->assertDatabaseHas('pages', ['slug' => 'about', 'is_active' => true]);
        $this->assertDatabaseHas('pages', ['slug' => 'services', 'is_active' => true]);

        $this->get('/about')->assertOk();
        $this->get('/services')->assertOk();
    }

    public function test_existing_public_page_content_is_not_overwritten(): void
    {
        Page::query()->create([
            'slug' => 'about',
            'title' => 'Индивидуальный текст',
            'is_active' => false,
            'blocks' => ['paragraphs' => ['Сохранить этот текст.']],
        ]);

        $this->seed(RequiredPublicPagesSeeder::class);

        $this->assertDatabaseHas('pages', [
            'slug' => 'about',
            'title' => 'Индивидуальный текст',
            'is_active' => false,
        ]);
    }
}
