<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns\ResolvesTenantContext;
use VentureDrake\LaravelCrm\Http\Requests\Api\V2\UpsertWhatsappAccountRequest;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\WhatsappAccountResource;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;

class WhatsappAccountController extends ApiController
{
    use ResolvesTenantContext;

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $this->resolveTenant($request);

        $accounts = TenantWhatsappAccount::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->paginate($this->perPage($request));

        return WhatsappAccountResource::collection($accounts);
    }

    public function store(UpsertWhatsappAccountRequest $request): WhatsappAccountResource
    {
        $tenant = $this->resolveTenant($request, $request->integer('tenant_id') ?: null);

        abort_unless($tenant, 404);

        $this->assertTenantCanBeManaged($request, $tenant);

        $data = $request->validated();

        $account = new TenantWhatsappAccount([
            'tenant_id' => $tenant->id,
        ]);

        $this->fillAccount($account, $data);
        $account->save();

        return new WhatsappAccountResource($account);
    }

    public function show(Request $request, TenantWhatsappAccount $account): WhatsappAccountResource
    {
        $tenant = $this->resolveTenant($request, $account->tenant_id);

        abort_unless($tenant && $account->tenant_id === $tenant->id, 404);

        return new WhatsappAccountResource($account);
    }

    public function update(UpsertWhatsappAccountRequest $request, TenantWhatsappAccount $account): WhatsappAccountResource
    {
        $tenant = $this->resolveTenant($request, $account->tenant_id);

        abort_unless($tenant && $account->tenant_id === $tenant->id, 404);

        $this->assertTenantCanBeManaged($request, $tenant);

        $this->fillAccount($account, $request->validated());
        $account->save();

        return new WhatsappAccountResource($account->fresh());
    }

    public function destroy(Request $request, TenantWhatsappAccount $account): Response
    {
        $tenant = $this->resolveTenant($request, $account->tenant_id);

        abort_unless($tenant && $account->tenant_id === $tenant->id, 404);

        $this->assertTenantCanBeManaged($request, $tenant);

        $account->delete();

        return response()->noContent();
    }

    protected function fillAccount(TenantWhatsappAccount $account, array $data): void
    {
        $account->fill([
            'display_name' => $data['display_name'],
            'business_account_id' => $data['business_account_id'] ?? null,
            'phone_number_id' => $data['phone_number_id'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'app_id' => $data['app_id'] ?? null,
            'webhook_verify_token' => $data['webhook_verify_token'] ?? null,
            'status' => $data['status'],
            'last_error' => $data['last_error'] ?? null,
            'connected_at' => ($data['status'] ?? null) === 'connected'
                ? ($account->connected_at ?? now())
                : null,
        ]);

        if (($data['access_token'] ?? '') !== '') {
            $account->access_token_encrypted = Crypt::encryptString($data['access_token']);
        }
    }
}
