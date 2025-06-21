# uri

## Acerca de la clase Uri

Esta librería tiene las siguientes características.

- La instancia `Ginsen\ValueObject\Uri` cumple con el [PSR UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface).
- Permite crear instancias de **uris externas, internas y de patrón de sustitución**.
- Se comporta como clase inmutable (Value Object) si intentamos modificar una instancia creamos una nueva sin alterar
  la instancia original.

## Acerca de la clase UriType

Si utilizas Doctrine, la clase `Ginsen\Doctrine\Type\UriType` te permíte mapear el value object `Ginsen\ValueObject\Uri`,
ver [Custom Mapping Types](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html).


## Uri externa

Las uris externas tienen el formato corriente de una uri normal, por ejemplo:

```
https://github.com/ginsen/uri
```

Ver el siguiente fragmento de los tests.

```php
<?php
# Ginsen\Tests\ValueObject\UriTest

#[Test]
public function it_should_build_external_uri()
{
    $uri = Uri::fromStr('https://username:password@hostname:9090/path/file%281%29.html?arg=value#anchor');

    self::assertTrue($uri->isHttps());
    self::assertSame('https://username:password@hostname:9090/path/file%281%29.html?arg=value#anchor', $uri->toStr());
    self::assertSame('https://username:password@hostname:9090/path/file%281%29.html?arg=value#anchor', (string) $uri);
    self::assertSame('https', $uri->getScheme());
    self::assertSame('username', $uri->user());
    self::assertSame('password', $uri->password());
    self::assertSame('username:password', $uri->getUserInfo());
    self::assertSame('username:password@hostname:9090', $uri->getAuthority());
    self::assertSame('hostname', $uri->getHost());
    self::assertSame(9090, $uri->getPort());
    self::assertSame('/path/file%281%29.html', $uri->getPath());
    self::assertSame('arg=value', $uri->getQuery());
    self::assertSame('anchor', $uri->getFragment());
    self::assertSame('file%281%29.html', $uri->fileName());
}
```
## Uri interna

Cuando estamos en un contexto de contenedores, por ejemplo sistema con [Kubernetes](https://kubernetes.io/es/) o una red
interna de [Docker](https://www.docker.com/), es posible que queramos construir URI válidas para consumo interno dentro
de nuestra red de contenedores.

Esta clase permite crear URIs que cumplen con el **PSR UriInterface** y que pueden usarse en contexto de contenedores,
por ejemplo:

```
container/path/filename
```

Ver el siguiente fragmento de los tests.

```php
<?php
# Ginsen\Tests\ValueObject\UriTest

#[Test]
public function it_should_build_container_uri()
{
    $uri = Uri::fromStr('container/path/sub-path/');

    self::assertFalse($uri->isHttps());
    self::assertSame('container/path/sub-path/', $uri->toStr());
    self::assertSame('container/path/sub-path/', (string) $uri);
    self::assertSame('', $uri->getScheme());
    self::assertNull($uri->user());
    self::assertNull($uri->password());
    self::assertSame('container', $uri->getHost());
    self::assertNull($uri->getPort());
    self::assertSame('/path/sub-path/', $uri->getPath());
    self::assertSame('', $uri->getQuery());
    self::assertSame('', $uri->getFragment());
    self::assertNull($uri->fileName());
}
```

## URI de patrón de sustitución

En ocasiones nos interesa crear una uri donde podamos realizar más tarde sustituciones de variables, por ejemplo:

```
https://{host}/my-path
```
donde `{host}` puede ser sustituido por `domain.com` o `dev.domain.com` según en que entorno estemos.

Veamos los test.

```php
<?php
# Ginsen\Tests\ValueObject\UriTest

#[Test]
public function it_should_build_routing_uri()
{
    $uri = Uri::fromStr('{host}/path/{filter_1}/sub-path/{filter_2}');

    self::assertFalse($uri->isHttps());
    self::assertSame('{host}/path/{filter_1}/sub-path/{filter_2}', $uri->toStr());
    self::assertSame('{host}/path/{filter_1}/sub-path/{filter_2}', (string) $uri);
    self::assertSame('', $uri->getScheme());
    self::assertNull($uri->user());
    self::assertNull($uri->password());
    self::assertSame('{host}', $uri->getHost());
    self::assertNull($uri->getPort());
    self::assertSame('/path/{filter_1}/sub-path/{filter_2}', $uri->getPath());
    self::assertSame('', $uri->getQuery());
    self::assertSame('', $uri->getFragment());
    self::assertSame('{filter_2}', $uri->fileName());
}
```

## Características extras

### Extraer Query Param en formato array

```php
<?php

use Ginsen\ValueObject\Uri;

$uri = Uri::fromStr('https://foo.com/file.html?a=1&b=some+text');

$uri->getQuery();         # 'a=1&b=some+text'
$uri->getQueryToArray();  # ['a' => 1, 'b' => 'some text']
```

### Comparar 2 Value Objects

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://google.com');
$uri2 = Uri::fromStr('https://google.net');
$uri3 = Uri::fromStr('https://google.com');

$uri1->isEqual($uri2);  # false
$uri1->isEqual($uri3);  # true
```

### Recuperar el sufijo del dominio

Ver test.

```php
<?php

use Ginsen\ValueObject\Uri;

#[Test]
#[DataProvider('uriDataProvider')]
public function it_should_return_domain_suffix(string $uri, ?string $expected)
{
    $uri = Uri::fromStr($uri);
    self::assertSame($expected, $uri->domainSuffix());
}


public static function uriDataProvider(): iterable
{
    return [
        ['https://google.com', 'com'],
        ['https://google.es', 'es'],
        ['https://google.net', 'net'],
        ['https://www.es.google.com', 'com'],
        ['https://google', null],
        ['container', null],
    ];
}
```

### Validar una URI (método estático)

```php
<?php

use Ginsen\ValueObject\Uri;

Uri::isValid('https://google.com');  # true
Uri::isValid('some text');           # false
```

También podemos pasarle un Validador personalizado (Closure).

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = 'https://google.es';
$uri2 = 'https://google.com';

Uri::isValid($uri1);  # true
Uri::isValid($uri2);  # true

// El validador siempre a de retornar un valor boolean y a de recibir como argumento una instancia de Uri
$validator = fn (Uri $uri): bool => $uri->domainSuffix() === 'es';

Uri::isValid($uri1, $validator);  # true
Uri::isValid($uri2, $validator);  # false
```

### Chequear si existe una URI

Requiere conexión a internet, ver test.

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://github.com/ginsen/uri');
$uri2 = Uri::fromStr('https://github.com/ginsen/uri-fake');

$uri1->exists();  # true
$uri2->exists();  # false
```

### Comportamiento inmutable

Como Value Object, no permitivos modificar una instancia ya creada.

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://github.com/ginsen/uri');
$uri2 = $uri1->withHost('google.es');

$uri1->toStr(); # 'https://github.com/ginsen/uri'
$uri2->toStr(); # 'https://google.es/ginsen/uri'
```