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
            'seller' => ['']
        ];
    }

    /**
     * @test
     * @covers \App\Http\Controllers\UserController
     * @dataProvider getTopLevelAbilities
     */
    public function getUsers_WhenAdmin_Returns200($ability)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(['ability' => $ability]),['']
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/users");

        if ($ability == 'admin') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data.0',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'email')
                        ->etc()
                )
            )->assertOk();
        } else if ($ability == 'manager' || $ability == '') {
            $response->assertForbidden();
        }
    }
}
