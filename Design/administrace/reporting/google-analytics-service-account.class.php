<?php
// RAC Reporting – bezpečné nastavení servisního účtu GA4 | PHP 7.4+
declare(strict_types=1);

final class RacGa4ServiceAccount
{
    public static function configFile(): string
    {
        return __DIR__ . '/private/ga4-service-account.php';
    }

    public static function load(): ?array
    {
        $file = self::configFile();
        if (!is_file($file)) {
            return null;
        }
        if (!is_readable($file)) {
            throw new RuntimeException('Nastavení servisního účtu GA4 nelze přečíst.');
        }
        $config = require $file;
        if (!is_array($config) || !isset($config['property_id'], $config['key_file'])) {
            throw new RuntimeException('Nastavení servisního účtu GA4 nemá platný formát.');
        }
        return $config;
    }

    public static function validate(string $propertyId, string $keyFile): array
    {
        $propertyId = trim($propertyId);
        $keyFile = trim($keyFile);
        if (!preg_match('/^[0-9]+$/D', $propertyId)) {
            throw new RuntimeException('Zadejte číselné GA4 Property ID.');
        }
        if ($keyFile === '') {
            throw new RuntimeException('Zadejte absolutní cestu k JSON klíči servisního účtu.');
        }

        $resolved = realpath($keyFile);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw new RuntimeException('Soubor JSON klíče nebyl na serveru nalezen nebo není čitelný.');
        }
        $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot === false) {
            throw new RuntimeException('Nelze ověřit veřejný kořen webu. Klíč nelze bezpečně aktivovat.');
        }
        $root = rtrim($documentRoot, DIRECTORY_SEPARATOR);
        if ($resolved === $root || strpos($resolved, $root . DIRECTORY_SEPARATOR) === 0) {
            // Výjimka pro hosting RAC bez přístupu mimo www:
            // povolíme pouze www/rac-private s lokální .htaccess ochranou.
            $privateDir = realpath($root . DIRECTORY_SEPARATOR . 'rac-private');
            $htaccessFile = $privateDir !== false ? $privateDir . DIRECTORY_SEPARATOR . '.htaccess' : '';
            if ($privateDir === false
                || dirname($resolved) !== $privateDir
                || strtolower((string)pathinfo($resolved, PATHINFO_EXTENSION)) !== 'json'
                || $htaccessFile === ''
                || !is_file($htaccessFile)
                || !is_readable($htaccessFile)) {
                throw new RuntimeException(
                    'Klíč uvnitř webového kořene je povolen jen v /rac-private '
                    . 's platným souborem .htaccess. Jinak musí být mimo DocumentRoot.'
                );
            }
            $denyRules = file_get_contents($htaccessFile);
            if ($denyRules === false || !preg_match('/^\\s*(?:Require\\s+all\\s+denied|Deny\\s+from\\s+all)\\s*$/mi', $denyRules)) {
                throw new RuntimeException(
                    'Soubor rac-private/.htaccess musí obsahovat pravidlo „Require all denied“ '
                    . 'nebo „Deny from all“. Před vložením klíče ověřte také HTTP 403 '
                    . 'na neškodném souboru ve stejné složce.'
                );
            }
        }

        if (filesize($resolved) === false || filesize($resolved) > 131072) {
            throw new RuntimeException('JSON klíč má neplatnou velikost.');
        }
        $raw = file_get_contents($resolved);
        $key = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($key)
            || ($key['type'] ?? '') !== 'service_account'
            || !filter_var((string)($key['client_email'] ?? ''), FILTER_VALIDATE_EMAIL)
            || empty($key['private_key'])) {
            throw new RuntimeException('JSON neobsahuje platný klíč servisního účtu Google.');
        }
        if (openssl_pkey_get_private((string)$key['private_key']) === false) {
            throw new RuntimeException('Soukromý klíč v JSON není platný.');
        }
        return array('property_id' => $propertyId, 'key_file' => $resolved);
    }

    public static function save(array $settings): void
    {
        $dir = dirname(self::configFile());
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Složku pro nastavení servisního účtu nelze vytvořit.');
        }
        $denyFile = $dir . '/.htaccess';
        if (!is_file($denyFile)) {
            $rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
            if (file_put_contents($denyFile, $rules, LOCK_EX) === false) {
                throw new RuntimeException('Nepodařilo se zabezpečit složku nastavení.');
            }
        }
        $config = array(
            'property_id' => (string)$settings['property_id'],
            'key_file' => (string)$settings['key_file'],
        );
        $php = "<?php\n// RAC GA4: cesta k privátnímu JSON klíči, bez samotných přihlašovacích údajů.\nreturn " . var_export($config, true) . ";\n";
        $tmp = self::configFile() . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, $php, LOCK_EX) === false) {
            throw new RuntimeException('Nastavení servisního účtu nelze uložit.');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, self::configFile())) {
            @unlink($tmp);
            throw new RuntimeException('Nastavení servisního účtu nelze dokončit.');
        }
        @chmod(self::configFile(), 0600);
    }

    public static function disable(): void
    {
        $file = self::configFile();
        if (is_file($file) && !@unlink($file)) {
            throw new RuntimeException('Nastavení servisního účtu se nepodařilo deaktivovat.');
        }
    }
}
