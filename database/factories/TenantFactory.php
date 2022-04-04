<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $centralDomain = config('tenancy.central_domains.0');
        $subdomain = 'demo'; // $this->faker->domainWord();
        return [
            'name' => $subdomain,
            'tenancy_admin_email' => "admin@{$subdomain}.{$centralDomain}"
        ];
    }
}
