<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Password reset e-mail in the user's site language, linking to /{locale}/reset-password/{token}.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function __construct(#[\SensitiveParameter] string $token, string $locale)
    {
        parent::__construct($token);

        $this->locale = $locale;
    }

    protected function resetUrl($notifiable): string
    {
        return route('password.reset', [
            'locale' => $this->locale,
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('site.auth.reset_mail_subject'))
            ->line(__('site.auth.reset_mail_intro'))
            ->action(__('site.auth.reset_password'), $url)
            ->line(__('site.auth.reset_mail_expire', ['count' => config('auth.passwords.users.expire')]))
            ->line(__('site.auth.reset_mail_ignore'));
    }
}
