# Файли

## Завантаження на сервер

Файли потрапляють до застосунку кількома шляхами; усі вони зрештою стають `Core\Http\Message\UploadedFile`
(PSR-7 `UploadedFileInterface` зі зручнішим API).

| Як клієнт надсилає | Як отримати |
|---|---|
| HTML-форма / `multipart/form-data` через **POST** | `$request->getUploadedFile('avatar')` |
| `multipart/form-data` через **PUT / PATCH** (PHP його не розбирає, це робить фреймворк) | так само |
| кілька файлів `photos[]` | `$request->getUploadedFileList('photos')` |
| вкладені імена `docs[passport]` | `$request->getUploadedFile('docs.passport')` або `'docs[passport]'` |
| тіло запиту — це сам файл (`PUT /files/report.pdf`, `Content-Type: application/pdf`) | `$request->getBodyAsFile()` |
| data URI або рядок base64 у JSON | `UploadedFile::fromDataUri($value)`, `UploadedFile::fromBase64($value, $name, $type)` |

### Приклад: завантаження через форму

```php
final class UploadAvatar extends AbstractAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $file = $request->getUploadedFile('avatar') ?? throw new UnprocessableContentException(['avatar' => 'No file.']);

        if (!$file->isOk()) {
            throw new UnprocessableContentException(['avatar' => $file->getErrorMessage()]);
        }
        if (!$file->hasMediaType('image/png', 'image/jpeg', 'image/webp')) {
            throw new UnsupportedMediaTypeException(['image/png', 'image/jpeg', 'image/webp']);
        }
        if ($file->getSize() > 2_000_000) {
            throw new ContentTooLargeException('The avatar must not exceed 2 MB.');
        }

        $path = $file->store('/var/uploads/avatars');    // "/var/uploads/avatars/3f2a9c….png"

        return $this->created('/avatars/' . basename($path));
    }
}
```

### Приклад: REST-завантаження сирого тіла

```
PUT /files/report
Content-Type: text/plain
Content-Disposition: attachment; filename*=UTF-8''%D0%B7%D0%B2%D1%96%D1%82.txt

hello upload
```

```php
$file = $request->getBodyAsFile('report.bin');   // the default name if there is no Content-Disposition
$file->getClientFilename();   // "звіт.txt" (RFC 6266 / RFC 8187)
$file->getClientMediaType();  // "text/plain"
```

### Приклад: data URI у JSON

```json
{"title": "Avatar", "avatar": "data:image/png;base64,iVBORw0KGgo…"}
```

```php
$avatar = UploadedFile::fromDataUri($request->getBodyParam('avatar'), 'avatar.png');
```

### API UploadedFile

| Метод | |
|---|---|
| `isOk()`, `getError()`, `getErrorMessage()` | статус завантаження (`UPLOAD_ERR_*`) і зрозуміле повідомлення |
| `getMediaType()` | медіатип, **визначений за вмістом** (libmagic) — використовуйте його для прийняття рішень |
| `hasMediaType('image/*', 'application/pdf')` | перевіряє визначений тип за шаблонами |
| `getClientMediaType()`, `getClientFilename()` | те, що стверджує клієнт, — не заслуговує на довіру |
| `getSafeClientFilename()`, `getClientExtension()` | ім'я без шляхів і керувальних символів; його розширення в нижньому регістрі |
| `getSize()`, `getContents()`, `getStream()`, `getHash('sha256')` | вміст |
| `store($directory, $filename = null)` | переміщує файл; без імені генерує випадкове з розширенням, ніколи не перезаписує; повертає шлях |
| `moveTo($path)` | PSR-7: переміщує файл (один раз) |

Ніколи не використовуйте клієнтське ім'я чи тип файлу як шлях або для рішень щодо безпеки: `store()` генерує ім'я,
`getMediaType()` читає вміст. Визначений тип і клієнтське ім'я лишаються доступними й після переміщення.

### Обмеження

| Обмеження | Де | За замовчуванням |
|---|---|---|
| тіло запиту | `BodySizeLimitMiddleware` (`APP_MAX_BODY_SIZE`) | 10 MB → 413 |
| власні обмеження PHP (POST multipart) | `upload_max_filesize`, `post_max_size` у php.ini | `UPLOAD_ERR_INI_SIZE` |
| частини, розмір поля, заголовки частин | `MultipartParser(maxParts, maxFieldSize, maxHeaderSize)` | 1000, 1 MB, 8 KB → 413 |

Multipart-парсер працює потоково: файлові частини потрапляють у тимчасові потоки (пам'ять до 2 MB, далі
тимчасовий файл), тож великі файли не переповнюють пам'ять.

<a id="downloads"></a>

## Завантаження з сервера

`FileResponse` надсилає файл:

```php
return new FileResponse('/var/reports/2026.pdf', 'Звіт 2026.pdf');                     // download
return new FileResponse($path, disposition: DispositionType::Inline);                   // show in the browser
return $this->file($path, 'report.pdf');                                                // AbstractAction
```

Він встановлює `Content-Type` (визначений; `charset=utf-8` для тексту), `Content-Length`, `Content-Disposition` з
ASCII-запасним варіантом і UTF-8-ім'ям (`filename*=UTF-8''…`, RFC 6266 / 8187), `Last-Modified`, сильний
`ETag` та `Accept-Ranges: bytes`, а також відповідає на умовні запити та запити діапазонів (RFC 9110 §13, §14):

| Запит | Відповідь |
|---|---|
| `If-None-Match: <etag>` або `If-Modified-Since` (не змінювався) | `304 Not Modified` |
| `If-Match` / `If-Unmodified-Since` не виконуються | `412 Precondition Failed` |
| `Range: bytes=0-4`, `bytes=100-`, `bytes=-500` | `206 Partial Content` + `Content-Range` |
| `Range` за межами кінця | `416 Range Not Satisfiable` + `Content-Range: bytes */size` |
| `If-Range` із застарілим валідатором | увесь файл (`200`) |

```
GET /download/hello.txt
Range: bytes=0-4

HTTP/1.1 206 Partial Content
Content-Type: text/plain; charset=utf-8
Content-Length: 5
Content-Range: bytes 0-4/24
Content-Disposition: attachment; filename="______ hello.txt"; filename*=UTF-8''%D0%9F%D1%80%D0%B8%D0%B2%D1%96%D1%82%20hello.txt
ETag: "69ea5ea99e1091849c1747c66618cf82"
Accept-Ranges: bytes

Hello
```

Відеоплеєри та менеджери завантажень використовують діапазони для перемотування й докачування. На запит із кількома
діапазонами відповідь — увесь файл.

## Content-Disposition у коді

```php
use Core\Http\Header\ContentDisposition;

(string) ContentDisposition::attachment('Звіт.pdf');   // attachment; filename=____.pdf; filename*=UTF-8''%D0%97…
ContentDisposition::parse($header)?->filename;         // decoded, without paths
```
