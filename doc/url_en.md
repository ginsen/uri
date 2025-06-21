# uri

## About the Uri Class

This library has the following features:

* The `Ginsen\ValueObject\Uri` instance complies with the [PSR UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface).
* Allows creation of **external, internal, and substitution-pattern URIs**.
* Behaves as an immutable class (Value Object): modifying an instance will return a new one without changing the original.

## About the UriType Class

If you're using Doctrine, the `Ginsen\Doctrine\Type\UriType` class allows you to map the value object 
`Ginsen\ValueObject\Uri`. See [Custom Mapping Types](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html).


## External URI

External URIs follow the standard format of a normal URI, for example:

```
https://github.com/ginsen/uri
```

See the following test snippet:

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

## Internal URI

When working within containerized environments, such as systems with [Kubernetes](https://kubernetes.io) or internal
[Docker](https://www.docker.com/) networks, we may want to construct URIs for internal consumption within our container network.

This class allows creation of URIs compliant with the **PSR UriInterface** that can be used in container contexts, for example:

```
container/path/filename
```

See the following test snippet:

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

## Substitution-pattern URI

Sometimes, we may want to create a URI where variables can be substituted later, for example:

```
https://{host}/my-path
```

where `{host}` can be replaced with `domain.com` or `dev.domain.com` depending on the environment.

See the test below:

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

## Extra Features

### Extract Query Parameters as Array

```php
<?php

use Ginsen\ValueObject\Uri;

$uri = Uri::fromStr('https://foo.com/file.html?a=1&b=some+text');

$uri->getQuery();         # 'a=1&b=some+text'
$uri->getQueryToArray();  # ['a' => 1, 'b' => 'some text']
```

### Compare Two Value Objects

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://google.com');
$uri2 = Uri::fromStr('https://google.net');
$uri3 = Uri::fromStr('https://google.com');

$uri1->isEqual($uri2);  # false
$uri1->isEqual($uri3);  # true
```

### Get Domain Suffix

See test:

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

### Validate a URI (Static Method)

```php
<?php

use Ginsen\ValueObject\Uri;

Uri::isValid('https://google.com');  # true
Uri::isValid('some text');           # false
```

You can also pass a custom validator (Closure):

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = 'https://google.es';
$uri2 = 'https://google.com';

Uri::isValid($uri1);  # true
Uri::isValid($uri2);  # true

// The validator must return a boolean and receive the Uri argument
$validator = fn (Uri $uri): bool => $uri->domainSuffix() === 'es';

Uri::isValid($uri1, $validator);  # true
Uri::isValid($uri2, $validator);  # false
```

### Check if a URI Exists

Requires internet connection. See test:

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://github.com/ginsen/uri');
$uri2 = Uri::fromStr('https://github.com/ginsen/uri-fake');

$uri1->exists();  # true
$uri2->exists();  # false
```

### Immutable Behavior

As a Value Object, existing instances cannot be modified.

```php
<?php

use Ginsen\ValueObject\Uri;

$uri1 = Uri::fromStr('https://github.com/ginsen/uri');
$uri2 = $uri1->withHost('google.es');

$uri1->toStr(); # 'https://github.com/ginsen/uri'
$uri2->toStr(); # 'https://google.es/ginsen/uri'
```
