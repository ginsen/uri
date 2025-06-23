<?php

declare(strict_types=1);

namespace Ginsen\Uri\ValueObject;

trait UriExtraMethodsTrait
{
    public function isHttps(): bool
    {
        return 'https' === $this->getScheme();
    }


    public function hasUser(): bool
    {
        return null !== $this->user();
    }


    public function user(): ?string
    {
        return parse_url($this->uri, \PHP_URL_USER);
    }


    public function hasPassword(): bool
    {
        return null !== $this->password();
    }


    public function password(): ?string
    {
        return parse_url($this->uri, \PHP_URL_PASS);
    }


    public function hasHost(): bool
    {
        return !empty($this->getHost());
    }


    public function hasPort(): bool
    {
        return null !== $this->getPort();
    }


    public function hasPath(): bool
    {
        return !empty($this->getPath());
    }


    public function hasQuery(): bool
    {
        return !empty($this->getQuery());
    }


    public function hasFragment(): bool
    {
        return !empty($this->getFragment());
    }


    public function getQueryToArray(): array
    {
        if (!$query = parse_url($this->uri, \PHP_URL_QUERY)) {
            return [];
        }

        parse_str($query, $output);

        return $output;
    }


    public function domainSuffix(): ?string
    {
        $host = $this->getHost();

        if (empty($host)) {
            return null;
        }

        $data = explode('.', $host);

        return (count($data) > 1) ? end($data) : null;
    }


    public function fileName(): ?string
    {
        if (!$this->hasPath()) {
            return null;
        }

        if (str_ends_with($this->getPath(), '/')) {
            return null;
        }

        return basename($this->getPath());
    }
}
