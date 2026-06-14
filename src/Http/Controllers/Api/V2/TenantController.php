<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns\ResolvesTenantContext;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\TenantResource;

class TenantController extends ApiController
{
    use ResolvesTenantContext;

    public function index(Request $request): AnonymousResourceCollection
    {
        return TenantResource::collection($this->accessibleTenants($request));
    }
}
