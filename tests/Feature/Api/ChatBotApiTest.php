<?php

namespace Tests\Feature\Api;

use App\Models\MemberAttendance;
use App\Models\MemberPayment;
use Illuminate\Support\Carbon;

class ChatBotApiTest extends ApiRouteTestCase
{
    public function testChatbotRequiresAuthentication(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'hello',
        ]);

        $response->assertStatus(401);
    }

    public function testChatbotValidatesInput(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/chatbot/message', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function testChatbotReturnsPredictedIncomeFallback(): void
    {
        config(['services.gemini.key' => null]);
        $this->actingAsUser();

        // Create some members with plans to populate MRR calculations
        $plan = $this->createPaymentPlan([
            'price' => 2000,
            'duration_value' => 1,
            'duration_unit' => 'month',
        ]);

        $this->createMember(attributes: [
            'payment_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $this->createMember(attributes: [
            'payment_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        // Create a historical payment
        $member = $this->createMember(attributes: ['is_active' => false]);
        MemberPayment::create([
            'member_id' => $member->id,
            'amount' => 1500,
            'payment_date' => Carbon::now()->subMonth()->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'what is predicted income for next month?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['answer', 'gemini_connected'])
            ->assertJson([
                'gemini_connected' => false, // key is empty in test env
            ]);

        $answer = $response->json('answer');
        $this->assertStringContainsString('Predicted Income Forecast', $answer);
        $this->assertStringContainsString('LKR 4,000.00', $answer); // 2 active members with 2000 plan
    }

    public function testChatbotReturnsBestMemberFallback(): void
    {
        config(['services.gemini.key' => null]);
        $this->actingAsUser();

        // Create a member with high attendance
        $member1 = $this->createMember();

        for ($i = 0; $i < 5; $i++) {
            MemberAttendance::create([
                'member_id' => $member1->id,
                'attended_date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        // Create another member with high payments
        $member2 = $this->createMember();
        MemberPayment::create([
            'member_id' => $member2->id,
            'amount' => 12500,
            'payment_date' => Carbon::now()->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'who is the best member and reason it?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['answer', 'gemini_connected']);

        $answer = $response->json('answer');
        $this->assertStringContainsString('Best Member Evaluation', $answer);
        $this->assertStringContainsString($member1->name, $answer); // Should highlight him for attendance
        $this->assertStringContainsString($member2->name, $answer); // Should highlight him for financial contribution
    }
}
