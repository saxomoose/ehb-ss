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
            'seller' => ['']
        ];
    }

    /**
     * @test
     * @covers \App\Http\Controllers\BankAccountController
     */
    public function getBankAccount_WhenIsManagedByUser_Returns200()
    {
        $bankAccount = BankAccount::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere('id', $bankAccount->getManagers()->first()->id),['']
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/bankaccounts/{$bankAccount->id}");

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'data',
                fn ($json) =>
                $json->hasAll('id', 'beneficiary_name', 'bic', 'iban')
                    ->etc()
            )
        )->assertOk();
    }
}
