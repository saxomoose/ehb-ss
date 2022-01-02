<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class DummyTest extends TenantTestCase
{
    /**
     * @test
     */
    public function getEvents_WhenAdminOrManager_Returns200()
    {
        $response = $this->json('GET', "{$this->domainWithScheme}/api/events");
    }
}
