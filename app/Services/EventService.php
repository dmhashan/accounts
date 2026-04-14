<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationGuest;
use Illuminate\Support\Str;

class EventService
{
    public function index(int $tenantId, int $perPage, string $search = ''): array
    {
        $query = Event::where('tenant_id', $tenantId)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('registrations')
            ->withSum(['registrations as total_paid' => fn ($q) => $q->where('is_paid', true)], 'total_fee')
            ->withSum(['registrations as total_outstanding' => fn ($q) => $q->where('is_paid', false)], 'total_fee')
            ->orderByDesc('start_datetime');

        $paginator = $query->paginate($perPage);

        return [
            'data' => $paginator->map(fn (Event $e) => $this->listItem($e)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    public function show(Event $event): array
    {
        $event->loadCount('registrations');

        return [
            'id'                     => $event->id,
            'name'                   => $event->name,
            'slug'                   => $event->slug,
            'start_datetime'         => $event->start_datetime?->toIso8601String(),
            'end_datetime'           => $event->end_datetime?->toIso8601String(),
            'venue'                  => $event->venue,
            'venue_url'              => $event->venue_url,
            'agenda'                 => $event->agenda,
            'registration_process'   => $event->registration_process,
            'ticket_fee'             => (float) $event->ticket_fee,
            'additional_ticket_fee'  => (float) $event->additional_ticket_fee,
            'is_active'              => $event->is_active,
            'registrations_count'    => $event->registrations_count,
            'created_at'             => $event->created_at?->toIso8601String(),
        ];
    }

    public function store(int $tenantId, array $data): Event
    {
        $data['tenant_id'] = $tenantId;
        $data['slug']      = $this->uniqueSlug($tenantId, $data['slug'] ?? $data['name']);

        return Event::create($data);
    }

    public function update(Event $event, array $data): Event
    {
        if (isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($event->tenant_id, $data['slug'], $event->id);
        }

        $event->update($data);

        return $event->fresh();
    }

    public function destroy(Event $event): void
    {
        $event->delete();
    }

    public function registrations(Event $event, int $perPage): array
    {
        $paginator = EventRegistration::where('event_id', $event->id)
            ->with(['member:id,name,member_id,gender,phone_number', 'guests'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => $paginator->map(fn (EventRegistration $r) => $this->toRegistrationItem($r)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    public function register(Event $event, int $tenantId, array $data, ?int $memberId = null): EventRegistration
    {
        $guests       = $data['guests'] ?? [];
        $ticketFee    = (float) $event->ticket_fee;
        $guestFee     = (float) $event->additional_ticket_fee;
        $totalFee     = $ticketFee + (count($guests) * $guestFee);

        $registration = EventRegistration::create([
            'event_id'   => $event->id,
            'tenant_id'  => $tenantId,
            'member_id'  => $memberId,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'total_fee'  => $totalFee,
        ]);

        foreach ($guests as $guest) {
            EventRegistrationGuest::create([
                'event_registration_id' => $registration->id,
                'first_name'            => $guest['first_name'],
                'last_name'             => $guest['last_name'],
                'fee'                   => $guestFee,
            ]);
        }

        return $registration->load('guests');
    }

    public function getMyRegistration(Event $event, int $memberId): ?EventRegistration
    {
        return EventRegistration::where('event_id', $event->id)
            ->where('member_id', $memberId)
            ->with('guests')
            ->first();
    }

    public function updateRegistration(EventRegistration $registration, Event $event, array $data): EventRegistration
    {
        $guests   = $data['guests'] ?? [];
        $guestFee = (float) $event->additional_ticket_fee;
        $totalFee = (float) $event->ticket_fee + (count($guests) * $guestFee);

        $registration->update([
            'first_name' => $data['first_name'] ?? $registration->first_name,
            'last_name'  => $data['last_name']  ?? $registration->last_name,
            'email'      => $data['email']      ?? $registration->email,
            'phone'      => $data['phone']      ?? $registration->phone,
            'notes'      => $data['notes'] ?? null,
            'total_fee'  => $totalFee,
        ]);

        $registration->guests()->delete();

        foreach ($guests as $guest) {
            EventRegistrationGuest::create([
                'event_registration_id' => $registration->id,
                'first_name'            => $guest['first_name'],
                'last_name'             => $guest['last_name'],
                'fee'                   => $guestFee,
                'notes'                 => $guest['notes'] ?? null,
            ]);
        }

        return $registration->fresh(['guests']);
    }

    public function publicEvent(string $slug, int $tenantId): ?array
    {
        $event = Event::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount('registrations')
            ->first();

        if (! $event) {
            return null;
        }

        return [
            'id'                    => $event->id,
            'name'                  => $event->name,
            'slug'                  => $event->slug,
            'start_datetime'        => $event->start_datetime?->toIso8601String(),
            'end_datetime'          => $event->end_datetime?->toIso8601String(),
            'venue'                 => $event->venue,
            'venue_url'             => $event->venue_url,
            'agenda'                => $event->agenda,
            'registration_process'  => $event->registration_process,
            'ticket_fee'            => (float) $event->ticket_fee,
            'additional_ticket_fee' => (float) $event->additional_ticket_fee,
            'registrations_count'   => $event->registrations_count,
        ];
    }

    private function listItem(Event $event): array
    {
        return [
            'id'                    => $event->id,
            'name'                  => $event->name,
            'slug'                  => $event->slug,
            'start_datetime'        => $event->start_datetime?->toIso8601String(),
            'end_datetime'          => $event->end_datetime?->toIso8601String(),
            'venue'                 => $event->venue,
            'ticket_fee'            => (float) $event->ticket_fee,
            'additional_ticket_fee' => (float) $event->additional_ticket_fee,
            'is_active'             => $event->is_active,
            'registrations_count'   => $event->registrations_count ?? 0,
            'total_paid'            => (float) ($event->total_paid ?? 0),
            'total_outstanding'     => (float) ($event->total_outstanding ?? 0),
        ];
    }

    public function markRegistrationPaid(EventRegistration $registration, int $accountId): EventRegistration
    {
        $registration->update([
            'is_paid'            => true,
            'paid_at'            => now(),
            'company_account_id' => $accountId,
        ]);

        \App\Models\CompanyAccountTransaction::updateOrCreate(
            [
                'model_name'   => 'event_registration',
                'reference_id' => $registration->id,
            ],
            [
                'tenant_id'          => $registration->tenant_id,
                'company_account_id' => $accountId,
                'type'               => 'credit',
                'amount'             => (float) $registration->total_fee,
                'transaction_date'   => now()->toDateString(),
                'notes'              => 'Event registration: ' . $registration->first_name . ' ' . $registration->last_name,
            ]
        );

        return $registration->fresh(['guests']);
    }

    public function toRegistrationItem(EventRegistration $r): array
    {
        return [
            'id'         => $r->id,
            'first_name' => $r->first_name,
            'last_name'  => $r->last_name,
            'email'      => $r->email,
            'phone'      => $r->phone,
            'notes'      => $r->notes,
            'total_fee'  => (float) $r->total_fee,
            'is_paid'    => (bool) $r->is_paid,
            'paid_at'    => $r->paid_at?->toIso8601String(),
            'member'     => $r->member ? [
                'id'           => $r->member->id,
                'name'         => $r->member->name,
                'member_id'    => $r->member->member_id,
                'gender'       => $r->member->gender,
                'phone_number' => $r->member->phone_number,
            ] : null,
            'guests'     => $r->guests->map(fn ($g) => ['first_name' => $g->first_name, 'last_name' => $g->last_name, 'fee' => (float) $g->fee, 'notes' => $g->notes])->all(),
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    private function uniqueSlug(int $tenantId, string $base, ?int $excludeId = null): string
    {
        $slug      = Str::slug($base);
        $original  = $slug;
        $counter   = 1;

        while (true) {
            $exists = Event::where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if (! $exists) {
                break;
            }

            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
