<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $count = 1;
        $tenantName = tenant()->name;
        $name = "{$tenantName}_event_{$count}";
        $count++;

        return [
            //'id' => $this->faker->uuid(),
            'name' => $name,
            'date' => $this->faker->date('Y-m-d'),
        ];
    }
}
