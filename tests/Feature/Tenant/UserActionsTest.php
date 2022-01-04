<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class UserActionsTest extends TenantTestCase
{
    use WithFaker;

    public function getTopLevelAbilities()
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'seller' => ['seller']
        ];
    }

    /**
     * @test
     * @covers \App\Http\Controllers\UserController
     * @dataProvider getTopLevelAbilities
     */
    public function getUsers_WhenAdminOrWrite_Returns200($ability)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(),
            ["{$ability}"]
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/users");

        if ($ability == 'admin' || $ability == 'manager') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data.0',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'email')
                        ->etc()
                )
            )->assertOk();
        } else if ($ability == 'seller') {
            $response->assertForbidden();
        }
    }

    /**
     * @test
     */
    public function seed_()
    {
        Sanctum::actingAs(
            User::factory()->makeOne(['ability' => 'coordinator']),
            []
        );

        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/users", [
            'data' => [
                'email' => $this->faker->email(),
                'ability' => 'coordinator'
            ]
        ]);

        DB::rollBack();
    }
}
