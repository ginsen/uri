<?php

namespace Ginsen\ValueObject;

use Assert\Assertion;
use Ginsen\Exception\UriException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $uri;

    use Psr7UriTrait;
    use UriExtraMethodsTrait;


    public static function fromStr($uri): self
    {
        self::checkAssertion($uri);

        $instance      = new static();
        $instance->uri = $uri;

        if (!$instance->hasHost() && !$instance->hasPath()) {
            throw new UriException('Invalid Uri');
        }

        return $instance;
    }


    public static function isValid($uri, \Closure $call = null): bool
    {
        try {
            $instance = ($uri instanceof Uri)
                ? self::fromStr($uri->toStr())
                : self::fromStr($uri);
        } catch (UriException) {
            return false;
        }

        if (is_null($call)) {
            return true;
        }

        return (bool) $call($instance);
    }


    public function isEqual(self $uri): bool
    {
        return $this->uri === $uri->toStr();
    }


    public function exists(): bool
    {
        try {
            $headers = get_headers($this->uri);
        } catch (\Exception) {
            return false;
        }

        return (bool) preg_match('/200|301|302/', $headers[0]);
    }


    public function toStr(): string
    {
        return $this->uri;
    }


    /**
     * @throws
     */
    private static function checkAssertion($uri): void
    {
        try {
            Assertion::notNull($uri);
            if (!preg_match(self::uriValidatorPattern(), $uri)) {
                throw new UriException('This is not valid Uri pattern: ' . $uri);
            }
        } catch (\Exception $exception) {
            throw new UriException($exception->getMessage(), 400);
        }
    }


    private static function uriValidatorPattern(): string
    {
        return '~^
            ((http|https)://)?                                                  # protocol
            (([\.\pL\pN-]+:)?([\.\pL\pN-]+)@)?                                  # basic auth
            (
                ([\pL\pN\pS\-\.\{\}])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?)?     # a domain name
                |                                                               # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                              # an IP address
                |                                                               # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]                                                              # an IPv6 address
            )?
            (:[0-9]+)?                                                          # a port (optional)
            (?:/ (?:[\pL\pN\-._\~!$&\'\{\}()*+,;=:@\%]|%%[0-9A-Fa-f]{2})* )*    # a path
            (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?\%]|%%[0-9A-Fa-f]{2})* )? # a query (optional)
            (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?       # a fragment (optional)
        $~ixu';
    }


    private function __construct() {}
}