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
            
            // КРИТИЧНО для избежания спама: минимальные стандартные заголовки
            // Убираем все кастомные заголовки (X-Mailer, X-Priority, X-System и т.д.) - они могут вызывать подозрения спам-фильтров
            
            // Стандартный заголовок для транзакционных писем
            $headers->addTextHeader('Auto-Submitted', 'auto-generated');
            
            // Приоритет письма - нормальный (не HIGH, чтобы не выглядеть как спам)
            $message->priority(\Symfony\Component\Mime\Email::PRIORITY_NORMAL);
        });
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * Supports two types of deep linking:
     * 1. Universal Links (iOS) / App Links (Android) - uses HTTPS URL
     *    Automatically opens app if installed, falls back to web if not
     * 2. Custom URL Scheme - uses custom scheme (e.g., zenythium://)
     *    Opens app directly, but may show browser prompt if app not installed
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl(mixed $notifiable): string
    {
        $token = $this->token;
        $email = urlencode($notifiable->getEmailForPasswordReset());
        
        // Всегда используем HTTPS URL (Universal Links / App Links)
        // Это работает везде: в почтовых клиентах, браузерах, и автоматически открывает приложение если настроено
        // Если приложение не установлено, откроется веб-версия
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost'));
        
        // Убираем завершающий слэш если есть
        $frontendUrl = rtrim($frontendUrl, '/');
        
        // Формат: https://domain.com/reset-password?token=xxx&email=xxx
        // Эта ссылка будет работать как Universal Link / App Link при правильной настройке
        // В почтовых клиентах кнопки будут работать нормально
        return sprintf(
            '%s/reset-password?token=%s&email=%s',
            $frontendUrl,
            $token,
            $email
        );
    }
}

