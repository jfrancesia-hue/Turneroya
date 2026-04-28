<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\MercadoPagoSignatureVerifier;

final class MercadoPagoSignatureTest extends TestCase
{
    private string $secret = 'super-secret';

    private function buildSignature(string $dataId, string $requestId, string $ts, string $secret): string
    {
        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $requestId, $ts);
        $v1 = hash_hmac('sha256', $manifest, $secret);
        return "ts={$ts},v1={$v1}";
    }

    public function test_verify_valid_signature(): void
    {
        $ts = (string) time();
        $dataId = '987654321';
        $requestId = 'req-uuid-123';
        $sig = $this->buildSignature($dataId, $requestId, $ts, $this->secret);

        $this->assertTrue(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_with_extra_whitespace(): void
    {
        $ts = (string) time();
        $dataId = '111';
        $requestId = 'req-1';
        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $requestId, $ts);
        $v1 = hash_hmac('sha256', $manifest, $this->secret);
        // Cabecera real puede traer espacios alrededor de las comas
        $sig = "ts= {$ts} , v1= {$v1} ";

        $this->assertTrue(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_fails_when_v1_tampered(): void
    {
        $ts = (string) time();
        $dataId = '987654321';
        $requestId = 'req-uuid-123';
        $sig = $this->buildSignature($dataId, $requestId, $ts, $this->secret);
        // Corromper último char del v1
        $sig = substr($sig, 0, -1) . (str_ends_with($sig, 'a') ? 'b' : 'a');

        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_fails_when_data_id_changed(): void
    {
        $ts = (string) time();
        $sig = $this->buildSignature('111', 'req-1', $ts, $this->secret);
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify($sig, 'req-1', '222', $this->secret)
        );
    }

    public function test_verify_fails_when_request_id_changed(): void
    {
        $ts = (string) time();
        $sig = $this->buildSignature('111', 'req-1', $ts, $this->secret);
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify($sig, 'req-OTHER', '111', $this->secret)
        );
    }

    public function test_verify_fails_with_wrong_secret(): void
    {
        $ts = (string) time();
        $sig = $this->buildSignature('111', 'req-1', $ts, $this->secret);
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify($sig, 'req-1', '111', 'otro-secret')
        );
    }

    public function test_verify_fails_when_missing_ts(): void
    {
        $ts = (string) time();
        $manifest = sprintf('id:111;request-id:req-1;ts:%s;', $ts);
        $v1 = hash_hmac('sha256', $manifest, $this->secret);
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify("v1={$v1}", 'req-1', '111', $this->secret)
        );
    }

    public function test_verify_fails_outside_replay_window(): void
    {
        // ts de hace 10 minutos — fuera de la ventana de 5 min
        $ts = (string) (time() - 600);
        $dataId = '111';
        $requestId = 'req-old';
        $sig = $this->buildSignature($dataId, $requestId, $ts, $this->secret);

        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_succeeds_inside_replay_window(): void
    {
        // ts de hace 60 segundos — dentro de la ventana
        $ts = (string) (time() - 60);
        $dataId = '111';
        $requestId = 'req-fresh';
        $sig = $this->buildSignature($dataId, $requestId, $ts, $this->secret);

        $this->assertTrue(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_accepts_ts_in_milliseconds(): void
    {
        // MP a veces envía ts en milisegundos
        $tsMs = (string) (time() * 1000);
        $dataId = '222';
        $requestId = 'req-ms';
        $sig = $this->buildSignature($dataId, $requestId, $tsMs, $this->secret);

        $this->assertTrue(
            MercadoPagoSignatureVerifier::verify($sig, $requestId, $dataId, $this->secret)
        );
    }

    public function test_verify_fails_when_missing_v1(): void
    {
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify('ts=1704908010', 'req-1', '111', $this->secret)
        );
    }

    public function test_verify_fails_with_empty_inputs(): void
    {
        $this->assertFalse(MercadoPagoSignatureVerifier::verify('', 'r', 'd', $this->secret));
        $this->assertFalse(MercadoPagoSignatureVerifier::verify('ts=1,v1=x', '', 'd', $this->secret));
        $this->assertFalse(MercadoPagoSignatureVerifier::verify('ts=1,v1=x', 'r', '', $this->secret));
        $this->assertFalse(MercadoPagoSignatureVerifier::verify('ts=1,v1=x', 'r', 'd', ''));
    }

    public function test_verify_fails_with_garbage_header(): void
    {
        $this->assertFalse(
            MercadoPagoSignatureVerifier::verify('not-a-valid-format', 'r', 'd', $this->secret)
        );
    }
}
