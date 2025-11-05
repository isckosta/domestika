<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $type,
        public array $data = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Map notification types to email templates
            $emailTemplates = [
                'service_request.matched' => \App\Mail\ServiceRequestMatchedMail::class,
                'professional.response' => \App\Mail\ProfessionalResponseMail::class,
                'service_request.completed' => \App\Mail\ServiceRequestCompletedMail::class,
            ];

            $mailClass = $emailTemplates[$this->type] ?? null;

            if ($mailClass && class_exists($mailClass)) {
                Mail::to($this->user->email)->send(new $mailClass($this->data));

                Log::info('Notification email sent', [
                    'user_id' => $this->user->id,
                    'type' => $this->type,
                ]);
            } else {
                Log::warning('No email template found for notification type', [
                    'type' => $this->type,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending notification email', [
                'user_id' => $this->user->id,
                'type' => $this->type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

