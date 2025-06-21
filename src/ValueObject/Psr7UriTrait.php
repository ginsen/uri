<?php

namespace Ginsen\ValueObject;

use Psr\Http\Message\UriInterface;

trait Psr7UriTrait
{
    public function getScheme(): string
    {
        return (string) parse_url($this->uri, \PHP_URL_SCHEME);
    }


    public function getAuthority(): string
    {
        $authority = $this->getHost();
        $userInfo  = $this->getUserInfo();
        $port      = $this->getPort();

        if (!empty($userInfo)) {
            $authority = $userInfo . '@' . $authority;
        }

        if (!empty($port)) {
            $authority .= ':' . $port;
        }

        return $authority;
    }


    public function getUserInfo(): string
    {
        $username = (string) parse_url($this->uri, \PHP_URL_USER);

        if ($password = parse_url($this->uri, \PHP_URL_PASS)) {
            return $username . ':' . $password;
        }

        return $username;
    }


    public function getHost(): string
    {
        if ($host = (string) parse_url($this->uri, \PHP_URL_HOST)) {
            return $host;
        }

        if (preg_match('~^(?:(?:http|https)://)?([^/]+)~', $this->uri, $matches)) {
            return end($matches);
        }

        return $host;
    }


    public function getPort(): ?int
    {
        return parse_url($this->uri, \PHP_URL_PORT);
    }


    public function getPath(): string
    {
        $path = (string) parse_url($this->uri, \PHP_URL_PATH);

        if (preg_match('~^([^/]+)(.+)$~', $path, $matches)) {
            return end($matches);
        }

        if ('/' === $path) {
            return '';
        }

        return $path;
    }


    public function getQuery(): string
    {
        return (string) parse_url($this->uri, \PHP_URL_QUERY);
    }


    public function getFragment(): string
    {
        return (string) parse_url($this->uri, \PHP_URL_FRAGMENT);
    }


    public function withScheme(string $scheme): UriInterface
    {
        if (!empty($scheme)) {
            $scheme .= '://';
        }

        if (preg_match('~^((http|https)://)?(.+)$~', $this->uri, $match)) {
            $uri = $scheme . end($match);

            return self::fromStr($uri);
        }

        throw new \InvalidArgumentException('unsupported scheme');
    }


    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        if (!empty($user) && !empty($password)) {
            $user .= ':' . $password;
        }

        if (!empty($user)) {
            $user .= '@';
        }

        return $this->makeUri(
            $this->getScheme(),
            $user,
            $this->getHost(),
            $this->getPort(),
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment()
        );
    }


    public function withHost(string $host): UriInterface
    {
        return $this->makeUri(
            $this->getScheme(),
            $this->getUserInfo(),
            $host,
            $this->getPort(),
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment()
        );
    }


    public function withPort(?int $port): UriInterface
    {
        return $this->makeUri(
            $this->getScheme(),
            $this->getUserInfo(),
            $this->getHost(),
            $port,
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment()
        );
    }


    public function withPath(string $path): UriInterface
    {
        return $this->makeUri(
            $this->getScheme(),
            $this->getUserInfo(),
            $this->getHost(),
            $this->getPort(),
            $path,
            $this->getQuery(),
            $this->getFragment()
        );
    }


    public function withQuery(string $query): UriInterface
    {
        parse_str($query, $newParams);

        $params = !empty($newParams)
            ?http_build_query(array_merge($this->getQueryToArray(), $newParams))
            : '';

        return $this->makeUri(
            $this->getScheme(),
            $this->getUserInfo(),
            $this->getHost(),
            $this->getPort(),
            $this->getPath(),
            $params,
            $this->getFragment()
        );
    }


    public function withFragment(string $fragment): UriInterface
    {
        return $this->makeUri(
            $this->getScheme(),
            $this->getUserInfo(),
            $this->getHost(),
            $this->getPort(),
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment()
        );
    }


    public function __toString(): string
    {
        return $this->uri;
    }


    private function makeUri(
        string $scheme,
        string $userInfo,
        string $host,
        ?int   $port,
        string $path,
        string $query,
        string $fragment
    ): UriInterface
    {
        $uri = '';

        if (!empty($scheme)) {
            $uri = $scheme . '://';
        }

        if (!empty($userInfo)) {
            $uri .= $userInfo . '@';
        }

        if (!empty($host)) {
            $uri .= $host;
        }

        if (!empty($port)) {
            $uri .= ':' . $port;
        }

        if (!empty($path)) {
            $uri .= $path;
        }

        if (!empty($query)) {
            $uri .= '?' . $query;
        }

        if (!empty($fragment)) {
            $uri .= '#' . $fragment;
        }

        return self::fromStr($uri);
    }
}
