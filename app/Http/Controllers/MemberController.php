<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    /**
     * Show a public profile for a member by username (limited data).
     */
    public function publicProfile($username)
    {
        $tenant = app('tenant');
        $member = Member::where('tenant_id', $tenant->id)
            ->where('username', $username)
            ->where('is_active', true)
            ->with('user')
            ->firstOrFail();

        // Only expose limited fields
        $publicData = [
            'name' => $member->name,
            'username' => $member->username,
            'gender' => $member->gender,
            'joined_date' => $member->joined_date,
            'member_role' => $member->member_role,
            'email' => $member->email,
            'phone_number' => $member->phone_number,
        ];

        // Assigned workout plans with full program details
        $assignedWorkouts = \App\Models\WorkoutProgramAssignment::with([
            'assignedProgram.creator',
            'assignedProgram.days.dayExercises.exercise',
            'assignedProgram.extras',
        ])
            ->where('tenant_id', $tenant->id)
            ->where('member_id', $member->id)
            ->orderByDesc('effective_date')
            ->get();

        // Sales/Finance
        $sales = \App\Models\Sale::where('tenant_id', $tenant->id)
            ->where('customer_member_id', $member->id)
            ->orderByDesc('created_at')
            ->with(['items.product', 'items.variation'])
            ->get();
        $totalOutstanding = $sales->where('is_paid', false)->sum('balance');

        // Pre-format workout data for Alpine.js preview modals
        $workoutsData = $assignedWorkouts->map(function ($assignment) {
            $program = $assignment->assignedProgram;
            return [
                'title' => $program->title ?? 'N/A',
                'duration_weeks' => $program->duration_weeks,
                'creator_name' => $program->creator->name ?? null,
                'effective_date' => $assignment->effective_date?->format('Y-m-d'),
                'days' => ($program->days ?? collect())->map(function ($day) {
                    return [
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'exercises' => $day->dayExercises->map(fn ($ex) => [
                            'exercise_name' => $ex->exercise->name ?? 'Exercise',
                            'w1_w3_exercise' => $ex->w1_w3_exercise,
                            'w2_w4_exercise' => $ex->w2_w4_exercise,
                            'sets' => $ex->sets,
                            'reps' => $ex->reps,
                            'tempo' => $ex->tempo,
                            'rest_seconds' => $ex->rest_seconds,
                        ])->values(),
                    ];
                })->values(),
                'extras' => ($program->extras ?? collect())->map(fn ($e) => [
                    'type' => $e->type,
                    'exercise_name' => $e->exercise_name,
                    'sets' => $e->sets,
                    'reps_or_time' => $e->reps_or_time,
                    'rest' => $e->rest,
                    'notes' => $e->notes,
                    'frequency_per_week' => $e->frequency_per_week,
                    'duration_minutes' => $e->duration_minutes,
                    'cardio_type' => $e->cardio_type,
                ])->values(),
            ];
        })->values();

        // Pre-format sales data for Alpine.js preview modals
        $salesData = $sales->map(fn ($sale) => [
            'id' => $sale->id,
            'created_at' => $sale->created_at->format('Y-m-d'),
            'customer_name' => $sale->customer_name,
            'customer_type' => $sale->customer_type,
            'payment_method' => $sale->payment_method,
            'reference_number' => $sale->reference_number,
            'total_amount' => number_format($sale->total_amount, 2),
            'paid_amount' => number_format($sale->paid_amount, 2),
            'balance' => number_format($sale->balance, 2),
            'is_paid' => $sale->is_paid,
            'items' => $sale->items->map(fn ($item) => [
                'product_name' => $item->product->name ?? '-',
                'variation_name' => $item->variation->name ?? null,
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2),
                'subtotal' => number_format($item->subtotal, 2),
            ])->values(),
        ])->values();

        return view('members.public-profile', [
            'member' => $member,
            'publicData' => $publicData,
            'assignedWorkouts' => $assignedWorkouts,
            'sales' => $sales,
            'totalOutstanding' => $totalOutstanding,
            'workoutsData' => $workoutsData,
            'salesData' => $salesData,
        ]);
    }
    public function index()
    {
        $members = Member::where('tenant_id', app('tenant')->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('members.index', compact('members'));
    }

    public function create()
    {
        $generatedMemberId = Member::generateMemberId();

        return view('members.create', compact('generatedMemberId'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('members')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'gender' => 'required|in:male,female',
            'email' => [
                'required',
                'email',
                Rule::unique('members')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'phone_number' => 'required|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'age' => 'required|integer|min:1|max:120',
            'address' => 'nullable|string|max:1000',
            'member_role' => 'required|string|max:50',
            'admission_fee' => 'nullable|numeric|min:0',
            'payment_plan' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'joined_date' => 'required|date',
            'comment' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Generate member ID server-side and compose full name
        $validated['member_id'] = Member::generateMemberId();
        $validated['tenant_id'] = $tenant->id;
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true; // Admin-created members are verified

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('member-photos', 'public');
        }

        // Create member
        $member = Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    public function edit(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        if (!$member->first_name || !$member->last_name) {
            $parts = preg_split('/\s+/', trim($member->name ?? ''), 2);
            $member->first_name = $member->first_name ?: ($parts[0] ?? '');
            $member->last_name = $member->last_name ?: ($parts[1] ?? '');
        }

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $tenant = app('tenant');

        $memberUsernameRule = Rule::unique('members')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
            ->ignore($member->id);

        $memberEmailRule = Rule::unique('members')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
            ->ignore($member->id);

        $userUsernameRule = Rule::unique('users')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id));

        $userEmailRule = Rule::unique('users')
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id));

        if ($member->user_id) {
            $userUsernameRule = $userUsernameRule->ignore($member->user_id);
            $userEmailRule = $userEmailRule->ignore($member->user_id);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                $memberUsernameRule,
                $userUsernameRule,
            ],
            'gender' => 'required|in:male,female',
            'email' => [
                'required',
                'email',
                $memberEmailRule,
                $userEmailRule,
            ],
            'phone_number' => 'required|string|max:20',
            'nic' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'age' => 'required|integer|min:1|max:120',
            'address' => 'nullable|string|max:1000',
            'member_role' => 'required|string|max:50',
            'admission_fee' => 'nullable|numeric|min:0',
            'payment_plan' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'joined_date' => 'required|date',
            'comment' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

        if ($request->hasFile('profile_photo')) {
            if ($member->profile_photo_path) {
                Storage::disk('public')->delete($member->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('member-photos', 'public');
        }

        $member->update($validated);

        // Update linked user email and name
        if ($member->user) {
            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
            ]);
        }

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function toggleStatus(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $member->update([
            'is_active' => !$member->is_active
        ]);

        $status = $member->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('members.index')
            ->with('success', "Member {$status} successfully.");
    }

    public function toggleVerification(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        $member->update([
            'is_verified' => !$member->is_verified
        ]);

        $status = $member->is_verified ? 'verified' : 'unverified';
        
        return redirect()->route('members.index')
            ->with('success', "Member {$status} successfully.");
    }

    public function destroy(Member $member)
    {
        // Ensure member belongs to current tenant
        if ($member->tenant_id !== app('tenant')->id) {
            abort(403);
        }

        // Delete linked user if exists
        if ($member->user) {
            $member->user->delete();
        }

        if ($member->profile_photo_path) {
            Storage::disk('public')->delete($member->profile_photo_path);
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    public function profile()
    {
        // For members, show only their own profile
        $member = Member::where('tenant_id', app('tenant')->id)
            ->where('user_id', auth()->id())
            ->with('user')
            ->firstOrFail();
        
        return view('members.profile', compact('member'));
    }
}
