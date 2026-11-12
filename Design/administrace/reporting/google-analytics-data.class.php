<?php
// verze 2026-11-03-13.31
// RAC Google Analytics Data API helper | PHP 7.4+
declare(strict_types=1);

final class GoogleAnalyticsDataClient
{
    private string $propertyId;
    private string $accessToken;

    public function __construct(string $propertyId, string $accessToken)
    {
        $propertyId = trim($propertyId);
        $accessToken = trim($accessToken);
        if ($propertyId === '' || !preg_match('/^\d+$/', $propertyId)) {
            throw new RuntimeException('Google Analytics Property ID není platné.');
        }
        if ($accessToken === '') {
            throw new RuntimeException('Chybí Google OAuth access token.');
        }
        $this->propertyId = $propertyId;
        $this->accessToken = $accessToken;
    }

    public function runReport(array $request): array
    {
        return $this->request('https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($this->propertyId) . ':runReport', $request);
    }

    public function runRealtimeReport(array $request): array
    {
        return $this->request('https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($this->propertyId) . ':runRealtimeReport', $request);
    }

    private function request(string $url, array $request): array
    {
        $json = json_encode($request, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Požadavek Google Analytics nelze převést do JSON.');
        }
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
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 40,
        ));
        $response = curl_exec($curl);
        if ($response === false) {
            $message = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Chyba cURL: ' . $message);
        }
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google Analytics API vrátilo neplatnou odpověď.');
        }
        if ($status < 200 || $status >= 300) {
            $message = isset($decoded['error']['message']) ? (string)$decoded['error']['message'] : 'Neznámá chyba Google Analytics API.';
            throw new RuntimeException('Google Analytics API chyba (' . $status . '): ' . $message);
        }
        return $decoded;
    }
}
