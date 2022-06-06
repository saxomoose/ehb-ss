<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PINCodeNotification extends Notification
{
    use Queueable;

    public $pinCode;
    public $userId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($pinCode, $userId)
    {
        $this->pinCode = $pinCode; 
        $this->userId = $userId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->view("vendor.notifications.email", [
                        'pinCode' => $this->pinCode,
                        'userId' => $this->userId
                    ])
                    ->subject('Hexclan - Activate your account');
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
            //
        ];
    }
}
