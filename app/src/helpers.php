<?php declare(strict_types=1);

if (! \function_exists('json_encode_array')) {
    /**
     * @param mixed $value
     * @psalm-pure
     * @throws \JsonException
     */
    function json_encode_array(array $value, int $options = \JSON_THROW_ON_ERROR, int $depth = 512): string
    {
        return \json_encode($value, $options, $depth);
    }
}

if (! \function_exists('json_decode_array')) {
    /**
     * @psalm-pure
     * @throws \JsonException
     */
    function json_decode_array(string $json, bool $assoc = true, int $depth = 512, int $options = \JSON_THROW_ON_ERROR): array
    {
        return \json_decode($json, $assoc, $depth, $options) ?? [];
    }
}

if (! \function_exists('datetime_timestamp')) {
    /**
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    function datetime_timestamp(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s.u O');
    }
}
