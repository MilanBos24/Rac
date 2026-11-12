<?php
// verze 2026-11-03-13.31
// RAC Reporting OAuth helper | PHP 7.4+
declare(strict_types=1);

final class GoogleAnalyticsOAuth
{
    private array $config;

    public function __construct(array $config)
    {
        foreach (array('client_id', 'client_secret', 'redirect_uri', 'scope', 'authorization_endpoint', 'token_endpoint', 'encryption_key') as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException('V OAuth konfiguraci chybí položka: ' . $key);
            }
        }
        $this->config = $config;
    }

    public function getAuthorizationUrl(string $state): string
    {
        return $this->config['authorization_endpoint'] . '?' . http_build_query(array(
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $this->config['scope'],
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ), '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code): array
    {
        return $this->requestForm($this->config['token_endpoint'], array(
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ));
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->requestForm($this->config['token_endpoint'], array(
            'refresh_token' => $refreshToken,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'refresh_token',
        ));
    }

    public function listAccountSummaries(string $accessToken): array
    {
        $url = 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200';
        $all = array();

        do {
            $response = $this->requestJson($url, 'GET', null, $accessToken);
            if (!empty($response['accountSummaries']) && is_array($response['accountSummaries'])) {
                $all = array_merge($all, $response['accountSummaries']);
            }
            $pageToken = isset($response['nextPageToken']) ? (string)$response['nextPageToken'] : '';
            $url = 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200'
                . ($pageToken !== '' ? '&pageToken=' . rawurlencode($pageToken) : '');
        } while ($pageToken !== '');

        return $all;
    }

    public function revokeToken(string $token): void
    {
        $url = 'https://oauth2.googleapis.com/revoke?' . http_build_query(array('token' => $token), '', '&', PHP_QUERY_RFC3986);
        $this->requestRaw($url, 'POST', array('Content-Type: application/x-www-form-urlencoded'), '');
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Refresh token se nepodařilo zašifrovat.');
        }

        return base64_encode($iv) . '.' . base64_encode($tag) . '.' . base64_encode($ciphertext);
    }

    public function decrypt(string $payload): string
    {
        $parts = explode('.', $payload);
        if (count($parts) !== 3) {
            throw new RuntimeException('Uložený refresh token má neplatný formát.');
        }

        $iv = base64_decode($parts[0], true);
        $tag = base64_decode($parts[1], true);
        $ciphertext = base64_decode($parts[2], true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new RuntimeException('Uložený refresh token nelze dekódovat.');
        }

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->getEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new RuntimeException('Refresh token se nepodařilo dešifrovat.');
        }

        return $plaintext;
    }

    private function getEncryptionKey(): string
    {
        $key = base64_decode((string)$this->config['encryption_key'], true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('OAuth encryption_key musí být Base64 hodnota přesně 32 bajtů.');
        }
        return $key;
    }

    private function requestForm(string $url, array $fields): array
    {
        $body = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
        $raw = $this->requestRaw($url, 'POST', array('Content-Type: application/x-www-form-urlencoded'), $body);
        $decoded = json_decode($raw['body'], true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Google OAuth vrátil neplatnou odpověď.');
        }
        if ($raw['status'] < 200 || $raw['status'] >= 300) {
            $message = isset($decoded['error_description']) ? (string)$decoded['error_description'] : 'Neznámá chyba OAuth.';
            throw new RuntimeException('Google OAuth chyba (' . $raw['status'] . '): ' . $message);
        }
        return $decoded;
    }

    private function requestJson(string $url, string $method, ?array $body, string $accessToken): array
    {
        $headers = array('Authorization: Bearer ' . $accessToken, 'Accept: application/json');
        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                throw new RuntimeException('Požadavek Google API nelze převést do JSON.');
            }
        }

        $raw = $this->requestRaw($url, $method, $headers, $payload);
        $decoded = json_decode($raw['body'], true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google API vrátilo neplatnou odpověď.');
        }
        if ($raw['status'] < 200 || $raw['status'] >= 300) {
            $message = isset($decoded['error']['message']) ? (string)$decoded['error']['message'] : 'Neznámá chyba Google API.';
            throw new RuntimeException('Google API chyba (' . $raw['status'] . '): ' . $message);
        }
        return $decoded;
    }

    private function requestRaw(string $url, string $method, array $headers, ?string $body): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Na serveru není dostupné PHP rozšíření cURL.');
        }

        $curl = curl_init();
        if ($curl === false) {
            throw new RuntimeException('Nepodařilo se inicializovat cURL.');
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ));
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        if ($response === false) {
            $message = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Chyba cURL: ' . $message);
        }
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array('status' => $status, 'body' => $response);
    }
}
