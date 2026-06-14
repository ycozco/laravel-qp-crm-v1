<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantUser;

trait ResolvesTenantContext
{
    protected function accessibleTenants(Request $request): Collection
    {
        $user = $request->user();

        if (! $user) {
            return new Collection;
        }

        return Tenant::query()
            ->forUser($user)
            ->orderBy('name')
            ->get();
    }

    protected function resolveTenant(Request $request, ?int $tenantId = null): ?Tenant
    {
        $tenants = $this->accessibleTenants($request);

        if ($tenants->isEmpty()) {
            return null;
        }

        $tenantId ??= $request->integer('tenant_id') ?: null;

        if ($tenantId) {
            $tenant = $tenants->firstWhere('id', $tenantId);

            if (! $tenant) {
                throw new HttpException(403, 'You are not a member of the requested tenant.');
            }

            return $tenant;
        }

        return $tenants->first();
    }

    protected function tenantRole(Request $request, Tenant $tenant): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return TenantUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->getKey())
            ->value('role');
    }

    protected function assertTenantCanBeManaged(Request $request, Tenant $tenant): void
    {
        $role = $this->tenantRole($request, $tenant);

        if (! in_array($role, ['owner', 'admin', 'manager'], true)) {
            throw new HttpException(403, 'You do not have permission to manage this tenant.');
        }
    }
}
