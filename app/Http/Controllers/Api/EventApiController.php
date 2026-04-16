<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
    public function __construct(private readonly EventService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant  = app('tenant');
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $search  = trim((string) $request->query('search', ''));

        return response()->json($this->service->index($tenant->id, $perPage, $search));
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorizeEvent($event);

        return response()->json($this->service->show($event));
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate($this->rules($tenant->id));
        $validated['slug'] = $validated['slug'] ?? $validated['name'];

        $event = $this->service->store($tenant->id, $validated);

        return response()->json(['message' => 'Event created successfully.', 'data' => ['id' => $event->id]], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->authorizeEvent($event);

        $tenant    = app('tenant');
        $validated = $request->validate($this->rules($tenant->id, $event->id));

        $this->service->update($event, $validated);

        return response()->json(['message' => 'Event updated successfully.']);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorizeEvent($event);
        $this->service->destroy($event);

        return response()->json(['message' => 'Event deleted successfully.']);
    }

    public function registrations(Request $request, Event $event): JsonResponse
    {
        $this->authorizeEvent($event);

        $perPage = min((int) $request->integer('per_page', 20), 100);
        $search  = trim((string) $request->query('search', ''));

        return response()->json($this->service->registrations($event, $perPage, $search));
    }

    public function updateRegistration(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        $this->authorizeEvent($event);
        abort_if($registration->event_id !== $event->id, 404);
        abort_if($registration->is_paid, 422, 'Cannot edit a paid registration.');

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:200'],
            'email'          => ['nullable', 'email', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'guests'         => ['nullable', 'array', 'max:20'],
            'guests.*.name'  => ['required', 'string', 'max:200'],
            'guests.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->service->updateRegistration($registration, $event, $validated);

        return response()->json([
            'message' => 'Registration updated.',
            'data'    => $this->service->toRegistrationItem($updated),
        ]);
    }

    public function destroyRegistration(Event $event, EventRegistration $registration): JsonResponse
    {
        $this->authorizeEvent($event);
        abort_if($registration->event_id !== $event->id, 404);
        abort_if($registration->is_paid, 422, 'Cannot delete a paid registration.');

        $registration->guests()->delete();
        $registration->delete();

        return response()->json(['message' => 'Registration deleted.']);
    }

    public function adminRegister(Request $request, Event $event): JsonResponse
    {
        $this->authorizeEvent($event);

        $tenant = app('tenant');

        $validated = $request->validate([
            'member_id'      => ['nullable', 'integer', 'exists:members,id'],
            'name'           => ['required', 'string', 'max:200'],
            'email'          => ['nullable', 'email', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'guests'         => ['nullable', 'array', 'max:20'],
            'guests.*.name'  => ['required', 'string', 'max:200'],
            'guests.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $memberId = null;
        if (!empty($validated['member_id'])) {
            $member = Member::where('id', $validated['member_id'])
                ->where('tenant_id', $tenant->id)
                ->first();
            abort_if(!$member, 404, 'Member not found.');

            $alreadyRegistered = EventRegistration::where('event_id', $event->id)
                ->where('member_id', $member->id)
                ->exists();
            abort_if($alreadyRegistered, 409, 'This member is already registered for this event.');

            $memberId = $member->id;
        }

        $registration = $this->service->register($event, $tenant->id, $validated, $memberId);

        return response()->json([
            'message' => 'Registration added successfully.',
            'data'    => $this->service->toRegistrationItem($registration->load('guests')),
        ], 201);
    }

    public function markRegistrationPaid(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        $this->authorizeEvent($event);

        abort_if($registration->event_id !== $event->id, 404);
        abort_if($registration->is_paid, 422, 'Registration is already paid.');

        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'exists:company_accounts,id'],
        ]);

        $updated = $this->service->markRegistrationPaid($registration, $validated['account_id']);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data'    => $this->service->toRegistrationItem($updated),
        ]);
    }

    private function authorizeEvent(Event $event): void
    {
        $tenant = app('tenant');
        abort_if($event->tenant_id !== $tenant->id, 403, 'Forbidden.');
    }

    private function rules(int $tenantId, ?int $excludeId = null): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'slug'                   => ['nullable', 'string', 'max:150'],
            'start_datetime'         => ['required', 'date'],
            'end_datetime'           => ['nullable', 'date', 'after:start_datetime'],
            'venue'                  => ['nullable', 'string', 'max:255'],
            'venue_url'              => ['nullable', 'url', 'max:500'],
            'agenda'                 => ['nullable', 'string'],
            'registration_process'   => ['nullable', 'string'],
            'ticket_fee'             => ['nullable', 'numeric', 'min:0'],
            'additional_ticket_fee'  => ['nullable', 'numeric', 'min:0'],
            'is_active'              => ['boolean'],
        ];
    }
}
