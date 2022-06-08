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

    public function getPermission()
    {
        return [
            'admin' => [true],
            'manager' => [false],
            'seller' => [false]
        ];
    }

    /**
     * @test
     * @covers \App\Http\Controllers\UserController
     * @dataProvider getPermission
     */
    public function getUsers_WhenAdmin_Returns200($permission)
    {
        DB::beginTransaction();

        Sanctum::actingAs(
            User::factory()->createOne(['is_admin' => $permission]),['']
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/users");

        if ($permission) {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data.0',
                    fn ($json) =>
                    $json->hasAll('id', 'name', 'email')
                        ->etc()
                )
            )->assertOk();
        } else if (!$permission) {
            $response->assertForbidden();
        }

        DB::rollBack();
    }
}
