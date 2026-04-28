<?php
/**
 * Definición de todas las rutas de TurneroYa.
 */

use TurneroYa\Core\Router;

return function (Router $r): void {

    // Landing + páginas públicas
    $r->get('/', 'HomeController@index');
    $r->get('/pricing', 'BillingController@pricing');
    $r->get('/terms', 'LegalController@terms');
    $r->get('/privacy', 'LegalController@privacy');

    // Auth
    $r->group(['middleware' => ['Guest']], function (Router $r) {
        $r->get('/login', 'AuthController@showLogin');
        $r->post('/login', 'AuthController@login');
        $r->get('/register', 'AuthController@showRegister');
        $r->post('/register', 'AuthController@register');
    });
    $r->post('/logout', 'AuthController@logout');

    // Página pública de reserva
    $r->get('/book/{slug}', 'PublicBookingController@show');
    $r->group(['middleware' => ['PublicBookingRateLimit']], function (Router $r) {
        $r->post('/book/{slug}/slots', 'PublicBookingController@slots');
        $r->post('/book/{slug}/confirm', 'PublicBookingController@confirm');
    });
    $r->get('/book/{slug}/success/{bookingId}', 'PublicBookingController@success');

    // Webhooks públicos (sin auth) — rate-limited a 60 req/min por IP
    $r->group(['middleware' => ['WebhookRateLimit']], function (Router $r) {
        $r->post('/api/webhook/whatsapp', 'WebhookController@whatsapp');
        $r->post('/api/webhook/mercadopago', 'WebhookController@mercadopago');
    });

    // Cron (requiere secret) — rate-limited a 10 req/min por IP
    $r->group(['middleware' => ['CronRateLimit']], function (Router $r) {
        $r->get('/api/reminders/cron', 'CronController@reminders');
        $r->get('/api/payments/expire', 'CronController@expirePayments');
    });

    // Dashboard (requiere auth)
    $r->group(['prefix' => '/dashboard', 'middleware' => ['Auth']], function (Router $r) {
        $r->get('/', 'DashboardController@index');
        $r->get('/onboarding', 'OnboardingController@show');
        $r->post('/onboarding', 'OnboardingController@save');

        // Profesionales
        $r->get('/professionals', 'ProfessionalController@index');
        $r->get('/professionals/create', 'ProfessionalController@create');
        $r->post('/professionals', 'ProfessionalController@store');
        $r->get('/professionals/{id}/edit', 'ProfessionalController@edit');
        $r->post('/professionals/{id}', 'ProfessionalController@update');
        $r->post('/professionals/{id}/delete', 'ProfessionalController@destroy');

        // Servicios
        $r->get('/services', 'ServiceController@index');
        $r->get('/services/create', 'ServiceController@create');
        $r->post('/services', 'ServiceController@store');
        $r->get('/services/{id}/edit', 'ServiceController@edit');
        $r->post('/services/{id}', 'ServiceController@update');
        $r->post('/services/{id}/delete', 'ServiceController@destroy');

        // Horarios
        $r->get('/schedules', 'ScheduleController@index');
        $r->post('/schedules', 'ScheduleController@save');

        // Bloqueos/vacaciones
        $r->get('/blockouts', 'BlockoutController@index');
        $r->post('/blockouts', 'BlockoutController@store');
        $r->post('/blockouts/{id}/delete', 'BlockoutController@destroy');

        // Clientes
        $r->get('/clients', 'ClientController@index');
        $r->get('/clients/create', 'ClientController@create');
        $r->post('/clients', 'ClientController@store');
        $r->get('/clients/{id}', 'ClientController@show');
        $r->post('/clients/{id}', 'ClientController@update');
        $r->post('/clients/{id}/delete', 'ClientController@destroy');

        // Bookings + Calendario
        $r->get('/calendar', 'CalendarController@index');
        $r->get('/api/calendar/events', 'CalendarController@events');
        $r->get('/bookings', 'BookingController@index');
        $r->get('/bookings/create', 'BookingController@create');
        $r->post('/bookings', 'BookingController@store');
        $r->get('/bookings/{id}', 'BookingController@show');
        $r->post('/bookings/{id}/status', 'BookingController@updateStatus');
        $r->post('/bookings/{id}/delete', 'BookingController@destroy');
        $r->post('/api/bookings/available-slots', 'BookingController@availableSlots');

        // Analytics
        $r->get('/analytics', 'AnalyticsController@index');
        $r->get('/api/analytics/data', 'AnalyticsController@data');

        // Bot config
        $r->get('/bot/config', 'BotController@config');
        $r->post('/bot/config', 'BotController@saveConfig');

        // Settings
        $r->get('/settings', 'SettingsController@index');
        $r->post('/settings', 'SettingsController@save');

        // Billing / Suscripciones
        $r->get('/billing', 'BillingController@index');
        $r->post('/billing/subscribe', 'BillingController@subscribe');
        $r->post('/billing/cancel', 'BillingController@cancel');
    });
};
