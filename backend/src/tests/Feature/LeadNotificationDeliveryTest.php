<?php

namespace Tests\Feature;

use App\Jobs\SendNewLeadMailJob;
use App\Mail\SmtpTestMail;
use App\Models\LeadRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lead_is_queued_with_delivery_audit_fields(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/leads', [
            'name' => 'Иван Иванов',
            'phone' => '+79990000000',
            'email' => 'ivan@example.test',
            'message' => 'Нужен расчёт стоимости.',
            'privacy_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $lead = LeadRequest::query()->firstOrFail();

        $this->assertSame('queued', $lead->email_delivery_status);
        $this->assertSame(config('mail.lead_notify_address'), $lead->email_recipient);
        $this->assertNotNull($lead->email_queued_at);
        Queue::assertPushed(SendNewLeadMailJob::class, fn (SendNewLeadMailJob $job) => $job->leadId === $lead->id
            && $job->deliveryVersion === 1);
    }

    public function test_queued_notification_is_marked_sent_after_successful_smtp_delivery(): void
    {
        Mail::fake();
        $lead = LeadRequest::query()->create([
            'name' => 'Иван Иванов',
            'phone' => '+79990000000',
            'email' => 'ivan@example.test',
            'message' => 'Нужен расчёт стоимости.',
            'status' => 'new',
            'email_delivery_status' => 'queued',
            'email_delivery_version' => 1,
            'email_recipient' => 'manager@example.test',
            'email_queued_at' => now(),
        ]);

        (new SendNewLeadMailJob($lead->id, 1))->handle();

        $lead->refresh();
        $this->assertSame('sent', $lead->email_delivery_status);
        $this->assertSame(1, $lead->email_attempts);
        $this->assertNotNull($lead->email_sent_at);
        Mail::assertSent(\App\Mail\NewLeadMail::class);
    }

    public function test_legacy_serialized_job_is_converted_to_a_versioned_delivery_job(): void
    {
        Queue::fake();
        $lead = LeadRequest::query()->create([
            'name' => 'Иван Иванов',
            'phone' => '+79990000000',
            'email' => 'ivan@example.test',
            'message' => 'Нужен расчёт стоимости.',
            'status' => 'new',
            'email_delivery_status' => 'failed',
            'email_failed_at' => now(),
        ]);
        $legacyJob = new SendNewLeadMailJob();
        $legacyJob->lead = $lead;

        $restoredLegacyJob = unserialize(serialize($legacyJob));
        $restoredLegacyJob->handle();

        $lead->refresh();
        $this->assertSame('queued', $lead->email_delivery_status);
        $this->assertSame(1, $lead->email_delivery_version);
        Queue::assertPushed(SendNewLeadMailJob::class, fn (SendNewLeadMailJob $job) => $job->leadId === $lead->id
            && $job->deliveryVersion === 1);
    }

    public function test_mail_test_command_sends_only_a_static_test_message(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['--to' => 'operator@example.test'])
            ->expectsConfirmation('Send a test email to operator@example.test?', 'yes')
            ->assertSuccessful();

        Mail::assertSent(SmtpTestMail::class, function (SmtpTestMail $mail): bool {
            return $mail->hasTo('operator@example.test');
        });
    }

    public function test_admin_can_retry_a_failed_notification(): void
    {
        Queue::fake();
        $admin = User::factory()->create();
        $lead = LeadRequest::query()->create([
            'name' => 'Иван Иванов',
            'phone' => '+79990000000',
            'email' => 'ivan@example.test',
            'message' => 'Нужен расчёт стоимости.',
            'status' => 'new',
            'email_delivery_status' => 'failed',
            'email_failed_at' => now(),
            'email_error' => 'SMTP unavailable',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/leads/{$lead->id}/retry-email")
            ->assertOk()
            ->assertJsonPath('data.email_delivery_status', 'queued');

        $lead->refresh();
        $this->assertSame('queued', $lead->email_delivery_status);
        $this->assertNull($lead->email_error);
        Queue::assertPushed(SendNewLeadMailJob::class, fn (SendNewLeadMailJob $job) => $job->leadId === $lead->id
            && $job->deliveryVersion === 1);
    }
}
