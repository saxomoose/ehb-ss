<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasFactory;

    // Domains relationship are always.
    protected $with = ['domains'];

    // Protection against "mass assignment vulnerability" 
    // The fillable property specifies which attributes can be mass assigned. 
    // In other words, these attributes or fields are the only ones that can be mass assigned or uploaded. All others will be ignored.
    // This is for default security. Imagine if a user mass assigns a tenant or updates the admin email.
    // when setting this fillable property, we say: "even if other attributes are included here, the only ones allowed to be fillable are 'name' and 'tenancy_admin_email' ".
    protected $fillable = ['name', 'tenancy_admin_email'];


    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'tenancy_admin_email'
        ];
    }
}
