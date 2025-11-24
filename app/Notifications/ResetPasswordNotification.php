<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\ResetPasswordMail;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotificationBase;

final class ResetPasswordNotification extends ResetPasswordNotificationBase
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Mail\Mailable
     */
    public function toMail(mixed $notifiable): ResetPasswordMail
    {
        $resetUrl = $this->resetUrl($notifiable);
        $recipientEmail = $notifiable->getEmailForPasswordReset();
        
        // Передаем email получателя в Mailable
        $mailable = new ResetPasswordMail($resetUrl, $recipientEmail);
        
        // Добавляем антиспам заголовки через withSymfonyMessage
        return $mailable->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) use ($notifiable, $recipientEmail) {
            // КРИТИЧНО: обязательно устанавливаем адрес получателя
            // MailChannel не устанавливает адрес автоматически для Mailable из Notification
            // withSymfonyMessage вызывается во время отправки, поэтому устанавливаем адрес здесь
            // Проверяем наличие адреса получателя через итератор
            $toAddresses = $message->getTo();
            $hasRecipient = false;
            foreach ($toAddresses as $address) {
                $hasRecipient = true;
                break;
            }
            
            if (!$hasRecipient) {
                $message->to($recipientEmail);
            }
            
            $headers = $message->getHeaders();
            
            // Получаем домен из APP_URL или используем дефолтный
            $domain = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'zenythium.fitness';
            
            // Генерируем уникальный Message-ID для предотвращения дублирования
            $messageId = sprintf(
                '%s@%s',
                uniqid('zenythium.', true) . '.' . time(),
                $domain
            );
            
            // Устанавливаем Message-ID если его еще нет
            if (!$headers->has('Message-ID')) {
                $headers->addIdHeader('Message-ID', $messageId);
            }
            
            // Заголовки для транзакционных писем (не реклама/рассылка)
            $headers->addTextHeader('Auto-Submitted', 'auto-generated');
            $headers->addTextHeader('X-Auto-Response-Suppress', 'All');
            
            // Заголовки для идентификации письма
            $headers->addTextHeader('X-Mailer', 'Zenythium Fitness Mail System');
            $headers->addTextHeader('X-Entity-Ref-ID', uniqid('zenythium.', true));
            $headers->addTextHeader('X-Email-Type', 'password-reset');
            $headers->addTextHeader('X-System', 'Zenythium Fitness');
            
            // List-Unsubscribe для соответствия стандартам (даже для транзакционных)
            $unsubscribeUrl = config('app.url') . '/unsubscribe?email=' . urlencode($notifiable->getEmailForPasswordReset());
            $headers->addTextHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
            $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            
            // Приоритет письма (HIGH для важных транзакционных)
            $message->priority(\Symfony\Component\Mime\Email::PRIORITY_HIGH);
            
            // Устанавливаем Reply-To для обратной связи
            $fromAddress = config('mail.from.address', 'noreply@' . $domain);
            $message->replyTo($fromAddress);
            
            // Заголовки для улучшения доставляемости
            $headers->addTextHeader('X-Priority', '1');
            
            // Заголовок для идентификации транзакционного письма
            $headers->addTextHeader('X-Transaction-Type', 'password-reset');
        });
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl(mixed $notifiable): string
    {
        // Используем FRONTEND_URL для мобильного приложения, если он задан
        $frontendUrl = env('FRONTEND_URL', env('APP_URL', 'http://localhost'));

        // Убираем завершающий слэш если есть
        $frontendUrl = rtrim($frontendUrl, '/');

        // Для мобильного приложения можно использовать deep link или обычную ссылку
        // Формат: frontend_url/reset-password?token=xxx&email=xxx
        return sprintf(
            '%s/reset-password?token=%s&email=%s',
            $frontendUrl,
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset())
        );
    }
}

