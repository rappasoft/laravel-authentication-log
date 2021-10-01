<?php

namespace Rappasoft\LaravelAuthenticationLog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class NewDevice extends Notification implements ShouldQueue
{
    use Queueable;

    public AuthenticationLog $authenticationLog;

    public function __construct(AuthenticationLog $authenticationLog)
    {
        $this->authenticationLog = $authenticationLog;
    }

    public function via($notifiable)
    {
        return $notifiable->notifyAuthenticationLogVia();
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(__('Your :app account logged in from a new device.', ['app' => config('app.name')]))
            ->markdown('authentication-log::emails.new', [
                'account' => $notifiable,
                'time' => $this->authenticationLog->login_at,
                'ipAddress' => $this->authenticationLog->ip_address,
                'browser' => $this->authenticationLog->user_agent,
                'location' => $this->authenticationLog->location,
            ]);
    }

    public function toSlack($notifiable)
    {
        return (new SlackMessage())
            ->from(config('app.name'))
            ->warning()
            ->content(__('Your :app account logged in from a new device.', ['app' => config('app.name')]))
            ->attachment(function ($attachment) use ($notifiable) {
                $attachment->fields([
                    __('Account') => $notifiable->email,
                    __('Time') => $this->authenticationLog->login_at->toCookieString(),
                    __('IP Address') => $this->authenticationLog->ip_address,
                    __('Browser') => $this->authenticationLog->user_agent,
                    __('Location') =>
                        $this->authenticationLog->location &&
                        $this->authenticationLog->location['default'] === false ?
                            ($this->authenticationLog->location['city'] ?? 'N/A') . ', ' . ($this->authenticationLog->location['state'] ?? 'N/A') :
                            'Unknown',
                ]);
            });
    }

    public function toNexmo($notifiable)
    {
        return (new NexmoMessage())
            ->content(__('Your :app account logged in from a new device.', ['app' => config('app.name')]));
    }
}
