# Dependency injection

The container (`Core\Di\Container`) creates objects by reflection. Constructor dependencies are resolved by
type ("autowiring"); `di.xml` adds what reflection cannot know: which class implements an interface,
which objects are shared, scalar arguments and calls after creation.

## Getting objects

```php
$container->get(Router::class);              // shared: the same instance every time
$container->create(ItemList::class);         // a new instance
$container->create(Report::class, ['year' => 2026]);   // with explicit constructor arguments (by name)
$container->callMethod($object, 'handle', ['id' => 5]); // a method call with autowired arguments
```

Actions and commands are created with `create()` for each request, so they can keep per-request state.
`Core\Di\ContainerInterface` itself is injectable.

## Autowiring

For every constructor parameter the container takes, in this order:

1. an explicit argument (`create($class, ['name' => …])` or `<argument name="…">` in `di.xml`);
2. the configured service, if the parameter's class or interface is configured in `di.xml`;
3. the default value;
4. an object of the parameter's class, created by the container (`get()`, so shared);
5. `null` for nullable parameters.

Otherwise the container throws `Cannot resolve argument "$x" of Class::__construct()`. Rule 2 before 3 means
that `__construct(ViewRenderer $renderer = new ViewRenderer())` still gets the `ViewRenderer` configured in
`di.xml`: defaults are for using a class without the container.

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

| Element | Meaning |
|---|---|
| `<interface name="I" class="C"/>` | `I` is created as `C` (including the instance definition of `C`) |
| `<instance class="C" shared="true">` | definition of `C`; `shared="true"`: one instance per container |
| `<argument name="x">` | constructor argument by parameter name, contains exactly one value |
| `<callback method="m">` | calls `$object->m(...)` after creation; several callbacks run in order |

### Argument values

| Value | Result |
|---|---|
| `<string> a b </string>` | `" a b "` (kept as written) |
| `<int>5</int>`, `<float>1.5</float>`, `<bool>true</bool>` | typed scalars (`true`/`1` are true) |
| `<path base="BP">Application/runtime</path>` | the constant's value + `/` + the path (`BP`, `APP_ETC` or any constant) |
| `<env name="APP_SECRET"/>` | environment variable, required |
| `<env name="APP_DEBUG" type="bool" default="false"/>` | typed (`string`, `int`, `float`, `bool`) with a default |
| `<array><item name="k">…</item><item>…</item></array>` | an array; items without a name get numeric keys |
| `<object class="C"><argument …/></object>` | a new `C` with these arguments (others autowired) |

An `<object>` is always a new instance. To share a configured service, declare it as an `<instance
shared="true">` and let autowiring inject it (rule 2).

## Configuration files

Every file matching `*di.xml` in the configuration directory is validated against `app/Core/Di/etc/di.xsd`
and merged: `di.xml` first, then `local.di.xml` and other more specific names. See
[Configuration](configuration.md#merging).
