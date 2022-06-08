<?php

namespace Tests\Feature\Tenant;

use App\Models\BankAccount;
use App\Models\Event;
use App\Models\User;
use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Http\Middleware\CheckForAnypermission;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EventActionsTest extends TenantTestCase
{
    use WithFaker;

    public function getRoleAndPermission()
    {
        return [
            'admin' => ['admin', true],
            'manager' => ['manager', false],
            'seller' => ['seller', false]
        ];
    }

    public function getInvalidData()
    {
        //$this->setUpFaker();
        tenancy()->initialize($GLOBALS['tenant']); // Cannot use tenant field given order of execution.
        $event = Event::inRandomOrder()->first();
        $invalidBankAccountId = 2;
        $validName = Event::factory()->makeOne()->name;
        $validDate = $event->date;
        $validBankAccountId = $event->bank_account_id;

        return [
            'invalid name - name not unique' => [$event->name, $validDate, $validBankAccountId],
            'invalid date - wrong type' => [$validName, 'invalid date', $validBankAccountId],
            'invalid bank_account_id - does not exist' => [$validName, $validDate, $invalidBankAccountId]
        ];
    }

    /**
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getRoleAndPermission
     */
    public function getEvents_WhenAdminOrManager_Returns200($role, $permission)
    {
        DB::beginTransaction();

        Sanctum::actingAs(
            User::factory()->createOne(['is_admin' => $permission]),['']);

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events");

        if ($role == 'admin' ) {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data.0',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'date')
                        ->etc()
                )
            )->assertOk();
        } else if ($role == 'manager' || $role == 'seller') {
            $response->assertForbidden();
        }

        DB::rollBack();
    }

    /**
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getRoleAndPermission
     */
    public function postEvent_WithValidInput_Returns201($role, $permission)
    {
        DB::beginTransaction();

        Sanctum::actingAs(
            User::factory()->createOne(['is_admin' => $permission]),['']
        );

        $event = Event::factory()
            ->for(User::firstWhere('is_admin', false))
            ->for(BankAccount::first())
            ->makeOne();

        $response = $this->postJson("{$this->domainWithScheme}/api/events", [
            'data' => [
                'name' => $event->name,
                'date' => $event->date,
                'user_id' => $event->user_id,
                'bank_account_id' => $event->bank_account_id,
            ]
        ]);

        if ($role == 'admin') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'date', 'bank_account_id')
                        ->etc()
                )
            )->assertCreated(); // Tests JSON response
            $this->assertDatabaseHas('events', ['name' => $event->name]); // Tests database write.
        } else if ($role == 'manager' || $role == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseMissing('events', ['name' => $event->name]);
        }

        DB::rollBack();
    }

    /**
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getInvalidData
     */
    public function postEvent_WithInvalidInput_Returns422($name, $date, $bank_account_id)
    {
        $this->withoutMiddleware();

        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/events", [
            'data' => [
                'name' => $name,
                'date' => $date,
                'bank_account_id' => $bank_account_id
            ]
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertDatabaseCount('events', 2); // Assumes that only 2 events are seeded during testing.

        DB::rollBack();
    }

    /**
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getPermission
     */
    public function getEvent_WhenAdminOrManager_Returns200($permission)
    {
        DB::beginTransaction();

        $event = Event::inRandomOrder()->first();
        $manager = $event->user;
        $user = User::factory()->createOne(['permission' => $permission]);
        
        if ($permission == 'manager') {
            Sanctum::actingAs(
                $manager,
                ['']
            );
        } else {
            Sanctum::actingAs(
                $user,
                ['']
            );
        }

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events/{$event->id}");

        if ($permission == 'admin' || $permission == 'manager') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'date')
                        ->etc()
                )
            )->assertOk();
        } else if ($permission == 'seller') {
            $response->assertForbidden();
        }

        DB::rollBack();
    }

    /**
     * @test
     * Test with user with permission manager.
     * @covers \App\Http\Controllers\EventController
     */
    public function patchEvent_WithPassingValidation_Returns200()
    {
        DB::beginTransaction();
        
        $event = Event::inRandomOrder()->first();
        $manager = $event->user;
        Sanctum::actingAs(
            $manager,
            ['']
        );

        $updatedName = 'updated_event_name';
        $updatedDate = Carbon::now()->toDateString();

        $response = $this->patchJson("{$this->domainWithScheme}/api/events/{$event->id}", [
            'data' => [
                'name' => $updatedName,
                'date' => $updatedDate,
                'user_id' => $event->user_id,
                'bank_account_id' => $event->bank_account_id
            ]
        ]);
        $updatedEvent = Event::firstWhere('name', '=', $updatedName);

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'data',
                fn ($json) =>
                $json->where('id', $event->id)
                    ->where('name', $updatedName)
                    ->where('date', $updatedDate)
                    ->etc()
            )
        )->assertOk();
        $this->assertEquals($updatedName, $updatedEvent->name);
        $this->assertEquals($updatedDate, $updatedEvent->date);

        DB::rollBack();
    }

    /**
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getPermission
     */
    public function deleteEvent_WhenAdmin_Returns204($permission)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(),
            ["{$permission}"]
        );
        $event = Event::inRandomOrder()->first();

        DB::beginTransaction();
        $response = $this->deleteJson("{$this->domainWithScheme}/api/events/{$event->id}");

        if ($permission == 'admin') {
            $response->assertNoContent();
            $this->assertDeleted($event);
        } else if ($permission == 'manager') {
            $response->assertForbidden();
            $this->assertDatabaseHas('events', ['id' => $event->id]);
        } else if ($permission == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseHas('events', ['id' => $event->id]);
        }

        DB::rollBack();
    }
}
