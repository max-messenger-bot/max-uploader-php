# Примеры Multipart-загрузки

## Простая загрузка различных типов файлов

**Загрузка файла без указания имени:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxSimpleUploader;

$apiClient = new MaxApiClient($accessToken);
$simpleUploader = new MaxSimpleUploader($apiClient);

$fileToken = $simpleUploader->uploadFile(__FILE__);
$message = NewMessageBody::new()
    ->addFileAttachment($fileToken);
```

**Загрузка файла с указанием имени:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxSimpleUploader\File;

$fileToken = $simpleUploader->uploadFile(new File(__FILE__, 'file.txt'));
$message = NewMessageBody::new()
    ->addFileAttachment($fileToken);
```

**Загрузка файла с именем и текстом сообщения:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxSimpleUploader\File;

$fileToken = $simpleUploader->uploadFile(new File(__FILE__, 'Документация.txt'));
$message = NewMessageBody::make('Документацию к проекту')
    ->addFileAttachment($fileToken);
```

**Загрузка файла из строки:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxSimpleUploader\StringFile;

$fileToken = $simpleUploader->uploadFile(new StringFile('Содержимое файла', 'Отчёт.txt'));
$message = NewMessageBody::make('Прикладываю отчёт')
    ->addFileAttachment($fileToken);
```

**Загрузка аудиофайла:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$audioToken = $simpleUploader->uploadAudio(__DIR__ . '/files/audio.mp3');
$message = NewMessageBody::make('Запись лекции')
    ->addAudioAttachment($audioToken);
```

**Загрузка нескольких изображений:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$image1Token = $simpleUploader->uploadImage(__DIR__ . '/files/image.jpeg');
$image2Token = $simpleUploader->uploadImage(__DIR__ . '/files/image.png');
$message = NewMessageBody::make('Фотографии с пикника')
    ->addImageAttachment($image1Token)
    ->addImageAttachment($image2Token);
```

**Загрузка нескольких видеофайлов:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$video1Token = $simpleUploader->uploadVideo(__DIR__ . '/files/video1.mp4');
$video2Token = $simpleUploader->uploadVideo(__DIR__ . '/files/video2.mp4');
$message = NewMessageBody::new()
    ->addVideoAttachment($videoToken)
    ->addVideoAttachment($videoToken);
```

**Загрузка изображения и видео в одном сообщении:**

```php
use MaxMessenger\Bot\Models\Requests\NewMessageBody;

$imageToken = $simpleUploader->uploadImage(__DIR__ . '/files/image.jpeg');
$videoToken = $simpleUploader->uploadVideo(__DIR__ . '/files/video.mp4');
$message = NewMessageBody::new()
    ->addImageAttachment($videoToken)
    ->addVideoAttachment($videoToken);
```

## Получение детальной информации о загруженных файлах

**Расширенная загрузка файла с получением деталей:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Models\Requests\NewMessageBody;
use MaxMessenger\Uploader\MaxSimpleUploader;

$apiClient = new MaxApiClient($accessToken);
$simpleUploader = new MaxSimpleUploader($apiClient);

$uploadedFile = $simpleUploader->uploadFileEx(__FILE__);
echo 'FileId: ' . $uploadedFile->getFileId() . PHP_EOL;
echo 'Token: ' . $uploadedFile->getToken() . PHP_EOL;

$message = NewMessageBody::new()
    ->addFileAttachment($uploadedFile->getToken());
```

**Расширенная загрузка изображения с получением деталей:**

```php
$photoTokens = $simpleUploader->uploadImageEx(__DIR__ . '/files/image.jpeg');
foreach ($photoTokens->getPhotos() as $photoId => $photoToken) {
    echo 'PhotoId: ' . $photoId . PHP_EOL;
    echo 'Token: ' . $photoToken->getToken() . PHP_EOL;
}

$photos = $photoTokens->getPhotos();
$imageToken = reset($photos)->getToken();
```

## Контроль скорости загрузки и обработка ошибок

**Настройка лимита низкой скорости (100kb/s) через методы:**

```php
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Uploader\MaxSimpleUploader;

$apiClient = new MaxApiClient($accessToken);
$simpleUploader = new MaxSimpleUploader($apiClient);

$simpleUploader->setLowSpeedLimit(100 * 1024 * 10);
$simpleUploader->setLowSpeedTime(10);
```

**Настройка лимита низкой скорости (100kb/s) через свойства:**

```php
$simpleUploader->lowSpeedLimit = 100 * 1024 * 10;
$simpleUploader->lowSpeedTime = 10;
```

**Обработка таймаута при загрузке большого файла:**

```php
use Mj4444\SimpleHttpClient\Exceptions\GeneralException;

$simpleUploader->lowSpeedLimit = PHP_INT_MAX;
$simpleUploader->lowSpeedTime = 1;

try {
    $videoToken = $simpleUploader->uploadVideo(__DIR__ . '/files/big-video.mp4');
} catch (GeneralException $e) {
    echo 'Message: ' . $e->getMessage()) . PHP_EOL;
    echo 'Code: ' . $e->getCode()) . PHP_EOL;
    if ($e->getCode() === GeneralException::OPERATION_TIMEOUTED) {
        echo 'Timeout' . PHP_EOL;
    }
}
```
