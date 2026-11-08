<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\CatalogStats;
use App\Filament\Widgets\RecentUploads;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_professors_see_uploads_for_their_courses_only(): void
    {
        Storage::fake(Material::DISK);
        $professor = User::factory()->professor()->create();
        $theirs = Material::factory()->create();
        $theirs->topic->course->professors()->attach($professor);
        $someoneElses = Material::factory()->create();
        $this->actingAs($professor);

        $this->get(route('filament.admin.pages.dashboard'))->assertOk();

        Livewire::test(RecentUploads::class)
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords([$someoneElses]);

        Livewire::test(CatalogStats::class)
            ->assertSee('My courses')
            ->assertDontSee('Students');
    }

    public function test_admins_see_site_wide_figures(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->count(2)->create();

        Livewire::test(CatalogStats::class)
            ->assertSee('Courses')
            ->assertSee('Students');
    }

    public function test_everyone_can_open_their_profile_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('filament.learn.auth.profile'))
            ->assertOk();

        $this->actingAs(User::factory()->professor()->create())
            ->get(route('filament.admin.auth.profile'))
            ->assertOk();
    }

    public function test_make_admin_promotes_an_existing_account_or_creates_one(): void
    {
        $student = User::factory()->create(['email' => 'dean@example.com']);

        $this->artisan('users:make-admin', ['email' => 'DEAN@example.com'])->assertSuccessful();
        $this->assertSame(UserRole::Admin, $student->refresh()->role);

        $this->artisan('users:make-admin', ['email' => 'new@example.com'])
            ->expectsOutputToContain('Temporary password')
            ->assertSuccessful();
        $this->assertSame(UserRole::Admin, User::firstWhere('email', 'new@example.com')->role);
    }
}
