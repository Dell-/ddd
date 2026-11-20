# Впровадження залежностей

Контейнер (`Core\Di\Container`) створює об’єкти за допомогою рефлексії. Залежності конструктора розв’язуються за
типом (автопідстановка, «autowiring»); `di.xml` додає те, чого рефлексія знати не може: який клас реалізує інтерфейс,
які об’єкти є спільними, скалярні аргументи та виклики після створення.

## Отримання об’єктів

```php
$container->get(Router::class);              // shared: the same instance every time
$container->create(ItemList::class);         // a new instance
$container->create(Report::class, ['year' => 2026]);   // with explicit constructor arguments (by name)
$container->callMethod($object, 'handle', ['id' => 5]); // a method call with autowired arguments
```

Actions і команди створюються через `create()` для кожного запиту, тож можуть зберігати стан запиту.
Сам `Core\Di\ContainerInterface` також можна впроваджувати.

## Автопідстановка (autowiring)

Для кожного параметра конструктора контейнер бере, у такому порядку:

1. явний аргумент (`create($class, ['name' => …])` або `<argument name="…">` у `di.xml`);
2. налаштований сервіс, якщо клас або інтерфейс параметра налаштовано в `di.xml`;
3. значення за замовчуванням;
4. об’єкт класу параметра, створений контейнером (`get()`, отже спільний);
5. `null` для параметрів, що допускають null.

Інакше контейнер викидає `Cannot resolve argument "$x" of Class::__construct()`. Те, що правило 2 стоїть перед 3,
означає, що `__construct(ViewRenderer $renderer = new ViewRenderer())` усе одно отримає `ViewRenderer`, налаштований
у `di.xml`: значення за замовчуванням призначені для використання класу без контейнера.

## di.xml

```xml
<di xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Core/Di/etc/di.xsd">
    <!-- an interface and its implementation -->
    <interface name="Psr\Log\LoggerInterface" class="Monolog\Logger"/>

    <!-- one shared instance with arguments and calls after creation -->
    <instance class="Monolog\Logger" shared="true">
        <argument name="name"><string>app</string></argument>
        <callback method="pushHandler">
            <argument name="handler">
                <object class="Monolog\Handler\StreamHandler">
                    <argument name="stream"><path base="BP">Application/runtime/app.log</path></argument>
                </object>
            </argument>
        </callback>
    </instance>
</di>
```

| Елемент | Значення |
|---|---|
| `<interface name="I" class="C"/>` | `I` створюється як `C` (включно з визначенням екземпляра `C`) |
| `<instance class="C" shared="true">` | визначення `C`; `shared="true"`: один екземпляр на контейнер |
| `<argument name="x">` | аргумент конструктора за іменем параметра, містить рівно одне значення |
| `<callback method="m">` | викликає `$object->m(...)` після створення; кілька callback виконуються по черзі |

### Значення аргументів

| Значення | Результат |
|---|---|
| `<string> a b </string>` | `" a b "` (зберігається як записано) |
| `<int>5</int>`, `<float>1.5</float>`, `<bool>true</bool>` | типізовані скаляри (`true`/`1` дають true) |
| `<path base="BP">Application/runtime</path>` | значення константи + `/` + шлях (`BP`, `APP_ETC` або будь-яка константа) |
| `<env name="APP_SECRET"/>` | змінна середовища, обов’язкова |
| `<env name="APP_DEBUG" type="bool" default="false"/>` | типізована (`string`, `int`, `float`, `bool`) зі значенням за замовчуванням |
| `<array><item name="k">…</item><item>…</item></array>` | масив; елементи без імені отримують числові ключі |
| `<object class="C"><argument …/></object>` | новий `C` із цими аргументами (решта підставляється автоматично) |

`<object>` — це завжди новий екземпляр. Щоб поділити налаштований сервіс, оголосіть його як `<instance
shared="true">` і дозвольте автопідстановці впровадити його (правило 2).

## Файли конфігурації

Кожен файл, що відповідає `*di.xml`, у каталозі конфігурації валідується за `app/Core/Di/etc/di.xsd`
і зливається: спочатку `di.xml`, потім `local.di.xml` та інші більш специфічні імена. Див.
[Конфігурація](configuration.md#злиття).
