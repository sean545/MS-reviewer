<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounts_are_students_who_use_the_portal_but_not_the_admin_panel(): void
    {
        $student = User::factory()->create();

        $this->assertSame(UserRole::Student, $student->role);

        $this->actingAs($student);
        $this->get(route('filament.learn.pages.dashboard'))->assertOk();
        $this->get(route('filament.admin.pages.dashboard'))->assertForbidden();
    }

    public function test_professors_reach_the_admin_panel_but_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->professor()->create());

        $this->get(route('filament.admin.pages.dashboard'))->assertOk();
        $this->get(route('filament.admin.resources.users.index'))->assertForbidden();
    }

    public function test_admin_can_promote_a_student_to_professor(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $student = User::factory()->create();

        $this->get(route('filament.admin.resources.users.index'))->assertOk();

        Livewire::test(EditUser::class, ['record' => $student->getRouteKey()])
            ->fillForm(['role' => UserRole::Professor->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Professor, $student->refresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldDisabled('role')
            ->call('save');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($admin))
            ->assertActionVisible(TestAction::make(DeleteAction::class)->table($other));
    }

    public function test_visitors_can_register_and_become_students(): void
    {
        $this->get(route('filament.learn.auth.register'))->assertOk();
        $this->get('/')
            ->assertOk()
            ->assertSee('A quiet place')
            ->assertSee(route('filament.learn.auth.register'));
    }
}
