<?php
// RAC 2026-10-22-FX-03-CA | ECB EUR/USD orientacni prevod | PHP 7.4+
// Bezpecne selhani: neoverene kurzy se nezobrazuji. Detail chyby jde do PHP error logu.
function racFxDisplayRates(): ?array {
    static $done = false, $value = null;
    if ($done) return $value;
    $done = true;
    // Vlastni verejny CA bundle RAC; nikdy neobchazime TLS overovani.
    $caFile = __DIR__ . '/certs/rac-ca-bundle.pem';
    if (!is_readable($caFile)) {
        error_log('RAC FX CA: Chybi /inc/certs/rac-ca-bundle.pem (certifikaty).');
        return null;
    }
    $now = time();
    $file = rtrim(sys_get_temp_dir(), '/\\') . '/rac_ecb_display_rates_v1.json';
    $cached = null;
    if (is_readable($file)) {
        $old = json_decode((string)@file_get_contents($file), true);
        if (is_array($old) && isset($old['fetched'], $old['CZK'], $old['USD']) &&
            is_numeric($old['CZK']) && is_numeric($old['USD']) &&
            (float)$old['CZK'] > 5 && (float)$old['USD'] > .2 && (int)$old['fetched'] <= $now) {
            $cached = $old;
        }
    }
    if ($cached && $now - (int)$cached['fetched'] < 86400) {
        return $value = ['CZK'=>(float)$cached['CZK'], 'USD'=>(float)$cached['USD']];
    }
    $urls = [
        'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
        'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.csv'
    ];
    foreach ($urls as $url) {
        $body = false;
        $error = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>2,
                    CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>9,
                    CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
                    CURLOPT_CAINFO=>$caFile,
                    CURLOPT_USERAGENT=>'RAC-FX/2.0 (ECB reference rates)'
                ]);
                $body = curl_exec($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                if ($status !== 200) { $body = false; $error = 'HTTP '.$status.' '.$error; }
            }
        } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            $ctx = stream_context_create(['http'=>['timeout'=>9, 'follow_location'=>1, 'max_redirects'=>3, 'user_agent'=>'RAC-FX/2.0'],
                                          'ssl'=>['verify_peer'=>true, 'verify_peer_name'=>true, 'cafile'=>$caFile]]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body === false) $error = 'HTTPS stream failure';
        } else {
            $error = 'Neither cURL nor allow_url_fopen available';
        }
        if (!is_string($body) || strlen($body) < 10 || strlen($body) > 100000) {
            error_log('RAC FX ECB request failed: '. $error);
            continue;
        }
        $czk = null; $usd = null;
        if (substr($url, -4) === '.xml') {
            if (preg_match_all('~<Cube\b[^>]*\bcurrency\s*=\s*["\']([A-Z]{3})["\'][^>]*\brate\s*=\s*["\']([0-9.]+)["\']~i', $body, $hits, PREG_SET_ORDER)) {
                foreach ($hits as $hit) {
                    if ($hit[1] === 'CZK') $czk = (float)$hit[2];
                    if ($hit[1] === 'USD') $usd = (float)$hit[2];
                }
            }
        } else {
            $lines = preg_split('/\r\n|\n|\r/', trim($body));
            if (count($lines) >= 2) {
                $head = str_getcsv($lines[0]); $rates = str_getcsv($lines[1]);
                foreach ($head as $i => $currency) {
                    if (trim($currency) === 'CZK' && isset($rates[$i])) $czk = (float)trim($rates[$i]);
                    if (trim($currency) === 'USD' && isset($rates[$i])) $usd = (float)trim($rates[$i]);
                }
            }
        }
        if ($czk !== null && $usd !== null && $czk > 5 && $czk < 100 && $usd > .2 && $usd < 4) {
            $latest = ['CZK'=>$czk, 'USD'=>$usd, 'fetched'=>$now];
            $tmp = @tempnam(sys_get_temp_dir(), 'racfx_');
            if ($tmp !== false) {
                @file_put_contents($tmp, json_encode($latest));
                if (!@rename($tmp, $file)) @unlink($tmp);
            } else {
                error_log('RAC FX cache: unable to create temp file');
            }
            return $value = ['CZK'=>$czk, 'USD'=>$usd];
        }
        error_log('RAC FX ECB: invalid/unparseable '.(substr($url,-4)==='.xml'?'XML':'CSV').' rates');
    }
    // Pri docasnem vypadku je mozne pouzit maximalne sedm dni stare overene kurzy.
    if ($cached && $now - (int)$cached['fetched'] <= 7*86400) {
        return $value = ['CZK'=>(float)$cached['CZK'], 'USD'=>(float)$cached['USD']];
    }
    return null;
}
