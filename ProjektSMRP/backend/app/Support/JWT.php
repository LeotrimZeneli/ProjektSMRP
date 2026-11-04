<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\ApiException;

final class JWT
{
    private static ?array $config = null;

    public static function issue(array $user): string
    {
        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => self::config()['issuer'],
            'sub' => (string) $user['id'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + self::config()['lifetime_seconds'],
            'jti' => bin2hex(random_bytes(16)),
        ];
        $encodedHeader = self::encode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = self::encode(json_encode($payload, JSON_THROW_ON_ERROR));
        $unsigned = $encodedHeader . '.' . $encodedPayload;

        return $unsigned . '.' . self::encode(hash_hmac('sha256', $unsigned, self::secret(), true));
    }

    public static function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new ApiException(401, 'Invalid or expired bearer token.');
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $expected = self::encode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, self::secret(), true));
        if (!hash_equals($expected, $signature)) {
            throw new ApiException(401, 'Invalid or expired bearer token.');
        }

        $header = json_decode(self::decode($encodedHeader), true);
        $payload = json_decode(self::decode($encodedPayload), true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256' ||
            !is_array($payload) || ($payload['iss'] ?? null) !== self::config()['issuer'] ||
            !isset($payload['sub'], $payload['exp']) || !is_numeric($payload['exp']) ||
            (int) $payload['exp'] <= time()) {
            throw new ApiException(401, 'Invalid or expired bearer token.');
        }

        return $payload;
    }

    private static function secret(): string
    {
        $configuredSecret = self::config()['secret'];
        if (is_string($configuredSecret) && $configuredSecret !== '') {
            if (strlen($configuredSecret) < 32) {
                throw new \RuntimeException('JWT_SECRET must be at least 32 characters long.');
            }

            return $configuredSecret;
        }

        $path = dirname(__DIR__, 2) . '/storage/jwt_secret';
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not create the private JWT key directory.');
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Could not open the local JWT key file.');
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new \RuntimeException('Could not lock the local JWT key file.');
        }

        $secret = stream_get_contents($handle);
        if ($secret === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new \RuntimeException('Could not read the local JWT key.');
        }
        $secret = trim($secret);
        if ($secret === '') {
            $secret = bin2hex(random_bytes(64));
            if (!rewind($handle) || !ftruncate($handle, 0) ||
                fwrite($handle, $secret) !== strlen($secret) || !fflush($handle)) {
                flock($handle, LOCK_UN);
                fclose($handle);
                throw new \RuntimeException('Could not securely save the local JWT key.');
            }
        }
        flock($handle, LOCK_UN);
        if (!fclose($handle) || !chmod($path, 0600)) {
            throw new \RuntimeException('Could not secure the local JWT key file.');
        }

        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET must be at least 32 characters long.');
        }

        return $secret;
    }

    private static function config(): array
    {
        return self::$config ??= require dirname(__DIR__, 2) . '/config/jwt_config.php';
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new ApiException(401, 'Invalid or expired bearer token.');
        }

        return $decoded;
    }
}
