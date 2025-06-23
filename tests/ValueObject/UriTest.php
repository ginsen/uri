<?php

namespace Ginsen\Uri\Tests\ValueObject;

use Ginsen\Uri\Exception\UriException;
use Ginsen\Uri\ValueObject\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uri::class)]
class UriTest extends TestCase
{
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


    #[Test]
    public function it_should_check_scheme()
    {
        $uri1 = Uri::fromStr('http://foo.com');
        $uri2 = Uri::fromStr('https://foo.com');

        self::assertFalse($uri1->isHttps());
        self::assertSame('http', $uri1->getScheme());

        self::assertTrue($uri2->isHttps());
        self::assertSame('https', $uri2->getScheme());
    }


    #[Test]
    public function it_should_check_user_and_password()
    {
        $uri1 = Uri::fromStr('https://username:password@foo.com');
        $uri2 = Uri::fromStr('https://foo.com');

        self::assertTrue($uri1->hasUser());
        self::assertSame('username', $uri1->user());

        self::assertTrue($uri1->hasPassword());
        self::assertSame('password', $uri1->password());

        self::assertFalse($uri2->hasUser());
        self::assertNull($uri2->user());

        self::assertFalse($uri2->hasPassword());
        self::assertNull($uri2->password());
    }


    #[Test]
    public function it_should_check_port()
    {
        $uri1 = Uri::fromStr('https://foo.com:8080');
        $uri2 = Uri::fromStr('https://foo.com');

        self::assertTrue($uri1->hasPort());
        self::assertSame(8080, $uri1->getPort());

        self::assertFalse($uri2->hasPort());
        self::assertNull($uri2->getPort());
    }


    #[Test]
    public function it_should_check_path()
    {
        $uri1 = Uri::fromStr('https://foo.com/path/file.html');

        self::assertTrue($uri1->hasHost());
        self::assertTrue($uri1->hasPath());
        self::assertSame('foo.com', $uri1->getHost());
        self::assertSame('/path/file.html', $uri1->getPath());

        $uri2 = Uri::fromStr('https://foo.com');

        self::assertTrue($uri2->hasHost());
        self::assertFalse($uri2->hasPath());
        self::assertSame('foo.com', $uri2->getHost());
        self::assertSame('', $uri2->getPath());

        $uri3 = Uri::fromStr('https://foo.com/');

        self::assertTrue($uri3->hasHost());
        self::assertFalse($uri3->hasPath());
        self::assertSame('foo.com', $uri3->getHost());
        self::assertSame('', $uri3->getPath());

        $uri4 = Uri::fromStr('host/path/foo/bar');

        self::assertTrue($uri4->hasHost());
        self::assertTrue($uri4->hasPath());
        self::assertSame('host', $uri4->getHost());
        self::assertSame('/path/foo/bar', $uri4->getPath());

        $uri5 = Uri::fromStr('/path/foo/bar');

        self::assertFalse($uri5->hasHost());
        self::assertTrue($uri5->hasPath());
        self::assertSame('', $uri5->getHost());
        self::assertSame('/path/foo/bar', $uri5->getPath());
    }


    #[Test]
    public function it_should_check_query()
    {
        $uri1 = Uri::fromStr('https://foo.com/file.html?a=1&b=some+text');
        $uri2 = Uri::fromStr('https://foo.com/file.html');
        $uri3 = Uri::fromStr('https://foo.com/file.html?h=195&w=450&fit=clip&q=100&auto=format%2Ccompress');

        self::assertTrue($uri1->hasQuery());
        self::assertSame('a=1&b=some+text', $uri1->getQuery());

        self::assertFalse($uri2->hasQuery());
        self::assertSame('', $uri2->getQuery());

        self::assertTrue($uri3->hasQuery());
        self::assertSame('h=195&w=450&fit=clip&q=100&auto=format%2Ccompress', $uri3->getQuery());
        self::assertSame([
            'h'    => '195',
            'w'    => '450',
            'fit'  => 'clip',
            'q'    => '100',
            'auto' => 'format,compress'
        ], $uri3->getQueryToArray());
    }


