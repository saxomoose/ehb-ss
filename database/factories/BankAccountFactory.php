<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class BankAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = tenant()->name;

        return [
            //'id' => $this->faker->uuid(),
            'beneficiary_name' => $name,
            'bic' => $this->faker->swiftBicNumber(),
            'iban' => $this->faker->iban('BE', '', 16),
        ];
    }
}
