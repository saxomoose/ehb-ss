<?php

namespace Tests\Feature\Tenant;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EventUserActionsTest extends TenantTestCase
{
    public function getRole()
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'seller' => ['seller']
        ];
    }

    /**
     */
    public function seed_WhenUserIsManager_Returns201()
    {
        Sanctum::actingAs(
            User::factory()->makeOne(['role' => 'manager']),
            []
        );

        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/users", [
            'data' => [
                'email' => $this->faker->email(),
                'role' => 'coordinator'
            ]
        ]);

        DB::rollBack();
    }

    /**
     * @test
     * @dataProvider getRole
     */
    public function upsert_WithValidInput_Returns201($role)
    {
        DB::beginTransaction();

        $event = Event::inRandomOrder()->first();
        $manager = $event->user;
        if ($role == 'admin') {
            Sanctum::actingAs(
                User::firstWhere('is_admin', true),
                ['']
            );
        } else if ($role == 'manager') {
            Sanctum::actingAs(
                $manager,
                ['']
            );
        } else if ($role == 'seller') {
            Sanctum::actingAs(
                User::firstWhere('is_admin', false),
                ['']
            );
        }

        $userModel = User::factory()->createOne();

        $response = $this->putJson("{$this->domainWithScheme}/api/events/{$event->id}/users/{$userModel->id}");

        if ($role == 'admin' || $role == 'manager') {
            $response->assertCreated();
            $this->assertDatabaseHas('event_user', [
                'event_id' => $event->id,
                'user_id' => $userModel->id,
            ]);
        } else if ($role == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseMissing('event_user', [
                'event_id' => $event->id,
                'user_id' => $userModel->id,
            ]);
        }

        DB::rollBack();
    }
}
