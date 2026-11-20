# Views and content negotiation

One action can serve several formats. It returns a `View` — the data and, optionally, an HTML template —
and the `ViewRenderer` picks the representation from the request's `Accept` header (proactive negotiation,
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

Every negotiated response carries `Vary: Accept`, so caches keep the formats apart (RFC 9110 §12.5.5).

## How the choice is made

1. Candidates are the media types of the renderers that **support** the view, in registration order — the
   server's preference.
2. Each candidate gets the quality of the most specific matching `Accept` range
   (`text/html;level=1` > `text/html` > `text/*` > `*/*`); `q=0` excludes it.
3. The highest quality wins, the server's order breaks ties. No `Accept` header means "anything": the first
   candidate.
4. Nothing acceptable → **406 Not Acceptable** with the available types in the problem details.

| Renderer | Media types | Supports |
|---|---|---|
| `JsonRenderer` | `application/json` | every view |
| `XmlRenderer` | `application/xml`, `text/xml` | every view (data encoded by `XmlEncoder`) |
| `HtmlRenderer` | `text/html`, `application/xhtml+xml` | a view with a template the engine has, or string/`Stringable` data |
| `TextRenderer` | `text/plain` | scalar, `Stringable` or `null` data |

The renderers and their order are configured in `di.xml`:

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

## The View

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

## XML encoding

`XmlEncoder` turns data into XML: keys become elements, list items `<item>`, `null` is `xsi:nil`, booleans
`true`/`false`, enums their value, dates RFC 3339, invalid names are made valid (`1st` → `_1st`).

```php
['id' => 1, 'tags' => ['a', 'b'], 'active' => true, 'note' => null]
```
```xml
<response><id>1</id><tags><item>a</item><item>b</item></tags><active>true</active>
<note xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/></response>
```

## Templates

The framework does not bundle a template engine; `HtmlRenderer` uses any `TemplateEngineInterface`:

```php
interface TemplateEngineInterface
{
    public function exists(string $template): bool;
    public function render(string $template, array $context = []): string;
}
```

The context is `View::context()` plus the request as `request` (for example for the CSRF token:
`$context['request']->getAttribute('csrf_token')`). A Twig adapter:

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

Templates must escape their output (Twig does by default).

## A new format

Implement `RendererInterface` and register it:

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

## Without negotiation

When a resource has one format, return the response directly (`$this->json()`, `new HtmlResponse()`, …);
nothing is negotiated and no `Vary` is added.

## The Accept header in code

```php
use Core\Http\Header\Accept;

Accept::parse('text/html;q=0.5, application/json')->negotiate(['text/html', 'application/json']);  // application/json
Accept::parse('text/*')->getQuality('text/csv');      // 1.0
$request->accepts('application/json');
```