    #[Test]
    public function it_should_check_anchor()
    {
        $uri1 = Uri::fromStr('https://foo.com/file.html#steep1');
        $uri2 = Uri::fromStr('https://foo.com/file.html');

        self::assertTrue($uri1->hasFragment());
        self::assertSame('steep1', $uri1->getFragment());

        self::assertFalse($uri2->hasFragment());
        self::assertSame('', $uri2->getFragment());
    }


    #[Test]
    public function it_should_check_filename()
    {
        $uri1 = Uri::fromStr('https://foo.com/path/file.html');
        $uri2 = Uri::fromStr('https://foo.com');

        self::assertTrue($uri1->hasPath());
        self::assertSame('file.html', $uri1->fileName());

        self::assertFalse($uri2->hasPath());
        self::assertNull($uri2->fileName());
    }


    #[Test]
    public function it_throw_uri_exception_when_argument_is_invalid()
    {
        self::expectException(UriException::class);
        Uri::fromStr('foo bar');
    }


    #[Test]
    public function it_should_compare_with_other_uri()
    {
        $uri1 = Uri::fromStr('https://google.com');
        $uri2 = Uri::fromStr('https://google.com');
        $uri3 = Uri::fromStr('https://google.net');

        self::assertTrue($uri1->isEqual($uri2));
        self::assertFalse($uri2->isEqual($uri3));
    }


    #[Test]
    public function it_should_check_if_param_is_valid()
    {
        self::assertFalse(Uri::isValid('foo bar'));
        self::assertTrue(Uri::isValid('https://google.com'));
        self::assertFalse(Uri::isValid(null));
    }


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


    #[Test]
    public function it_should_use_callback_validator()
    {
        $uri1 = Uri::fromStr('https://google.es');
        $uri2 = Uri::fromStr('https://google.io');

        $validator = fn (Uri $uri): bool => in_array($uri->domainSuffix(), ['es','com','net']);

        self::assertTrue(Uri::isValid($uri1, $validator));
        self::assertFalse(Uri::isValid($uri2, $validator));
    }


    #[Test]
    public function it_should_check_if_uri_exists()
    {
        $uri1 = Uri::fromStr('https://github.com/ginsen/uri');
        $uri2 = Uri::fromStr('https://github.com/ginsen/urifake');

        self::assertTrue($uri1->exists());
        self::assertFalse($uri2->exists());
    }


    #[Test]
    public function it_should_build_uri_with_query()
    {
        $uri1 = Uri::fromStr('https://localhost.test/image.png?w=900&color=blue');
        $uri1 = $uri1->withQuery('w=185&h=55&fit=clip');

        self::assertTrue([
                'w'     => '185',
                'h'     => '55',
                'fit'   => 'clip',
                'color' => 'blue',
            ] == $uri1->getQueryToArray()
        );

        $uri2 = Uri::fromStr('https://localhost.test/image.png');
        $uri2 = $uri2->withQuery('w=185&h=60');

        self::assertTrue([
                'w' => '185',
                'h' => '60',
            ] == $uri2->getQueryToArray()
        );

        $uri3 = Uri::fromStr('https://localhost.test/image.png?w=900&color=blue');
        $uri3 = $uri3->withQuery('');

        self::assertTrue([] == $uri3->getQueryToArray());
    }


    #[Test]
    public function it_should_build_new_instance_when_edit()
    {
        $uri1 = Uri::fromStr('https://github.com/ginsen/uri');
        $uri2 = $uri1->withHost('google.es');

        self::assertSame('https://github.com/ginsen/uri', $uri1->toStr());
        self::assertSame('https://google.es/ginsen/uri', $uri2->toStr());
    }
}
