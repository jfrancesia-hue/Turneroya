<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Verificador de firmas de Twilio para webhooks entrantes.
 *
 * Algoritmo (docs oficiales Twilio):
 *  1. Tomar la URL completa del request (incluye scheme + host + path + query).
 *  2. Si el body es application/x-www-form-urlencoded: ordenar las claves
 *     alfabéticamente y concatenar key+value (sin separador) detrás de la URL.
 *  3. HMAC-SHA1 con el AuthToken como clave.
 *  4. Base64-encode del binario.
 *  5. Comparar con la cabecera X-Twilio-Signature usando hash_equals.
 *
 * Ref: https://www.twilio.com/docs/usage/webhooks/webhooks-security
 */
final class TwilioSignatureVerifier
{
    /**
     * Verifica una firma Twilio.
     *
     * @param string $url       URL absoluta del webhook tal como Twilio la firmó.
     * @param array  $params    Parámetros POST (form-encoded). Si el body es JSON, debe pasarse [] aquí.
     * @param string $signature Valor de la cabecera X-Twilio-Signature.
     * @param string $authToken AuthToken de la cuenta Twilio.
     */
    public static function verify(string $url, array $params, string $signature, string $authToken): bool
    {
        if ($signature === '' || $authToken === '') {
            return false;
        }

        // Ordenar claves alfabéticamente y concatenar key+value sin separador
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            // Twilio nunca envía arrays en POST form-encoded, pero PHP puede parsear
            // "Param[]=a&Param[]=b" como array. Defensivo: no spec, no matchea
            // firma legítima — solo evita warning de type coercion.
            $data .= $key . (is_array($value) ? json_encode($value) : (string) $value);
        }

        $computed = base64_encode(hash_hmac('sha1', $data, $authToken, true));
        return hash_equals($computed, $signature);
    }

    /**
     * Construye la URL absoluta del request actual a partir de $_SERVER,
     * respetando X-Forwarded-Proto cuando hay un proxy delante (Render, Cloudflare, etc.).
     */
    public static function currentUrl(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = (string) $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        return $proto . '://' . $host . $uri;
    }
}
