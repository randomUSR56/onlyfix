<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ticketId = $this->ticket->id;

        return (new MailMessage)
            ->subject("Ticket #{$ticketId} Status Updated")
            ->greeting("Hello {$notifiable->name},")
            ->line("The status of ticket #{$ticketId} has been updated.")
            ->line("**Previous status:** {$this->oldStatus}")
            ->line("**New status:** {$this->newStatus}")
            ->line("**Description:** {$this->ticket->description}")
            ->action('View Ticket', url("/tickets/{$ticketId}"))
            ->line('Thank you for using OnlyFix!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
