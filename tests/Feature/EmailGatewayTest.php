<?php

namespace Tests\Feature;

use App\Mail\TestEmailMailable;
use App\Mail\WelcomeCustomerMailable;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\EmailGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::where('email', 'admin@kreatifskyabadi.co.id')->first();
    }

    public function test_it_retrieves_email_gateway_configuration(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/email-gateway');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'mailer',
                    'host',
                    'port',
                    'username',
                    'from_address',
                    'from_name',
                    'is_active',
                ],
            ]);
    }

    public function test_it_updates_email_gateway_configuration(): void
    {
        $payload = [
            'mailer' => 'smtp',
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'testuser',
            'password' => 'secret123',
            'encryption' => 'tls',
            'from_address' => 'system@kreatifskyabadi.co.id',
            'from_name' => 'Qmis Mail System',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/email-gateway', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.host', 'smtp.mailtrap.io')
            ->assertJsonPath('data.port', 2525)
            ->assertJsonPath('data.from_address', 'system@kreatifskyabadi.co.id');

        $this->assertEquals('smtp.mailtrap.io', Setting::get('mail_host'));
        $this->assertEquals(2525, Setting::get('mail_port'));
    }

    public function test_it_sends_test_email_via_email_gateway(): void
    {
        Mail::fake();

        $service = app(EmailGatewayService::class);
        $result = $service->sendTestEmail('target@example.com');

        $this->assertTrue($result['success']);
        Mail::assertSent(TestEmailMailable::class, function ($mail) {
            return $mail->recipientEmail === 'target@example.com';
        });
    }

    public function test_it_dispatches_welcome_email_on_registration(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Budi Santoso',
            'business_name' => 'Toko Barokah Budi',
            'email' => 'budi.barokah@example.com',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $payload);
        $response->assertStatus(201);

        Mail::assertQueued(WelcomeCustomerMailable::class, function ($mail) {
            return $mail->user->email === 'budi.barokah@example.com';
        });
    }
}
