# Configuration schemas

Both files are validated against XML Schemas when they are read. Reference them for editor support:

```xml
<di xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Core/Di/etc/di.xsd">
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Core/Route/etc/routes.xsd">
```

## di.xml (`app/Core/Di/etc/di.xsd`)

```
di
├── interface   @name (required)  @class (required)                      unique by name
└── instance    @class (required) @shared (bool)                          unique by class
    ├── argument  @name (required)   — exactly one value
    └── callback  @method (required)
        └── argument …

value: string | int | float | bool
     | path    @base (constant name, required)   text: relative path
     | env     @name (required) @type (string|int|float|bool) @default
     | array   └── item  @name (optional) — exactly one value
     | object  @class (required) └── argument …
```

## routes.xml (`app/Core/Route/etc/routes.xsd`)

```
routes  @trailing-slash (redirect|strict|ignore, default redirect)
├── route    @name (required, unique)  @method (required: list of GET HEAD POST PUT PATCH DELETE OPTIONS)
│            @path (required, starts with "/")  @action (required)  @trailing-slash
│   └── query  @name (required, unique in the route)  @requirement (regex)  @default  @required (bool)
└── command  @name (required, unique)  @action (required)
```

Path pattern syntax: `{name}` (one segment) or `{name:regex}`; names are `[A-Za-z_]\w*`; the regex may
contain `{n,m}` quantifiers. See [Routing](../http/routing.md).
