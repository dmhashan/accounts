<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignApiController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->campaigns->meta());
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $search = trim((string) $request->query('search', ''));

        return response()->json($this->campaigns->index($perPage, $search));
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $validated = $request->validate($this->rules());
        $campaign = $this->campaigns->store($validated, $request->file('cover_image'), $request->user());

        return response()->json([
            'message' => 'Campaign created successfully.',
            'data' => ['id' => $campaign->id],
        ], 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        return response()->json([
            'data' => $this->campaigns->show($campaign),
        ]);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $validated = $request->validate($this->rules($campaign->id));
        $campaign = $this->campaigns->update($campaign, $validated, $request->file('cover_image'), $request->user());

        return response()->json([
            'message' => 'Campaign updated successfully.',
            'data' => $this->campaigns->show($campaign),
        ]);
    }

    public function updateStatus(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Campaign::STATUS_DRAFT,
                Campaign::STATUS_PUBLISHED,
                Campaign::STATUS_CLOSED,
            ])],
        ]);

        $this->authorizeStatusChange($request, $validated['status']);

        $campaign = $this->campaigns->updateStatus($campaign, $validated['status'], $request->user());

        return response()->json([
            'message' => 'Campaign status updated successfully.',
            'data' => $this->campaigns->show($campaign),
        ]);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->campaigns->destroy($campaign);

        return response()->json([
            'message' => 'Campaign deleted successfully.',
        ]);
    }

    public function registrations(Request $request, Campaign $campaign): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);
        $search = trim((string) $request->query('search', ''));

        return response()->json($this->campaigns->registrations($campaign, $perPage, $search));
    }

    public function publicShow(string $slug): JsonResponse
    {
        $campaign = $this->campaigns->publicCampaign($slug);

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        return response()->json($campaign);
    }

    public function publicRegister(Request $request, string $slug): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('slug', Str::slug($slug))
            ->first();

        if (!$campaign || $campaign->status === Campaign::STATUS_DRAFT) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        if ($campaign->status === Campaign::STATUS_CLOSED) {
            return response()->json(['message' => 'Sorry, this campaign has finished or is closed.'], 422);
        }

        $member = $this->campaigns->register($campaign, $request);

        return response()->json([
            'message' => 'Thank you for your registration. Your details have been submitted successfully. Our team will review your information and contact you soon.',
            'data' => ['member_id' => $member->id],
        ], 201);
    }

    private function rules(?int $campaignId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('campaigns', 'slug')->ignore($campaignId),
            ],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'field_config' => ['nullable'],
            'document_config' => ['nullable'],
        ];
    }

    private function authorizeStatusChange(Request $request, string $status): void
    {
        $user = $request->user();
        $user?->loadMissing('role');

        $allowed = match ($status) {
            Campaign::STATUS_PUBLISHED => $user?->hasPermission('campaigns.publish') ?? false,
            Campaign::STATUS_CLOSED => $user?->hasPermission('campaigns.close') ?? false,
            Campaign::STATUS_DRAFT => $user?->hasPermission('campaigns.edit') ?? false,
            default => false,
        };

        abort_unless($allowed, 403, 'You do not have permission to change this campaign status.');
    }
}
