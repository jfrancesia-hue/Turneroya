<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Core\SignedToken;

final class SignedTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['APP_KEY'] = 'test-secret-' . str_repeat('x', 32);
    }

    public function test_round_trip_valid(): void
    {
        $token = SignedToken::issue('booking:foo');
        $this->assertTrue(SignedToken::verify('booking:foo', $token));
    }

    public function test_wrong_audience_fails(): void
    {
        $token = SignedToken::issue('booking:foo');
        $this->assertFalse(SignedToken::verify('booking:bar', $token));
    }

    public function test_tampered_hmac_fails(): void
    {
        $token = SignedToken::issue('booking:foo');
        [$ts, $hmac] = explode('.', $token, 2);
        $tampered = $ts . '.' . substr($hmac, 0, -1) . (substr($hmac, -1) === '0' ? '1' : '0');
        $this->assertFalse(SignedToken::verify('booking:foo', $tampered));
    }

    public function test_expired_token_fails(): void
    {
        $now = time();
        $token = SignedToken::issue('booking:foo', $now - 7200);
        $this->assertFalse(SignedToken::verify('booking:foo', $token, 3600, $now));
    }

    public function test_token_within_ttl_passes(): void
    {
        $now = time();
        $token = SignedToken::issue('booking:foo', $now - 1800);
        $this->assertTrue(SignedToken::verify('booking:foo', $token, 3600, $now));
    }

    public function test_future_token_beyond_skew_fails(): void
    {
        $now = time();
        $token = SignedToken::issue('booking:foo', $now + 3600);
        $this->assertFalse(SignedToken::verify('booking:foo', $token, 3600, $now));
    }

    public function test_malformed_token_fails(): void
    {
        $this->assertFalse(SignedToken::verify('booking:foo', ''));
        $this->assertFalse(SignedToken::verify('booking:foo', 'no-dot'));
        $this->assertFalse(SignedToken::verify('booking:foo', '.onlyhmac'));
        $this->assertFalse(SignedToken::verify('booking:foo', 'abc.123'));
    }

    public function test_changing_secret_invalidates_token(): void
    {
        $token = SignedToken::issue('booking:foo');
        $_ENV['APP_KEY'] = 'different-secret-' . str_repeat('y', 32);
        $this->assertFalse(SignedToken::verify('booking:foo', $token));
    }
}
