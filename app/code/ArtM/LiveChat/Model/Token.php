<?php

declare(strict_types=1);

namespace ArtM\LiveChat\Model;

use RuntimeException;

class Token
{
    public function issue(array $claims): string
    {
        $secret = (string) getenv('ARTM_LIVECHAT_SECRET');
        if ($secret === '') {
            $envFile = dirname(__DIR__) . '/server/.env';
            $envContents = is_readable($envFile) ? file_get_contents($envFile) : false;
            if ($envContents !== false && preg_match('/^\s*ARTM_LIVECHAT_SECRET\s*=.*$/m', $envContents, $match)) {
                $settings = parse_ini_string($match[0], false, INI_SCANNER_RAW);
                $secret = (string) ($settings['ARTM_LIVECHAT_SECRET'] ?? '');
            }
        }
        if (strlen($secret) < 32) {
            throw new RuntimeException('Live chat signing secret must contain at least 32 characters.');
        }

        $now = time();
        $header = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = $this->encode($claims + ['iat' => $now, 'exp' => $now + 900]);
        $unsigned = $header . '.' . $payload;

        return $unsigned . '.' . $this->encode(hash_hmac('sha256', $unsigned, $secret, true));
    }

    private function encode(array|string $value): string
    {
        $json = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }
}