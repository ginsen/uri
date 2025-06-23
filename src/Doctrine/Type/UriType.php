<?php

declare(strict_types=1);

namespace Ginsen\Uri\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Ginsen\Uri\ValueObject\Uri;

class UriType extends Type
{
    public const NAME = 'uri';


    public function getName(): string
    {
        return static::NAME;
    }


    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'text';
    }


    public function convertToPHPValue($value, AbstractPlatform $platform): ?Uri
    {
        if (null == $value || $value instanceof Uri) {
            return $value;
        }

        return Uri::fromStr($value);
    }


    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Uri) {
            return $value->toStr();
        }

        throw InvalidType::new($value, static::class, ['null', Uri::class]);
    }
}
