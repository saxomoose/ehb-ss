<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use App\Notifications\PINCodeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class RegisterActionTest extends TenantTestCase
{
    use WithFaker;
    /**
     * @test
     * @covers \App\Http\Controllers\RegisterController
     */
    public function register_WithValidInput_Returns200()
    {
        // given
        $user = User::inRandomOrder()->first();
        Event::fake();

        // when
        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/register", [
            'data' => [
                'name' => $this->faker->name(),
                'email' => $user->email,
                'password' => '5<4LpdPn'
            ]
        ]);

        // then
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        Event::assertDispatched(Registered::class);
        $user = User::find($user->id); // Necessary to retrieve the model again.
        $this->assertTrue(isset($user->pin_code));
        $this->assertTrue(isset($user->pin_code_timestamp));

        DB::rollBack();
    }

    /**
     * Fails unless saveQuietly is used. Because this route is not subject to auth:sanctum.
     * @test
     * @covers \App\Http\Controllers\RegisterController
     */
    public function register_WithValidInput_SendsPINCodeNotification()
    {
        $user = User::inRandomOrder()->first();
        Notification::fake();

        DB::beginTransaction();
        $this->postJson("{$this->domainWithScheme}/api/register", [
            'data' => [
                'name' => $this->faker->name(),
                'email' => $user->email,
                'password' => '5<4LpdPn'
            ]
        ]);

        Notification::assertSentTo($user, PINCodeNotification::class);

        DB::rollBack();
    }
}
