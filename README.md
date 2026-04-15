# Max Uploader For PHP


<img src="docs/Images/bot-icon.webp" align="left">

Этот пакет предназначен для загрузки файлов на сервера для Max API в полностью объектно-ориентированном формате.<br>
**Multipart upload** и **Resumable upload** методы.<br>
Полный контроль над загрузкой.

**Актуальность:** 15 апреля 2026 г.

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxUploader;

$apiClient = new MaxApiClient('your-access-token');
$uploader = new MaxUploader($apiClient);

$fileToken = $uploader->uploadFile(__FILE__);
$message = NewMessageBody::new()
    ->addFileAttachment($fileToken);
$apiClient->sendMessageToUser(12345678, $message);
```

> [!WARNING]
> По поводу ошибок в клиенте, пожалуйста обращайтесь ко мне напрямую:
>   - Max: [Евгений](https://max.ru/u/f9LHodD0cOID7ezkLpMv_5wNX9YmRCmk-0bp4q1uWCRtrdClF9F21Buxhyk)
>   - Telegram: [mj4444ru](https://t.me/mj4444ru)

## Основные особенности

- Реализована загрузка файлов на сервера обоими поддерживаемыми способами.
- Возможность вести логи, следить за происходящими процессами, следить за прогрессом загрузки.
- Это полностью объектно-ориентированный код без array shapes (object-like arrays).
- Для загрузки файлов не требуется изучение официального API.
- В большинстве случаев для понимания работы, Вам достаточно будет посмотреть [примеры кода](./docs/Examples/README.md).
- Входные и выходные данные валидируются.
- Весь функционал разбит на слои, каждый слой может быть частично или полностью заменён Вашей реализацией.

## Способы загрузки

- **Multipart upload** — Более простой, но менее надёжный способ загрузки (реализован классом `MaxSimpleUploader`).
- **Resumable upload** — Рекомендуемый способ загрузки (реализован классом `MaxUploader`).

## Документация в коде

I believe that in-code documentation should be in English. However, due to a lack of resources to translate
the documentation into English, the in-code documentation is presented in Russian.

Я считаю, что документация в публичном коде должна быть на английском языке. Однако из-за нехватки ресурсов для перевода
документации на английский язык, документация в коде представлена на русском языке.

## Установка

```bash
composer require max-messenger-bot/max-uploader-php
```

### Требования

- PHP 8.2+

### Зависимости

- `max-messenger-bot/max-bot-api-php` 0.1.*
- `mj4444/simple-http-client` ^0.2.0 — HTTP-клиент для выполнения запросов

## Примеры

Больше примеров смотрите в документации в разделе [примеры](docs/Examples/README.md).

## Документация

- [Примеры Multipart-загрузки](docs/Examples/MaxSimpleUploader.md)
- [Примеры Resumable-загрузки](docs/Examples/MaxUploader.md)
- [Интерфейс ContentInterface](docs/ContentInterface.md)
