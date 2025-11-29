<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;

class FirebaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $deviceToken;
    protected $data; // Additional data payload

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $message
     * @param string|array $deviceToken Single token or array of tokens
     * @param array $data Additional data payload (optional)
     */
    public function __construct($title, $message, $deviceToken, array $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->deviceToken = $deviceToken;
        $this->data = $data;
        
        // Configure queue settings
        $this->onQueue('notifications');
        $this->delay(now()->addSeconds(5)); // Small delay to prevent queue flooding
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'device_token' => $this->deviceToken,
            'data' => $this->data,
        ];
    }

    /**
     * Send notification to Firebase
     *
     * @return void
     */
    public function sendToFirebase()
    {
        try {
            if (is_array($this->deviceToken)) {
                // Batch sending for multiple tokens (up to 500)
                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $this->title,
                        'body' => $this->message,
                    ])
                    ->withData($this->data);

                $report = Firebase::messaging()->sendMulticast($message, $this->deviceToken);
                
                // Log failures
                foreach ($report->failures()->getItems() as $failure) {
                    Log::error('Firebase notification failed: ' . $failure->error()->getMessage());
                }
            } else {
                // echo "test";die;
                // Single device notification
                $message = CloudMessage::withTarget('token', $this->deviceToken)
                    ->withNotification([
                        'title' => $this->title,
                        'body' => $this->message,
                    ])
                    ->withData($this->data);

                Firebase::messaging()->send($message);
            }
        } catch (MessagingException $e) {
            Log::error('Firebase messaging error: ' . $e->getMessage());
            // Re-throw to allow job retries
            throw $e;
        }
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addMinutes(10);
    }
}