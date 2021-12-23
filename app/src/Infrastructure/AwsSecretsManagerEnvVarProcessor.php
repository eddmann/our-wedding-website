<?php declare(strict_types=1);

namespace App\Infrastructure;

use AsyncAws\SecretsManager\SecretsManagerClient;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final class AwsSecretsManagerEnvVarProcessor implements EnvVarProcessorInterface
{
    private const VALUE_PREFIX = 'sm:';
    private const VALUE_TTL = 900; // 15 mins

    public function __construct(private SecretsManagerClient $client)
    {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string|int
    {
        $envValue = $getEnv($name);

        if (! \str_starts_with($envValue, self::VALUE_PREFIX)) {
            return $envValue;
        }

        return $this->getSecretValue(\mb_substr($envValue, \mb_strlen(self::VALUE_PREFIX)));
    }

    public static function getProvidedTypes(): array
    {
        return [
            'secret' => 'string',
        ];
    }

    private function getSecretValue(string $key): string|int
    {
        [$id, $attribute] = self::splitKeyAttribute($key);

        $value = $this->fetchValueFromSecretsManager($id);

        if ($attribute) {
            $decoded = \json_decode_array($value);

            return $decoded[$attribute];
        }

        return $value;
    }

    private function fetchValueFromSecretsManager(string $id): string
    {
        $value = \apcu_fetch($id);

        if ($value) {
            return $value;
        }

        $newValue = $this->client->getSecretValue(['SecretId' => $id])->getSecretString();

        if ($newValue === null) {
            throw new \RuntimeException("Unable to find {$id} within AWS Secrets Manager");
        }

        \apcu_store($id, $newValue, self::VALUE_TTL);

        return $newValue;
    }

    private static function splitKeyAttribute(string $key): array
    {
        $parts = \explode(':', $key, 2);

        return \count($parts) === 1 ? [$parts[0], null] : $parts;
    }
}
