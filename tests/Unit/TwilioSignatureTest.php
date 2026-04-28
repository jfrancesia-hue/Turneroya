<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\TwilioSignatureVerifier;

/**
 * Tests del verificador de firmas de Twilio.
 * Como no tenemos un fixture oficial de Twilio embebido, generamos firmas
 * con el mismo algoritmo y verificamos round-trip + casos negativos.
 */
final class TwilioSignatureTest extends TestCase
{
    private string $authToken = '12345';

    /**
     * Helper que reproduce el algoritmo Twilio para generar una firma de referencia.
     */
    private function sign(string $url, array $params, string $token): string
    {
        ksort($params);
        $data = $url;
        foreach ($params as $k => $v) {
            $data .= $k . (string) $v;
        }
        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $url = 'https://example.com/api/webhook/whatsapp';
        $params = [
            'From' => 'whatsapp:+5491111111111',
            'To' => 'whatsapp:+5492222222222',
            'Body' => 'hola',
            'MessageSid' => 'SM' . str_repeat('a', 32),
        ];
        $sig = $this->sign($url, $params, $this->authToken);

        $this->assertTrue(
            TwilioSignatureVerifier::verify($url, $params, $sig, $this->authToken)
        );
    }

    public function test_verify_returns_false_when_signature_tampered(): void
    {
        $url = 'https://example.com/api/webhook/whatsapp';
        $params = ['From' => 'whatsapp:+5491111111111', 'Body' => 'hola'];
        $sig = $this->sign($url, $params, $this->authToken);
        // Corromper un byte
        $tampered = substr($sig, 0, -1) . (substr($sig, -1) === 'A' ? 'B' : 'A');

        $this->assertFalse(
            TwilioSignatureVerifier::verify($url, $params, $tampered, $this->authToken)
        );
    }

    public function test_verify_returns_false_when_param_changed(): void
    {
        $url = 'https://example.com/api/webhook/whatsapp';
        $params = ['From' => 'whatsapp:+5491111111111', 'Body' => 'hola'];
        $sig = $this->sign($url, $params, $this->authToken);

        $tamperedParams = $params;
        $tamperedParams['Body'] = 'chau';

        $this->assertFalse(
            TwilioSignatureVerifier::verify($url, $tamperedParams, $sig, $this->authToken)
        );
    }

    public function test_verify_returns_false_when_url_changed(): void
    {
        $url = 'https://example.com/api/webhook/whatsapp';
        $params = ['Body' => 'hola'];
        $sig = $this->sign($url, $params, $this->authToken);

        $this->assertFalse(
            TwilioSignatureVerifier::verify(
                'https://attacker.com/api/webhook/whatsapp',
                $params,
                $sig,
                $this->authToken
            )
        );
    }

    public function test_verify_returns_false_with_empty_signature(): void
    {
        $this->assertFalse(
            TwilioSignatureVerifier::verify('https://x', ['a' => 'b'], '', $this->authToken)
        );
    }

    public function test_verify_returns_false_with_empty_token(): void
    {
        $this->assertFalse(
            TwilioSignatureVerifier::verify('https://x', ['a' => 'b'], 'sig', '')
        );
    }

    public function test_verify_handles_alphabetic_ordering(): void
    {
        // Pasar params en orden no alfabético — la verificación debe igualmente ordenarlos
        $url = 'https://example.com/x';
        $unordered = ['z' => '1', 'a' => '2', 'm' => '3'];
        $sig = $this->sign($url, $unordered, $this->authToken);

        $this->assertTrue(
            TwilioSignatureVerifier::verify($url, $unordered, $sig, $this->authToken)
        );
    }

    public function test_current_url_uses_x_forwarded_proto(): void
    {
        $_SERVER['HTTP_HOST'] = 'turneroya.app';
        $_SERVER['REQUEST_URI'] = '/api/webhook/whatsapp';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertSame(
            'https://turneroya.app/api/webhook/whatsapp',
            TwilioSignatureVerifier::currentUrl()
        );
    }

    public function test_current_url_falls_back_to_https_var(): void
    {
        $_SERVER['HTTP_HOST'] = 'turneroya.app';
        $_SERVER['REQUEST_URI'] = '/x';
        $_SERVER['HTTPS'] = 'on';
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

        $this->assertSame('https://turneroya.app/x', TwilioSignatureVerifier::currentUrl());
    }

    public function test_current_url_defaults_to_http(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8000';
        $_SERVER['REQUEST_URI'] = '/y';
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);

        $this->assertSame('http://localhost:8000/y', TwilioSignatureVerifier::currentUrl());
    }

    /**
     * Fixture semi-oficial: reproduce el algoritmo Twilio con valores del cookbook
     * (https://www.twilio.com/docs/usage/security) y verifica que verify() coincide.
     * El valor hardcodeado de $expected atrapa drift en la implementación de verify().
     */
    public function test_official_twilio_fixture(): void
    {
        // Valores del cookbook oficial de Twilio
        $url = 'https://mycompany.com/myapp.php?foo=1&bar=2';
        $params = [
            'Digits'  => '1234',
            'To'      => '+18005551212',
            'From'    => '+14158675309',
            'Caller'  => '+14158675309',
            'CallSid' => 'CA1234567890ABCDE',
        ];
        $authToken = '12345';

        // Valor precomputado una vez con el algoritmo de referencia — no debe cambiar.
        $expected = $this->computeExpected($url, $params, $authToken);

        // Verificar que el valor no deriva respecto a la última ejecución conocida
        $this->assertSame('RSOYDt4T1cUTdK1PDd93/VVr8B8=', $expected);

        $this->assertTrue(
            TwilioSignatureVerifier::verify($url, $params, $expected, $authToken)
        );
    }

    private function computeExpected(string $url, array $params, string $token): string
    {
        ksort($params);
        $data = $url;
        foreach ($params as $k => $v) {
            $data .= $k . $v;
        }
        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }
}
