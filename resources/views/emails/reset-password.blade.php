<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Сброс пароля - Zenythium</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .email-body {
            padding: 40px 30px;
            color: #333333;
        }
        .email-body p {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #555555;
        }
        .reset-button {
            display: inline-block;
            margin: 30px 0;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #666666;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #999999;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            .reset-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Zenythium</h1>
        </div>
        
        <div class="email-body">
            <p style="font-size: 18px; font-weight: 600; color: #333333; margin-bottom: 10px;">
                Здравствуйте!
            </p>
            
            <p>
                Вы получили это письмо, потому что мы получили запрос на сброс пароля для вашей учетной записи.
            </p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Сбросить пароль
                </a>
            </div>
            
            <div class="info-box">
                <p>
                    <strong>⚠️ Важно:</strong> Эта ссылка для сброса пароля действительна в течение 60 минут.
                </p>
            </div>
            
            <p style="font-size: 14px; color: #999999;">
                Если у вас возникли проблемы с кнопкой выше, скопируйте и вставьте следующую ссылку в адресную строку браузера:
            </p>
            
            <p style="word-break: break-all; font-size: 12px; color: #667eea; background-color: #f8f9fa; padding: 12px; border-radius: 4px; margin: 15px 0;">
                {{ $resetUrl }}
            </p>
            
            <div class="divider"></div>
            
            <p style="font-size: 14px; color: #999999; margin-bottom: 0;">
                Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо. Ваш пароль останется без изменений.
            </p>
        </div>
        
        <div class="footer">
            <p style="margin-bottom: 10px;">
                <strong style="color: #667eea;">Zenythium</strong>
            </p>
            <p style="margin: 0;">
                © {{ date('Y') }} Zenythium. Все права защищены.
            </p>
        </div>
    </div>
</body>
</html>

