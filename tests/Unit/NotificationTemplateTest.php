<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Core\Config;
use TurneroYa\Services\NotificationService;

/**
 * Tests de sendWhatsAppTemplate.
 * No podemos golpear el API real de Twilio, pero sí verificar las early-returns
 * (false) cuando faltan credenciales/config — graceful degradation.
 */
final class NotificationTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset config a un estado conocido entre tests.
        Config::set('services.twilio.account_sid', '');
        Config::set('services.twilio.auth_token', '');
        Config::set('services.twilio.whatsapp_from', '');
    }

    public function test_returns_false_when_content_sid_is_empty(): void
    {
        // Aunque el resto esté configurado, si no hay contentSid → false.
        Config::set('services.twilio.account_sid', 'ACxxx');
        Config::set('services.twilio.auth_token', 'token');
        Config::set('services.twilio.whatsapp_from', 'whatsapp:+14155238886');

        $svc = new NotificationService();
        $this->assertFalse($svc->sendWhatsAppTemplate('+5491111111111', '', ['1' => 'Juan']));
    }

    public function test_returns_false_when_whatsapp_from_is_empty(): void
    {
        // Sin whatsapp_from no se puede enviar nada.
        $svc = new NotificationService();
        $this->assertFalse($svc->sendWhatsAppTemplate('+5491111111111', 'HXabcd', ['1' => 'Juan']));
    }

    public function test_returns_false_when_twilio_credentials_missing(): void
    {
        // whatsapp_from + contentSid presentes pero faltan account_sid/auth_token → twilio() devuelve null → false.
        Config::set('services.twilio.whatsapp_from', 'whatsapp:+14155238886');

        $svc = new NotificationService();
        $this->assertFalse($svc->sendWhatsAppTemplate('+5491111111111', 'HXabcd', ['1' => 'Juan']));
    }

    public function test_returns_false_when_only_account_sid_set(): void
    {
        // account_sid presente pero auth_token vacío → twilio() devuelve null → false.
        Config::set('services.twilio.whatsapp_from', 'whatsapp:+14155238886');
        Config::set('services.twilio.account_sid', 'ACxxx');

        $svc = new NotificationService();
        $this->assertFalse($svc->sendWhatsAppTemplate('+5491111111111', 'HXabcd', ['1' => 'Juan']));
    }
}
