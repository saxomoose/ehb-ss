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
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EventActionsTest extends TenantTestCase
{
    use WithFaker;

    public function getAbilities()
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'seller' => ['seller']
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
     * @dataProvider getAbilities
     */
    public function getEvents_WhenAdminOrManager_Returns200($ability)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(),
            ["{$ability}"]
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events");

        if ($ability == 'admin' || $ability == 'manager') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data.0',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'date')
                        ->etc()
                )
            )->assertOk();
        } else if ($ability == 'seller') {
            $response->assertForbidden();
        }
    }

    /**
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getAbilities
     */
    public function postEvent_WithValidInput_Returns201($ability)
    {
        Sanctum::actingAs(
            User::inRandomOrder()->first(),
            ["{$ability}"]
        );
        $event = Event::factory()
            ->for(BankAccount::first(['id']))
            ->make();

        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/events", [
            'data' => [
                'name' => $event->name,
                'date' => $event->date,
                'bank_account_id' => $event->bank_account_id,
            ]
        ]);

        if ($ability == 'admin' || $ability == 'manager') {
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
        } else if ($ability == 'seller') {
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
        $this->withoutMiddleware([Authenticate::class, CheckForAnyAbility::class]);

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
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getAbilities
     */
    public function getEvent_WhenAdminOrManager_Returns200($ability)
    {
        $event = Event::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere('id', '=', $event->created_by),
            ["{$ability}"]
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events/{$event->id}");

        if ($ability == 'admin' || $ability == 'manager') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'date')
                        ->etc()
                )
            )->assertOk();
        } else if ($ability == 'seller') {
            $response->assertForbidden();
        }
    }

    /**
     * Test with user with ability manager.
     * @test
     * @covers \App\Http\Controllers\EventController
     */
    public function patchEvent_WithPassingValidation_Returns200()
    {
        $event = Event::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere('id', '=', $event->created_by),
            ['manager']
        );

        $updatedName = Event::factory()->makeOne()->name;
        $updatedDate = Carbon::now()->toDateString();

        DB::beginTransaction();
        $response = $this->patchJson("{$this->domainWithScheme}/api/events/{$event->id}", [
            'data' => [
                'name' => $updatedName,
                'date' => $updatedDate,
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

        DB::rollBack();
    }

    /**
     * @test
     * @covers \App\Http\Controllers\EventController
     * @dataProvider getAbilities
     */
    public function deleteEvent_WhenAdmin_Returns204($ability)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(),
            ["{$ability}"]
        );
        $event = Event::inRandomOrder()->first();

        DB::beginTransaction();
        $response = $this->deleteJson("{$this->domainWithScheme}/api/events/{$event->id}");

        if ($ability == 'admin') {
            $response->assertNoContent();
            $this->assertDeleted($event);
        } else if ($ability == 'manager') {
            $response->assertForbidden();
            $this->assertDatabaseHas('events', ['id' => $event->id]);
        } else if ($ability == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseHas('events', ['id' => $event->id]);
        }

        DB::rollBack();
    }
}
