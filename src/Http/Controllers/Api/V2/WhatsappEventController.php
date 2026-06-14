<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns\ResolvesTenantContext;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\WhatsappWebhookEventResource;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappEventController extends ApiController
{
    use ResolvesTenantContext;

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $this->resolveTenant($request);

        $query = WhatsappWebhookEvent::query()
            ->when($tenant, fn (Builder $builder) => $builder->where('tenant_id', $tenant->id), fn (Builder $builder) => $builder->whereRaw('1 = 0'))
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = (string) $request->query('search');

                $builder->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('event_type', 'like', "%{$search}%")
                        ->orWhere('field', 'like', "%{$search}%")
                        ->orWhere('phone_number_id', 'like', "%{$search}%")
                        ->orWhere('meta_object_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('processing_status', (string) $request->query('status')));

        $this->applySort($query, $request, ['event_type', 'processing_status', 'received_at', 'processed_at', 'created_at'], '-received_at');

        return WhatsappWebhookEventResource::collection($query->paginate($this->perPage($request)));
    }
}
