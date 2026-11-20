# Files

## Uploads

Files reach an application in several ways; all of them end up as a `Core\Http\Message\UploadedFile`
(PSR-7 `UploadedFileInterface` with a friendly API).

| How the client sends it | How to get it |
|---|---|
| HTML form / `multipart/form-data` with **POST** | `$request->getUploadedFile('avatar')` |
| `multipart/form-data` with **PUT / PATCH** (PHP does not parse it, the framework does) | the same |
| several files `photos[]` | `$request->getUploadedFileList('photos')` |
| nested names `docs[passport]` | `$request->getUploadedFile('docs.passport')` or `'docs[passport]'` |
| the request body is the file (`PUT /files/report.pdf`, `Content-Type: application/pdf`) | `$request->getBodyAsFile()` |
| a data URI or base64 string in JSON | `UploadedFile::fromDataUri($value)`, `UploadedFile::fromBase64($value, $name, $type)` |

### Example: a form upload

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

### Example: a REST upload of the raw body

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

### Example: a data URI in JSON

```json
{"title": "Avatar", "avatar": "data:image/png;base64,iVBORw0KGgo…"}
```

```php
$avatar = UploadedFile::fromDataUri($request->getBodyParam('avatar'), 'avatar.png');
```

### The UploadedFile API

| Method | |
|---|---|
| `isOk()`, `getError()`, `getErrorMessage()` | upload status (`UPLOAD_ERR_*`) and a readable message |
| `getMediaType()` | media type **detected from the content** (libmagic) — use this for decisions |
| `hasMediaType('image/*', 'application/pdf')` | checks the detected type against patterns |
| `getClientMediaType()`, `getClientFilename()` | what the client claims — untrusted |
| `getSafeClientFilename()`, `getClientExtension()` | the name without paths and control characters; its lower-case extension |
| `getSize()`, `getContents()`, `getStream()`, `getHash('sha256')` | the content |
| `store($directory, $filename = null)` | moves the file; without a name a random one with the extension, never overwrites; returns the path |
| `moveTo($path)` | PSR-7: moves the file (once) |

Never use the client file name or type as a path or as a security decision: `store()` generates the name,
`getMediaType()` reads the content. The detected type and the client name stay available after a move.

### Limits

| Limit | Where | Default |
|---|---|---|
| request body | `BodySizeLimitMiddleware` (`APP_MAX_BODY_SIZE`) | 10 MB → 413 |
| PHP's own (POST multipart) | `upload_max_filesize`, `post_max_size` in php.ini | `UPLOAD_ERR_INI_SIZE` |
| parts, field size, part headers | `MultipartParser(maxParts, maxFieldSize, maxHeaderSize)` | 1000, 1 MB, 8 KB → 413 |

The multipart parser streams: file parts go to temporary streams (memory up to 2 MB, then a temporary
file), so large uploads do not fill the memory.

## Downloads

`FileResponse` sends a file:

```php
return new FileResponse('/var/reports/2026.pdf', 'Звіт 2026.pdf');                     // download
return new FileResponse($path, disposition: DispositionType::Inline);                   // show in the browser
return $this->file($path, 'report.pdf');                                                // AbstractAction
```

It sets `Content-Type` (detected; `charset=utf-8` for text), `Content-Length`, `Content-Disposition` with
an ASCII fallback and the UTF-8 name (`filename*=UTF-8''…`, RFC 6266 / 8187), `Last-Modified`, a strong
`ETag` and `Accept-Ranges: bytes`, and answers conditional and range requests (RFC 9110 §13, §14):

| Request | Response |
|---|---|
| `If-None-Match: <etag>` or `If-Modified-Since` (not modified) | `304 Not Modified` |
| `If-Match` / `If-Unmodified-Since` failing | `412 Precondition Failed` |
| `Range: bytes=0-4`, `bytes=100-`, `bytes=-500` | `206 Partial Content` + `Content-Range` |
| `Range` beyond the end | `416 Range Not Satisfiable` + `Content-Range: bytes */size` |
| `If-Range` with an old validator | the whole file (`200`) |

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

Video players and download managers use ranges to seek and resume. Several ranges in one request are
answered with the whole file.

## Content-Disposition in code

```php
use Core\Http\Header\ContentDisposition;

(string) ContentDisposition::attachment('Звіт.pdf');   // attachment; filename=____.pdf; filename*=UTF-8''%D0%97…
ContentDisposition::parse($header)?->filename;         // decoded, without paths
```
