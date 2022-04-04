<?php

namespace App\Http\Controllers;

use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Tenant::class, 'tenant');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TenantResource::collection(Tenant::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array:name,tenancy_admin_email',
            'data.name' => ['required', 'alpha_num', Rule::unique('tenants', 'name'), 'max:30'], // alpha_num rule does not accept whitespace.
            'data.tenancy_admin_email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawValidatedAttributes = $validator->validated();
        $validatedAttributes = $rawValidatedAttributes['data'];

        $domain = strtolower($validatedAttributes['name']) . '.' . config('tenancy.central_domains.0');

        $tenant = Tenant::create([
            'name' => $validatedAttributes['name'],
            'tenancy_admin_email' => $validatedAttributes['tenancy_admin_email']
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    public function show(Tenant $tenant)
    {
        return new TenantResource($tenant);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    /*     public function update(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:tenants|max: 30',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedAttributes = $validator->validated();

        $tenant->update($validatedAttributes['name']);

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    } */

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete;

        return response()->noContent();
    }
}
