<?php
/**
 * Seed: datos de ejemplo para TurneroYa.
 * - 1 peluquería "Belleza Pura"
 * - 3 profesionales (María, Sofía, Carlos)
 * - 5 servicios (Corte, Color, Brushing, Manicura, Peinado)
 * - Horarios L-V 9-19
 * - 10 clientes
 * - 20 bookings en los próximos 14 días
 * - 1 usuario admin: admin@demo.com / demo1234
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TurneroYa\Core\Config;
use TurneroYa\Core\Database;
use TurneroYa\Core\Auth;
use TurneroYa\Models\User;
use TurneroYa\Models\Business;
use TurneroYa\Models\Service;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Client;

Dotenv::createImmutable(BASE_PATH)->load();
Config::load(BASE_PATH . '/config');
date_default_timezone_set((string) Config::get('app.timezone', 'America/Argentina/Buenos_Aires'));

echo "[Seed] Iniciando carga de datos demo...\n";

try {
    Database::transaction(function () {
        // 1) Limpiar tablas (orden importante por FK)
        $tables = ['booking_analytics', 'bot_conversations', 'bookings', 'blockouts',
                   'schedules', 'professional_services', 'services', 'professionals',
                   'clients'];
        foreach ($tables as $t) {
            Database::query("DELETE FROM $t");
        }
        Database::query("DELETE FROM users WHERE email = 'admin@demo.com'");
        Database::query("DELETE FROM businesses WHERE slug = 'belleza-pura'");

        // 2) Crear negocio
        $businessId = Business::create([
            'name' => 'Belleza Pura',
            'slug' => 'belleza-pura',
            'type' => 'SALON',
            'description' => 'Peluquería y estética en Palermo',
            'phone' => '+541144556677',
            'whatsapp' => '+5491144556677',
            'email' => 'hola@bellezapura.demo',
            'address' => 'Gorriti 4321',
            'city' => 'Buenos Aires',
            'province' => 'CABA',
            'bot_welcome_message' => 'Atendemos de lunes a viernes de 9 a 19. Somos 3 profesionales: María, Sofía y Carlos.',
        ]);
        echo "  ✓ Negocio creado: $businessId\n";

        // 3) Usuario admin
        $userId = User::create([
            'email' => 'admin@demo.com',
            'password_hash' => Auth::hash('demo1234'),
            'name' => 'Admin Demo',
            'role' => 'OWNER',
            'business_id' => $businessId,
        ]);
        echo "  ✓ Usuario: admin@demo.com / demo1234\n";

        // 4) Servicios
        $services = [
            ['Corte de pelo', 30, 5000, '#ef4444'],
            ['Color', 90, 15000, '#a855f7'],
            ['Brushing', 45, 4500, '#3b82f6'],
            ['Manicura', 60, 6000, '#ec4899'],
            ['Peinado', 45, 5500, '#10b981'],
        ];
        $serviceIds = [];
        foreach ($services as [$name, $duration, $price, $color]) {
            $serviceIds[] = Service::create([
                'business_id' => $businessId,
                'name' => $name,
                'duration' => $duration,
                'price' => $price,
                'color' => $color,
            ]);
        }
        echo "  ✓ " . count($serviceIds) . " servicios creados\n";

        // 5) Profesionales
        $pros = [
            ['María García', 'Especialista en color', '#8b5cf6'],
            ['Sofía Rodríguez', 'Cortes y peinados', '#ec4899'],
            ['Carlos Ruiz', 'Cortes masculinos', '#3b82f6'],
        ];
        $proIds = [];
        foreach ($pros as [$name, $spec, $color]) {
            $proIds[] = Professional::create([
                'business_id' => $businessId,
                'name' => $name,
                'specialization' => $spec,
                'color' => $color,
            ]);
        }
        echo "  ✓ " . count($proIds) . " profesionales creados\n";

        // 6) Asignar servicios a profesionales (todos hacen todo para simplificar)
        foreach ($proIds as $pid) {
            foreach ($serviceIds as $sid) {
                Database::insert('professional_services', [
                    'professional_id' => $pid,
                    'service_id' => $sid,
                ]);
            }
        }

        // 7) Horarios L-V 9-19 con break 13-14 (a nivel negocio)
        for ($dow = 1; $dow <= 5; $dow++) {
            Database::insert('schedules', [
                'day_of_week' => $dow,
                'start_time' => '09:00',
                'end_time' => '19:00',
                'break_start' => '13:00',
                'break_end' => '14:00',
                'business_id' => $businessId,
            ]);
        }
        // Sábado 10-14
        Database::insert('schedules', [
            'day_of_week' => 6,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'business_id' => $businessId,
        ]);
        echo "  ✓ Horarios L-V 9-19 (break 13-14) + Sáb 10-14\n";

        // 8) Clientes
        $clientNames = [
            ['Laura Fernández', '+5491155556677'],
            ['Diego Pérez', '+5491155557788'],
            ['Mariana López', '+5491155558899'],
            ['Juan Sosa', '+5491155559900'],
            ['Valentina Díaz', '+5491155551122'],
            ['Tomás Morales', '+5491155552233'],
            ['Camila Ruiz', '+5491155553344'],
            ['Lucas Álvarez', '+5491155554455'],
            ['Florencia Méndez', '+5491155555566'],
            ['Nicolás Torres', '+5491155556611'],
        ];
        $clientIds = [];
        foreach ($clientNames as [$name, $phone]) {
            $clientIds[] = Client::create([
                'business_id' => $businessId,
                'name' => $name,
                'phone' => $phone,
                'whatsapp_number' => $phone,
            ]);
        }
        echo "  ✓ " . count($clientIds) . " clientes creados\n";

        // 9) 20 bookings aleatorios en próximos 14 días
        $sources = ['WEB', 'WHATSAPP_BOT', 'MANUAL'];
        $bookingCount = 0;
        for ($i = 0; $i < 20; $i++) {
            $daysAhead = random_int(0, 13);
            $date = (new \DateTimeImmutable('today'))->modify("+$daysAhead days");
            // Lunes a viernes (0=dom, 6=sáb)
            if ((int) $date->format('w') === 0) $date = $date->modify('+1 day');

            $hour = random_int(9, 17);
            if ($hour === 13) $hour = 14;
            $minute = [0, 30][random_int(0, 1)];
            $startTime = sprintf('%02d:%02d', $hour, $minute);

            $sIdx = array_rand($serviceIds);
            $duration = $services[$sIdx][1];
            $endTime = (new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $startTime))
                ->modify("+$duration minutes")->format('H:i');

            try {
                Database::insert('bookings', [
                    'business_id' => $businessId,
                    'client_id' => $clientIds[array_rand($clientIds)],
                    'service_id' => $serviceIds[$sIdx],
                    'professional_id' => $proIds[array_rand($proIds)],
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => ['CONFIRMED', 'CONFIRMED', 'CONFIRMED', 'PENDING'][random_int(0, 3)],
                    'source' => $sources[array_rand($sources)],
                    'price' => $services[$sIdx][2],
                ]);
                $bookingCount++;
            } catch (\Throwable $e) {
                // skip dobles bookings
            }
        }
        echo "  ✓ $bookingCount bookings creados\n";
    });

    echo "\n✓ Seed completado exitosamente\n";
    echo "\nCredenciales:\n";
    echo "  Email:    admin@demo.com\n";
    echo "  Password: demo1234\n";
    echo "\nURLs:\n";
    echo "  Dashboard: http://localhost:8000/login\n";
    echo "  Reservas:  http://localhost:8000/book/belleza-pura\n";
} catch (\Throwable $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
