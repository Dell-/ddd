# Представлення (View) та узгодження вмісту (content negotiation)

Один action може віддавати кілька форматів. Він повертає `View` — дані та, за потреби, HTML-шаблон — а
`ViewRenderer` обирає представлення за заголовком `Accept` запиту (проактивне узгодження,
RFC 9110 §12).

```php
return $this->view(['id' => 7, 'name' => 'Item 7'], 'item/show');
```

```
GET /items/7                        → 200 application/json   {"id":7,"name":"Item 7"}
GET /items/7  Accept: application/xml   → 200 application/xml    <response><id>7</id><name>Item 7</name></response>
GET /items/7  Accept: text/html         → 200 text/html          <h1>Item 7</h1>   (template "item/show")
GET /view     Accept: text/html         → 406 Not Acceptable (no template, so no HTML)
```

Кожна узгоджена відповідь містить `Vary: Accept`, щоб кеші розрізняли формати (RFC 9110 §12.5.5).

## Як робиться вибір

1. Кандидати — це медіатипи рендерерів, які **підтримують** view, у порядку реєстрації — це
   перевага сервера.
2. Кожен кандидат отримує якість найточнішого відповідного діапазону `Accept`
   (`text/html;level=1` > `text/html` > `text/*` > `*/*`); `q=0` виключає його.
3. Перемагає найвища якість, за рівності вирішує порядок сервера. Відсутній заголовок `Accept` означає «будь-що»: перший
   кандидат.
4. Нічого прийнятного → **406 Not Acceptable** з доступними типами в problem details.

| Рендерер | Медіатипи | Підтримує |
|---|---|---|
| `JsonRenderer` | `application/json` | кожен view |
| `XmlRenderer` | `application/xml`, `text/xml` | кожен view (дані кодує `XmlEncoder`) |
| `HtmlRenderer` | `text/html`, `application/xhtml+xml` | view із шаблоном, який є в рушії, або дані типу string/`Stringable` |
| `TextRenderer` | `text/plain` | скалярні дані, `Stringable` або `null` |

Рендерери та їхній порядок налаштовуються в `di.xml`:

```xml
<instance class="Core\Http\View\ViewRenderer" shared="true">
    <argument name="renderers">
        <array>
            <item><object class="Core\Http\View\JsonRenderer"/></item>
            <item><object class="Core\Http\View\XmlRenderer"/></item>
            <item><object class="Core\Http\View\HtmlRenderer"/></item>
            <item><object class="Core\Http\View\TextRenderer"/></item>
        </array>
    </argument>
</instance>
```

## View

```php
new View(
    data: $item,                 // anything: arrays, JsonSerializable, objects, scalars
    template: 'item/show',       // for HtmlRenderer
    status: Status::Created,
    headers: ['Location' => '/items/42'],
);
$view->withStatus(Status::Accepted)->withHeader('X-Total', '10');
$view->context();                // the data as an array for templates
```

## XML-кодування

`XmlEncoder` перетворює дані на XML: ключі стають елементами, елементи списків — `<item>`, `null` — `xsi:nil`, булеві значення —
`true`/`false`, enum — своїм значенням, дати — RFC 3339, некоректні імена виправляються (`1st` → `_1st`).

```php
['id' => 1, 'tags' => ['a', 'b'], 'active' => true, 'note' => null]
```
```xml
<response><id>1</id><tags><item>a</item><item>b</item></tags><active>true</active>
<note xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/></response>
```

## Шаблони

Фреймворк не постачається з шаблонізатором; `HtmlRenderer` використовує будь-який `TemplateEngineInterface`:

```php
interface TemplateEngineInterface
{
    public function exists(string $template): bool;
    public function render(string $template, array $context = []): string;
}
```

Контекст — це `View::context()` плюс запит під ключем `request` (наприклад, для CSRF-токена:
`$context['request']->getAttribute('csrf_token')`). Адаптер для Twig:

```php
final class TwigEngine implements TemplateEngineInterface
{
    public function __construct(private readonly \Twig\Environment $twig)
    {
    }

    public function exists(string $template): bool
    {
        return $this->twig->getLoader()->exists($template . '.html.twig');
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template . '.html.twig', $context);
    }
}
```

```xml
<item>
    <object class="Core\Http\View\HtmlRenderer">
        <argument name="engine"><object class="App\View\TwigEngine"/></argument>
    </object>
</item>
```

Шаблони мусять екранувати свій вивід (Twig робить це за замовчуванням).

## Новий формат

Реалізуйте `RendererInterface` і зареєструйте його:

```php
final class CsvRenderer implements RendererInterface
{
    public function getMediaTypes(): array
    {
        return ['text/csv'];
    }

    public function supports(View $view): bool
    {
        return is_array($view->data) && array_is_list($view->data);
    }

    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface
    {
        $out = fopen('php://temp', 'r+');
        foreach ($view->data as $row) {
            fputcsv($out, (array) $row, escape: '');
        }
        rewind($out);

        return new Response($view->status, ['Content-Type' => 'text/csv; charset=utf-8'] + $view->headers, (string) stream_get_contents($out));
    }
}
```

## Без узгодження

Коли ресурс має один формат, повертайте відповідь напряму (`$this->json()`, `new HtmlResponse()`, …);
нічого не узгоджується, і `Vary` не додається.

## Заголовок Accept у коді

```php
use Core\Http\Header\Accept;

Accept::parse('text/html;q=0.5, application/json')->negotiate(['text/html', 'application/json']);  // application/json
Accept::parse('text/*')->getQuality('text/csv');      // 1.0
$request->accepts('application/json');
```
