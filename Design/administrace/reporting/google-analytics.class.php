<?php
// verze 2026-11-03-11.22
// RAC Reporting – Google Analytics 4 REST klient | PHP 7.4+ | bez Composeru

declare(strict_types=1);

final class GoogleAnalyticsRestClient
{
    private $propertyId;
    private $keyFile;
    private $accessToken = '';
    private $accessTokenExpires = 0;

    public function __construct(string $propertyId, string $keyFile)
    {
        $propertyId = trim($propertyId);
        if ($propertyId === '' || !preg_match('/^[0-9]+$/D', $propertyId)) {
            throw new InvalidArgumentException('Neplatné GA4 Property ID.');
        }
        if (!is_file($keyFile) || !is_readable($keyFile)) {
            throw new RuntimeException('Chybí nebo nelze přečíst JSON klíč servisního účtu Google Analytics.');
        }

        $this->propertyId = $propertyId;
        $this->keyFile = $keyFile;
    }

    public function runReport(array $body): array
    {
        return $this->analyticsRequest('runReport', $body);
    }

    public function runRealtimeReport(array $body): array
    {
        return $this->analyticsRequest('runRealtimeReport', $body);
    }

    private function analyticsRequest(string $method, array $body): array
    {
        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($this->propertyId) . ':' . $method;
        return $this->requestJson('POST', $url, $body, array(
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json; charset=utf-8',
        ));
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken !== '' && time() < ($this->accessTokenExpires - 60)) {
            return $this->accessToken;
        }

        $raw = file_get_contents($this->keyFile);
        if ($raw === false) {
            throw new RuntimeException('JSON klíč servisního účtu nelze načíst.');
        }

        $key = json_decode($raw, true);
        if (!is_array($key)) {
            throw new RuntimeException('JSON klíč servisního účtu není platný JSON.');
        }

        $clientEmail = trim((string)($key['client_email'] ?? ''));
        $privateKey = (string)($key['private_key'] ?? '');
        $tokenUri = trim((string)($key['token_uri'] ?? 'https://oauth2.googleapis.com/token'));

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('JSON klíč neobsahuje client_email nebo private_key.');
        }
        if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('client_email v JSON klíči není platný e-mail.');
        }
        if (!filter_var($tokenUri, FILTER_VALIDATE_URL) || stripos($tokenUri, 'https://') !== 0) {
            throw new RuntimeException('token_uri v JSON klíči není platná HTTPS adresa.');
        }

        $now = time();
        $header = array('alg' => 'RS256', 'typ' => 'JWT');
        $claims = array(
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => $tokenUri,
            'iat' => $now - 5,
            'exp' => $now + 3600,
        );

        $segments = array(
            $this->base64UrlEncode($this->jsonEncode($header)),
            $this->base64UrlEncode($this->jsonEncode($claims)),
        );
        $signingInput = implode('.', $segments);

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false) {
            throw new RuntimeException('Soukromý klíč servisního účtu nelze načíst.');
        }

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        if ($signed !== true) {
            throw new RuntimeException('Nepodařilo se podepsat JWT pro Google OAuth.');
        }

        $segments[] = $this->base64UrlEncode($signature);
        $assertion = implode('.', $segments);

        $response = $this->requestForm($tokenUri, array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ));

        $accessToken = trim((string)($response['access_token'] ?? ''));
        if ($accessToken === '') {
            throw new RuntimeException('Google OAuth nevrátil access token.');
        }

        $expiresIn = (int)($response['expires_in'] ?? 3600);
        $this->accessToken = $accessToken;
        $this->accessTokenExpires = time() + max(60, $expiresIn);

        return $this->accessToken;
    }

    private function requestForm(string $url, array $data): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Nelze inicializovat cURL.');
        }

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ),
            CURLOPT_USERAGENT => 'RAC-Reporting/1.0',
        ));

        return $this->finishRequest($ch, $url);
    }

    private function requestJson(string $method, string $url, array $body, array $headers): array
    {
        $payload = $this->jsonEncode($body);
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Nelze inicializovat cURL.');
        }

        $headers[] = 'Accept: application/json';

        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'RAC-Reporting/1.0',
        ));

        return $this->finishRequest($ch, $url);
    }

    private function finishRequest($ch, string $url): array
    {
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Chyba spojení s Google API: ' . $error);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            if ($status >= 200 && $status < 300 && trim((string)$response) === '') {
                return array();
            }
            throw new RuntimeException('Google API vrátilo neplatnou odpověď.');
        }

        if ($status < 200 || $status >= 300) {
            $message = '';
            if (isset($decoded['error']['message'])) {
                $message = trim((string)$decoded['error']['message']);
            } elseif (isset($decoded['error_description'])) {
                $message = trim((string)$decoded['error_description']);
            } elseif (isset($decoded['error'])) {
                $message = is_string($decoded['error']) ? trim($decoded['error']) : '';
            }
            if ($message === '') {
                $message = 'HTTP ' . $status . ' při volání ' . $url;
            }
            throw new RuntimeException('Google API: ' . $message);
        }

        return $decoded;
    }

    private function jsonEncode(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Nepodařilo se vytvořit JSON požadavek.');
        }
        return $json;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
