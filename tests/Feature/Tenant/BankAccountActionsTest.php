<?php

namespace Tests\Feature\Tenant;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class BankAccountActionsTest extends TenantTestCase
{
    public function getTopLevelAbilities()
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'seller' => ['seller']
        ];
    }

    /**
     * @covers \App\Http\Controllers\BankAccountController
     * @dataProvider getTopLevelAbilities
     */
    public function getBankAccount_WhenAdminOrManager_Returns200($ability)
    {
        $bankAccount = BankAccount::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere('id', '=', $bankAccount->created_by),
            ["{$ability}"]
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/bankaccounts/{$bankAccount->id}");

        if ($ability == 'admin' || $ability == 'manager') {
            $response->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'data',
                    fn ($json) =>
                    $json->hasAll('id', 'beneficiary_name', 'bic', 'iban')
                        ->etc()
                )
            )->assertOk();
        } else if ($ability == 'seller') {
            $response->assertForbidden();
        }
    }
}
