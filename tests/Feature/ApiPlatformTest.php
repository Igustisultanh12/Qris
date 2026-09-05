<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Services\Qris\Crc16;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPlatformTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customerA;
    private Customer $customerB;
    private Merchant $merchantA;
    private Merchant $merchantB;
    private string $plainKeyA = 'ka_live_test_key_customer_a_12345';
    private string $plainKeyB = 'ka_live_test_key_customer_b_67890';
    private string $validStaticQris;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        // Customer A setup
        $this->customerA = Customer::create([
            'name' => 'Customer A Corp',
            'email' => 'customera@example.com',
            'status' => 'active',
            'max_merchants' => 5,
        ]);

        Subscription::create([
            'customer_id' => $this->customerA->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'price' => $plan->price,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        ApiKey::create([
            'customer_id' => $this->customerA->id,
            'name' => 'Key A',
            'key_prefix' => 'ka_live_test_a...',
            'key_hash' => hash('sha256', $this->plainKeyA),
            'rate_limit_per_minute' => 60,
            'is_active' => true,
        ]);

        // Customer B setup
        $this->customerB = Customer::create([
            'name' => 'Customer B Corp',
            'email' => 'customerb@example.com',
            'status' => 'active',
            'max_merchants' => 5,
        ]);

        Subscription::create([
            'customer_id' => $this->customerB->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'price' => $plan->price,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        ApiKey::create([
            'customer_id' => $this->customerB->id,
            'name' => 'Key B',
            'key_prefix' => 'ka_live_test_b...',
            'key_hash' => hash('sha256', $this->plainKeyB),
            'rate_limit_per_minute' => 60,
            'is_active' => true,
        ]);

        // Valid Indonesian Static QRIS
        $base = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5923KREATIF SKY ABADI STORE6013JAKARTA PUSAT61051011062070703A016304';
        $this->validStaticQris = $base . Crc16::calculate($base);

        // Merchant A
        $this->merchantA = Merchant::create([
            'customer_id' => $this->customerA->id,
            'merchant_code' => 'MC-TEST-A-001',
            'name' => 'Store A',
            'status' => 'active',
            'fee_mode' => 'charged_to_customer',
        ]);
        $this->merchantA->qrisList()->create([
            'customer_id' => $this->customerA->id,
            'qris_static' => $this->validStaticQris,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Merchant B
        $this->merchantB = Merchant::create([
            'customer_id' => $this->customerB->id,
            'merchant_code' => 'MC-TEST-B-002',
            'name' => 'Store B',
            'status' => 'active',
            'fee_mode' => 'absorbed',
        ]);
        $this->merchantB->qrisList()->create([
            'customer_id' => $this->customerB->id,
            'qris_static' => $this->validStaticQris,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public function test_health_check_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'healthy',
                ],
            ])
            ->assertHeader('X-Request-ID');
    }

    public function test_qris_parse_without_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/qris/parse', [
            'qris' => $this->validStaticQris,
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_qris_parse_with_valid_key_returns_200(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyA)
            ->postJson('/api/v1/qris/parse', [
                'qris' => $this->validStaticQris,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'merchant_name' => 'KREATIF SKY ABADI STORE',
                    'currency' => '360',
                    'method' => 'static',
                ],
            ]);
    }

    public function test_create_dynamic_qris_returns_201_and_transaction_record(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyA)
            ->postJson('/api/v1/qris/dynamic', [
                'merchant_id' => $this->merchantA->merchant_code,
                'amount' => 50000,
                'reference' => 'INV-ORDER-999',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => 50000,
                    'reference' => 'INV-ORDER-999',
                    'status' => 'generated',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customerA->id,
            'merchant_id' => $this->merchantA->id,
            'reference' => 'INV-ORDER-999',
            'amount' => 50000,
            'status' => 'generated',
        ]);
    }

    public function test_idempotency_returns_same_transaction_without_duplication(): void
    {
        $idempotencyKey = 'UNIQUE-CLIENT-IDEMPOTENCY-KEY-12345';

        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->plainKeyA,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/qris/dynamic', [
            'merchant_id' => $this->merchantA->merchant_code,
            'amount' => 75000,
            'reference' => 'ORDER-IDEMPOTENT-1',
        ]);

        $res1->assertStatus(201);
        $txId1 = $res1->json('data.transaction_id');

        // Send identical second request
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->plainKeyA,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/qris/dynamic', [
            'merchant_id' => $this->merchantA->merchant_code,
            'amount' => 75000,
            'reference' => 'ORDER-IDEMPOTENT-1',
        ]);

        $res2->assertSuccessful();
        $txId2 = $res2->json('data.transaction_id');

        $this->assertSame($txId1, $txId2);
        $this->assertSame(1, Transaction::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_security_customer_a_cannot_access_or_generate_for_merchant_b(): void
    {
        // Customer A attempts to generate QR for Merchant B
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyA)
            ->postJson('/api/v1/qris/dynamic', [
                'merchant_id' => $this->merchantB->merchant_code,
                'amount' => 10000,
                'reference' => 'MALICIOUS-CROSS-TENANT',
            ]);

        $response->assertStatus(404); // Merchant not found in Customer A's scope
    }

    public function test_security_customer_b_cannot_view_customer_a_transactions(): void
    {
        // Create transaction for Customer A
        $txA = Transaction::create([
            'customer_id' => $this->customerA->id,
            'merchant_id' => $this->merchantA->id,
            'reference' => 'SECRET-REF-A',
            'amount' => 25000,
            'total' => 25000,
            'qris_static' => $this->validStaticQris,
            'qris_dynamic' => $this->validStaticQris,
            'status' => 'generated',
            'expires_at' => now()->addMinutes(15),
        ]);

        // Customer B attempts to retrieve Customer A's transaction
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyB)
            ->getJson('/api/v1/transactions/' . $txA->transaction_number);

        $response->assertStatus(404);
    }

    public function test_simulate_paid_marks_transaction_as_paid(): void
    {
        // Generate transaction first
        $createRes = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyA)
            ->postJson('/api/v1/qris/dynamic', [
                'merchant_id' => $this->merchantA->merchant_code,
                'amount' => 50000,
                'reference' => 'TEST-SIM-PAID-001',
            ]);

        $createRes->assertStatus(201);
        $txNumber = $createRes->json('data.transaction_id');

        // Call simulate-paid
        $simRes = $this->withHeader('Authorization', 'Bearer ' . $this->plainKeyA)
            ->postJson("/api/v1/qris/{$txNumber}/simulate-paid");

        $simRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('transactions', [
            'transaction_number' => $txNumber,
            'status' => 'paid',
        ]);
    }
}
