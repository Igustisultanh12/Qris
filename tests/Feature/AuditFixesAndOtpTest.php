<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditFixesAndOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_bug_01_web_routes_regex_allows_api_dash_routes(): void
    {
        // /api-keys, /api-credentials, /api-docs should render the SPA view (200 OK)
        $resKeys = $this->get('/api-keys');
        $resKeys->assertStatus(200);

        $resCreds = $this->get('/api-credentials');
        $resCreds->assertStatus(200);

        $resDocs = $this->get('/api-docs');
        $resDocs->assertStatus(200);

        // While non-existent backend API endpoints under /api/... return 404 JSON
        $resApi = $this->getJson('/api/non-existent-endpoint');
        $resApi->assertStatus(404);
    }

    public function test_bug_02_and_06_admin_plan_show_and_destroy(): void
    {
        $admin = User::where('email', 'admin@kreatifskyabadi.co.id')->first();
        $plan = SubscriptionPlan::where('slug', 'business')->first();

        // test show()
        $showRes = $this->actingAs($admin)->getJson("/api/admin/plans/{$plan->id}");
        $showRes->assertStatus(200)->assertJsonPath('data.slug', 'business');

        // create an unused plan
        $newPlan = SubscriptionPlan::create([
            'name' => 'Custom Tier',
            'slug' => 'custom-tier',
            'price' => 500000,
            'billing_cycle' => 'monthly',
            'max_merchants' => 10,
            'max_api_calls_per_month' => 10000,
            'max_transactions_per_month' => 5000,
            'rate_limit_per_minute' => 100,
            'is_active' => true,
        ]);

        // test destroy()
        $delRes = $this->actingAs($admin)->deleteJson("/api/admin/plans/{$newPlan->id}");
        $delRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('subscription_plans', ['id' => $newPlan->id]);
    }

    public function test_bug_03_support_ticket_can_be_viewed_by_integer_id(): void
    {
        $customerUser = User::where('email', 'demo@example.com')->first();
        $ticket = SupportTicket::create([
            'customer_id' => $customerUser->customer_id,
            'user_id' => $customerUser->id,
            'ticket_number' => 'TICK-9999',
            'subject' => 'Kendala Integrasi POS',
            'category' => 'technical',
            'priority' => 'high',
            'status' => 'open',
            'message' => 'Mohon bantuan cek payload QRIS.',
        ]);

        // Access via integer id
        $res = $this->actingAs($customerUser)->getJson("/api/tickets/{$ticket->id}");
        $res->assertStatus(200)->assertJsonPath('data.subject', 'Kendala Integrasi POS');

        // Reply via integer id
        $replyRes = $this->actingAs($customerUser)->postJson("/api/tickets/{$ticket->id}/reply", [
            'message' => 'Ini balasan tambahan.',
        ]);
        $replyRes->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_bug_04_billing_controller_null_customer_and_plan_id_support(): void
    {
        $admin = User::where('email', 'admin@kreatifskyabadi.co.id')->first();
        $this->assertNull($admin->customer);

        // Accessing customer billing endpoints as admin should not trigger 500
        $curRes = $this->actingAs($admin)->getJson('/api/customer/billing/current');
        $curRes->assertStatus(200)->assertJsonPath('data.subscription', null);

        $invRes = $this->actingAs($admin)->getJson('/api/customer/billing/invoices');
        $invRes->assertStatus(200)->assertJsonPath('data.data', []);

        // Customer creating invoice using plan_id
        $customerUser = User::where('email', 'demo@example.com')->first();
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();

        $createRes = $this->actingAs($customerUser)->postJson('/api/customer/billing/invoices/create', [
            'plan_id' => $proPlan->id,
        ]);
        $createRes->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_customer_registration_requires_email_otp(): void
    {
        $payload = [
            'name' => 'Ahmad Fauzi',
            'business_name' => 'Fauzi Coffee & Roastery',
            'email' => 'ahmad.fauzi@example.com',
            'phone' => '081299998888',
            'password' => 'PasswordAhmad2026!',
            'password_confirmation' => 'PasswordAhmad2026!',
        ];

        // 1. Register
        $regRes = $this->postJson('/api/auth/register', $payload);
        $regRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.otp_required', true)
            ->assertJsonPath('data.email', 'ahmad.fauzi@example.com');

        $user = User::where('email', 'ahmad.fauzi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertEquals('pending', $user->status);

        // 2. Login before verification should return otp_required
        $loginBefore = $this->postJson('/api/auth/login', [
            'email' => 'ahmad.fauzi@example.com',
            'password' => 'PasswordAhmad2026!',
        ]);
        $loginBefore->assertStatus(200)->assertJsonPath('data.otp_required', true);

        // 3. Verify with invalid OTP should fail
        $verifyFail = $this->postJson('/api/auth/verify-otp', [
            'email' => 'ahmad.fauzi@example.com',
            'otp' => '000000',
        ]);
        $verifyFail->assertStatus(422);

        // 4. Verify with cached OTP
        $cachedOtp = Cache::get("email_otp_ahmad.fauzi@example.com");
        $this->assertNotNull($cachedOtp);

        $verifySuccess = $this->postJson('/api/auth/verify-otp', [
            'email' => 'ahmad.fauzi@example.com',
            'otp' => $cachedOtp,
        ]);
        $verifySuccess->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);

        // 5. Newly verified user has no active subscription -> dashboard returns null subscription
        $dashRes = $this->actingAs($user)->getJson('/api/customer/dashboard');
        $dashRes->assertStatus(200)->assertJsonPath('data.subscription', null);
    }
}
