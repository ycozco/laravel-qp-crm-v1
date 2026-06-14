<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns\ResolvesTenantContext;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\WhatsappConversationResource;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;

class WhatsappConversationController extends ApiController
{
    use ResolvesTenantContext;

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = $this->resolveTenant($request);

        $query = WhatsappConversation::query()
            ->withCount('messages')
            ->with('account')
            ->when($tenant, fn (Builder $builder) => $builder->where('tenant_id', $tenant->id), fn (Builder $builder) => $builder->whereRaw('1 = 0'))
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = (string) $request->query('search');

                $builder->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', (string) $request->query('status')));

        $this->applySort($query, $request, ['contact_name', 'status', 'last_message_at', 'created_at'], '-last_message_at');

        return WhatsappConversationResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(Request $request, WhatsappConversation $conversation): WhatsappConversationResource
    {
        $tenant = $this->resolveTenant($request, $conversation->tenant_id);

        abort_unless($tenant && $conversation->tenant_id === $tenant->id, 404);

        $conversation->load(['account', 'messages' => fn ($query) => $query->where('tenant_id', $tenant->id)->oldest('sent_at')]);
        $conversation->loadCount('messages');

        return new WhatsappConversationResource($conversation);
    }
}
