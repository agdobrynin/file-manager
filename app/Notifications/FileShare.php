<?php

namespace App\Notifications;

use App\Models\File;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FileShare extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<File>  $files
     */
    public function __construct(public readonly Collection $files, public readonly User $formUser)
    {
    }

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
        $simpleMessage = (new MailMessage)
            ->greeting('Hello '.$notifiable->name.'.')
            ->line('User '.$this->formUser->name.
                ' share for you '.
                $this->files->count().
                Str::plural(' item', $this->files->count()).':');

        $fileList = [];

        foreach ($this->files as $file) {
            $fileList[] = '* '.($file->isFolder() ? 'Folder' : 'File').
                ' "['.$file->name.']('.route('share_for_me.index', ['search' => $file->name]).')"';
        }

        return $simpleMessage->lines($fileList)
            ->action('Goto section "Share for me"', route('share_for_me.index'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
