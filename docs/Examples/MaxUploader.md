# Примеры Resumable-загрузки

## Простая загрузка различных типов файлов

**Загрузка файла без указания имени:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxUploader;

$apiClient = new MaxApiClient($accessToken);
$uploader = new MaxUploader($apiClient);

$fileToken = $uploader->uploadFile(__FILE__);
$message = NewMessageBody::new()
    ->addFileAttachment($fileToken);
```

**Загрузка файла с указанием имени:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxUploader\Content\File;

$fileToken = $uploader->uploadFile(new File(__FILE__, 'file.txt'));
$message = NewMessageBody::new()
    ->addFileAttachment($fileToken);
```

**Загрузка файла с именем и текстом сообщения:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxUploader\Content\File;

$fileToken = $uploader->uploadFile(new File(__FILE__, 'Документация.txt'));
$message = NewMessageBody::make('Документацию к проекту')
    ->addFileAttachment($fileToken);
```

**Загрузка файла из строки:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxUploader\Content\StringFile;

$fileToken = $uploader->uploadFile(new StringFile('Содержимое файла', 'Отчёт.txt'));
$message = NewMessageBody::make('Прикладываю отчёт')
    ->addFileAttachment($fileToken);
```

**Загрузка файла из ресурса:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxUploader\Content\Stream;

$imageToken = $uploader->uploadFile(new Stream($resource, 'image.webp'));
$message = NewMessageBody::new()
    ->addImageAttachment($fileToken);
```

**Загрузка аудиофайла:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$audioToken = $uploader->uploadAudio(__DIR__ . '/files/audio.mp3');
$message = NewMessageBody::make('Запись лекции')
    ->addAudioAttachment($audioToken);
```

**Загрузка нескольких изображений:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$image1Token = $uploader->uploadImage(__DIR__ . '/files/image.jpeg');
$image2Token = $uploader->uploadImage(__DIR__ . '/files/image.png');
$message = NewMessageBody::make('Фотографии с пикника')
    ->addImageAttachment($image1Token)
    ->addImageAttachment($image2Token);
```

**Загрузка нескольких видеофайлов:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$video1Token = $uploader->uploadVideo(__DIR__ . '/files/video1.mp4');
$video2Token = $uploader->uploadVideo(__DIR__ . '/files/video2.mp4');
$message = NewMessageBody::new()
    ->addVideoAttachment($videoToken)
    ->addVideoAttachment($videoToken);
```

**Загрузка изображения и видео в одном сообщении:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$imageToken = $uploader->uploadImage(__DIR__ . '/files/image.jpeg');
$videoToken = $uploader->uploadVideo(__DIR__ . '/files/video.mp4');
$message = NewMessageBody::new()
    ->addImageAttachment($videoToken)
    ->addVideoAttachment($videoToken);
```

## Получение детальной информации о загруженных файлах

**Расширенная загрузка файла с получением деталей:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxUploader;

$apiClient = new MaxApiClient($accessToken);
$uploader = new MaxUploader($apiClient);

$uploadedFile = $uploader->uploadFileEx(__FILE__);
echo 'FileId: ' . $uploadedFile->getFileId() . PHP_EOL;
echo 'Token: ' . $uploadedFile->getToken() . PHP_EOL;

$message = NewMessageBody::new()
    ->addFileAttachment($uploadedFile->getToken());
```

**Расширенная загрузка изображения с получением деталей:**

```php
$photoTokens = $uploader->uploadImageEx(__DIR__ . '/files/image.jpeg');
foreach ($photoTokens->getPhotos() as $photoId => $photoToken) {
    echo 'PhotoId: ' . $photoId . PHP_EOL;
    echo 'Token: ' . $photoToken->getToken() . PHP_EOL;
}

$photos = $photoTokens->getPhotos();
$imageToken = reset($photos)->getToken();
```

## Контроль скорости загрузки, количества повторных попыток и обработка ошибок

**Настройка лимита низкой скорости (100kb/s) и количества повторных попыток через методы:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxUploader;

$apiClient = new MaxApiClient($accessToken);
$uploader = new MaxUploader($apiClient);

$uploader->setLowSpeedLimit(100 * 1024 * 10);
$uploader->setLowSpeedTime(10);
$uploader->setTimeout(600);
$uploader->setRetryAttempts(10);
```

**Настройка лимита низкой скорости (100kb/s) и количества повторных попыток через свойства:**

```php
$uploader->lowSpeedLimit = 100 * 1024 * 10;
$uploader->lowSpeedTime = 10;
$uploader->timeout = 600;
$uploader->retryAttempts = 10;
```

**Обработка таймаута при загрузке большого файла:**

```php
use Mj4444\SimpleHttpClient\Exceptions\GeneralException;

$uploader->timeout = 1;
$uploader->retryAttempts = 0;

try {
    $videoToken = $uploader->uploadVideo(__DIR__ . '/files/big-video.mp4');
} catch (GeneralException $e) {
    echo 'Message: ' . $e->getMessage()) . PHP_EOL;
    echo 'Code: ' . $e->getCode()) . PHP_EOL;
    if ($e->getCode() === GeneralException::OPERATION_TIMEOUTED) {
        echo 'Timeout' . PHP_EOL;
        
        // Возобновляем загрузку
        $videoToken = $uploader->uploadVideo(__DIR__ . '/files/big-video.mp4', $uploader->getLastMeta());
    }
}
```

## Обработка событий

**Обработка события Event:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxUploader;
use MaxMessenger\Uploader\MaxUploader\DTO\UploadEventType;

$apiClient = new MaxApiClient($accessToken);
$uploader = new MaxUploader($apiClient);

$uploader->setEventCallback(function (UploadEventType $event) {
    // FileInfoRequest — Генерируется перед запросом информации о состоянии загружаемого файла.
    // SendFragment — Генерируется перед началом загрузки очередного фрагмента.
    // Reset — Генерируется при перезапуске загрузки с начала.
    // Resume — Генерируется при возобновлении загрузки.
    // Timeout — Генерируется при получении исключения с таймаутом.
    // UrlRequest — Генерируется перед запросом ссылки для загрузки.
    echo 'Event: '$event->name . PHP_EOL;
});
```

**Обработка события Progress:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxUploader;
use MaxMessenger\Uploader\MaxUploader\DTO\Progress;

$apiClient = new MaxApiClient($accessToken);
$uploader = new MaxUploader($apiClient);

$uploader->setProgressCallback(function (Progress $progress) {
    $allBytesSent = $progress->fragmentOffset + $progress->bytesSent;
    echo "FragmentOffset: $progress->fragmentOffset, "; // Начало текущего загружаемого фрагмента
    echo "FragmentLength: $progress->fragmentLength, "; // Длинна текущего загружаемого фрагмента
    echo "FragmentBytesSent: $progress->bytesSent, "; // Отправлено байт текущего фрагмента
    echo "AllBytesSent: $allBytesSent, "; // Отправлено байт всего от начала файла
    echo "Size: $progress->size\n"; // Общая длина файла
});
```

> Для получения событий Progress, загрузчик (провайдер) контента должен поддерживать это событие.
> Все загрузчики (провайдеры), реализованные в данном пакете, поддерживают это событие.
