<?php

namespace App\Services;

use App\Jobs\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notifications to matched professionals.
     */
    public function notifyMatchedProfessionals(ServiceRequest $request, array $matches): void
    {
        DB::transaction(function () use ($request, $matches) {
            foreach ($matches as $match) {
                $professional = $match['professional'];
                $user = $professional->user;

                // Create internal notification
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'service_request.matched',
                    'title' => 'Nova solicitação de serviço compatível',
                    'message' => "Uma nova solicitação de {$request->category} foi encontrada e você está entre os profissionais recomendados.",
                    'data' => [
                        'service_request_id' => $request->id,
                        'category' => $request->category,
                        'score' => $match['score'],
                        'urgency' => $request->urgency,
                    ],
                ]);

                // Queue email notification
                dispatch(new SendNotificationEmailJob($user, 'service_request.matched', [
                    'service_request' => $request,
                    'score' => $match['score'],
                ]));

                // Log activity
                activity()
                    ->performedOn($request)
                    ->causedBy($user)
                    ->withProperties([
                        'professional_id' => $professional->id,
                        'score' => $match['score'],
                    ])
                    ->log('service_request.notified');
            }

            Log::info('Matched professionals notified', [
                'service_request_id' => $request->id,
                'professionals_count' => count($matches),
            ]);
        });
    }

    /**
     * Notify contractor when a professional responds.
     */
    public function notifyContractorOfResponse(ServiceRequest $request, Professional $professional): void
    {
        $contractor = $request->user;

        Notification::create([
            'user_id' => $contractor->id,
            'type' => 'professional.response',
            'title' => 'Profissional interessado',
            'message' => "{$professional->user->name} demonstrou interesse na sua solicitação de {$request->category}.",
            'data' => [
                'service_request_id' => $request->id,
                'professional_id' => $professional->id,
            ],
        ]);

        dispatch(new SendNotificationEmailJob($contractor, 'professional.response', [
            'service_request' => $request,
            'professional' => $professional,
        ]));
    }

    /**
     * Notify users when a new chat message is received.
     */
    public function notifyChatMessage(User $receiver, ServiceRequest $request, User $sender): void
    {
        Notification::create([
            'user_id' => $receiver->id,
            'type' => 'chat.message',
            'title' => 'Nova mensagem',
            'message' => "Você recebeu uma nova mensagem de {$sender->name}.",
            'data' => [
                'service_request_id' => $request->id,
                'sender_id' => $sender->id,
            ],
        ]);
    }

    /**
     * Notify professional when request is completed.
     */
    public function notifyRequestCompleted(ServiceRequest $request): void
    {
        $responses = $request->responses()->where('status', 'accepted')->get();

        foreach ($responses as $response) {
            $professional = $response->professional;
            $user = $professional->user;

            Notification::create([
                'user_id' => $user->id,
                'type' => 'service_request.completed',
                'title' => 'Serviço concluído',
                'message' => "A solicitação de {$request->category} foi marcada como concluída.",
                'data' => [
                    'service_request_id' => $request->id,
                ],
            ]);
        }
    }
}

