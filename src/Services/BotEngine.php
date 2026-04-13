<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Models\Business;
use TurneroYa\Models\Service as ServiceModel;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Client;
use TurneroYa\Models\Booking;
use TurneroYa\Models\BotConversation;

/**
 * BotEngine — motor conversacional del bot de WhatsApp usando Claude.
 *
 * Usa tool-use para que Claude invoque acciones concretas:
 *  - list_services: listar servicios disponibles
 *  - list_professionals: listar profesionales para un servicio
 *  - find_available_slots: buscar próximos slots libres
 *  - create_booking: crear el turno
 *  - cancel_booking: cancelar turno por número
 *  - get_client_bookings: ver turnos del cliente
 */
final class BotEngine
{
    private ClaudeClient $claude;

    public function __construct(private readonly string $businessId)
    {
        $this->claude = new ClaudeClient(
            (string) config('services.anthropic.api_key'),
            (string) config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
        );
    }

    /**
     * Procesa un mensaje entrante de WhatsApp y devuelve la respuesta del bot.
     *
     * @return string texto a enviar por WhatsApp
     */
    public function handleMessage(string $whatsappNumber, string $userText): string
    {
        $business = Business::find($this->businessId);
        if (!$business || !$business['bot_enabled']) {
            return 'Este negocio no tiene el bot habilitado en este momento.';
        }

        // Conversación persistente
        $conversation = BotConversation::findOrCreate($this->businessId, $whatsappNumber);
        $messages = json_decode((string) $conversation['messages'], true) ?: [];

        // Limitar historial a últimos 20 mensajes (usuario+asistente)
        if (count($messages) > 40) {
            $messages = array_slice($messages, -40);
        }

        // Agregar mensaje del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $userText,
        ];

        // Loop de tool-use — Claude puede llamar herramientas y nosotros devolvemos resultados
        $maxIterations = 5;
        $iterations = 0;
        $finalText = '';

        while ($iterations < $maxIterations) {
            $iterations++;
            try {
                $response = $this->claude->messages(
                    $messages,
                    $this->buildSystemPrompt($business),
                    $this->buildTools(),
                    1024
                );
            } catch (\Throwable $e) {
                error_log('[BotEngine] ' . $e->getMessage());
                return 'Perdón, tuve un problema técnico. Probá de nuevo en un momento.';
            }

            // Guardar respuesta del assistant
            $assistantContent = $response['content'] ?? [];
            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantContent,
            ];

            // Extraer texto y tool_use
            $toolUses = [];
            foreach ($assistantContent as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $finalText .= $block['text'];
                }
                if (($block['type'] ?? '') === 'tool_use') {
                    $toolUses[] = $block;
                }
            }

            // Si no hay tool_use, terminamos
            if (empty($toolUses)) {
                break;
            }

            // Ejecutar todas las tools y agregar resultados
            $toolResults = [];
            foreach ($toolUses as $tu) {
                $result = $this->executeTool($tu['name'], $tu['input'] ?? [], $whatsappNumber);
                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $tu['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
            $messages[] = [
                'role' => 'user',
                'content' => $toolResults,
            ];

            $finalText = ''; // el texto final será el próximo turno del assistant
        }

        // Persistir estado
        BotConversation::updateState(
            (string) $conversation['id'],
            [],
            $messages
        );

        return trim($finalText) ?: 'Disculpá, no entendí. ¿Podés reformular?';
    }

    private function buildSystemPrompt(array $business): string
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $now = (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d H:i');
        $personality = $business['bot_personality'] ?: 'profesional y amigable';
        $welcome = $business['bot_welcome_message'] ?? '';

        return <<<PROMPT
Sos el asistente de WhatsApp de "{$business['name']}", un negocio argentino.
Ayudás a los clientes a sacar turnos, cancelarlos o reprogramarlos.

Personalidad: {$personality}. Hablás en español rioplatense (usás "vos", "podés", "querés", "dale").
Sos conciso: respuestas cortas, máximo 2-3 oraciones por turno (WhatsApp).

Fecha y hora actual: {$now} ({$tz})

Flujo recomendado para sacar un turno:
1. Preguntá qué servicio necesita (usá list_services para ver las opciones)
2. Preguntá si tiene preferencia de profesional (usá list_professionals)
3. Buscá horarios disponibles con find_available_slots
4. Cuando elija uno, usá create_booking
5. Confirmá con el número de turno

Reglas importantes:
- NUNCA inventes servicios, profesionales, horarios ni precios. Siempre usá las tools.
- Cuando el cliente no da nombre, el número de WhatsApp ya lo identifica (lo manejás automáticamente).
- Si el cliente pide cancelar, usá get_client_bookings para mostrar los próximos y luego cancel_booking.
- Formateá las fechas amigablemente: "martes 5/3 a las 15:30".
- Si el cliente pide algo fuera de tu alcance (consultas médicas, precios especiales, etc.), derivá a que llame al negocio.
- NUNCA menciones que sos una IA ni que usás tools. Actuá como un asistente real del negocio.

{$welcome}
PROMPT;
    }

    /**
     * Definición de herramientas (tool_use) para Claude.
     */
    private function buildTools(): array
    {
        return [
            [
                'name' => 'list_services',
                'description' => 'Lista los servicios disponibles del negocio con su duración y precio.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'list_professionals',
                'description' => 'Lista los profesionales que pueden realizar un servicio dado.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'service_id' => ['type' => 'string', 'description' => 'ID del servicio'],
                    ],
                    'required' => ['service_id'],
                ],
            ],
            [
                'name' => 'find_available_slots',
                'description' => 'Busca los próximos horarios disponibles para un servicio (y profesional opcional). Devuelve hasta 8 opciones.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'service_id' => ['type' => 'string'],
                        'professional_id' => ['type' => 'string', 'description' => 'opcional; si no se pasa, busca entre todos los profesionales'],
                        'from_date' => ['type' => 'string', 'description' => 'opcional YYYY-MM-DD, desde qué fecha buscar'],
                    ],
                    'required' => ['service_id'],
                ],
            ],
            [
                'name' => 'create_booking',
                'description' => 'Crea un turno. Requiere que ya tengas service_id, professional_id, date y start_time exactos obtenidos desde find_available_slots.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'service_id' => ['type' => 'string'],
                        'professional_id' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'start_time' => ['type' => 'string', 'description' => 'HH:MM'],
                        'client_name' => ['type' => 'string', 'description' => 'Nombre del cliente si no estaba registrado'],
                    ],
                    'required' => ['service_id', 'professional_id', 'date', 'start_time'],
                ],
            ],
            [
                'name' => 'get_client_bookings',
                'description' => 'Devuelve los próximos turnos confirmados del cliente (identificado por su número de WhatsApp).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'cancel_booking',
                'description' => 'Cancela un turno dado su ID.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string'],
                    ],
                    'required' => ['booking_id'],
                ],
            ],
        ];
    }

    /**
     * Ejecuta una tool y devuelve el resultado que Claude recibirá.
     */
    private function executeTool(string $name, array $input, string $whatsappNumber): array
    {
        try {
            return match ($name) {
                'list_services' => $this->toolListServices(),
                'list_professionals' => $this->toolListProfessionals((string) ($input['service_id'] ?? '')),
                'find_available_slots' => $this->toolFindSlots($input),
                'create_booking' => $this->toolCreateBooking($input, $whatsappNumber),
                'get_client_bookings' => $this->toolGetClientBookings($whatsappNumber),
                'cancel_booking' => $this->toolCancelBooking((string) ($input['booking_id'] ?? '')),
                default => ['error' => 'Tool desconocida: ' . $name],
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function toolListServices(): array
    {
        $services = ServiceModel::allByBusiness($this->businessId, true);
        return [
            'services' => array_map(fn($s) => [
                'id' => $s['id'],
                'name' => $s['name'],
                'duration_min' => (int) $s['duration'],
                'price' => $s['price'] ? (float) $s['price'] : null,
                'description' => $s['description'],
            ], $services),
        ];
    }

    private function toolListProfessionals(string $serviceId): array
    {
        $pros = Professional::professionalsForService($serviceId);
        return [
            'professionals' => array_map(fn($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'specialization' => $p['specialization'],
            ], $pros),
        ];
    }

    private function toolFindSlots(array $input): array
    {
        $calc = new SlotCalculator($this->businessId);
        $slots = $calc->nextAvailableSlots(
            (string) $input['service_id'],
            isset($input['professional_id']) ? (string) $input['professional_id'] : null,
            8
        );
        return ['slots' => $slots];
    }

    private function toolCreateBooking(array $input, string $whatsappNumber): array
    {
        $client = Client::findOrCreate(
            $this->businessId,
            $whatsappNumber,
            (string) ($input['client_name'] ?? '')
        );
        if (!empty($input['client_name']) && strpos($client['name'], 'Cliente ') === 0) {
            Client::update($client['id'], ['name' => $input['client_name']]);
        }

        $service = new BookingService($this->businessId);
        $result = $service->createBooking([
            'client_id' => $client['id'],
            'service_id' => (string) $input['service_id'],
            'professional_id' => (string) ($input['professional_id'] ?? ''),
            'date' => (string) $input['date'],
            'start_time' => (string) $input['start_time'],
            'source' => 'WHATSAPP_BOT',
            'auto_confirm' => true,
        ]);
        return [
            'success' => true,
            'booking_id' => $result['id'],
            'booking_number' => $result['number'],
        ];
    }

    private function toolGetClientBookings(string $whatsappNumber): array
    {
        $client = Client::findByPhoneOrWhatsapp($this->businessId, $whatsappNumber);
        if (!$client) return ['bookings' => []];
        $bookings = Booking::forBusinessAndDateRange(
            $this->businessId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+60 days'))
        );
        $own = array_values(array_filter($bookings, fn($b) =>
            $b['client_id'] === $client['id'] && in_array($b['status'], ['PENDING', 'CONFIRMED'], true)
        ));
        return [
            'bookings' => array_map(fn($b) => [
                'id' => $b['id'],
                'booking_number' => (int) $b['booking_number'],
                'service' => $b['service_name'],
                'professional' => $b['professional_name'],
                'date' => $b['date'],
                'start_time' => substr($b['start_time'], 0, 5),
                'status' => $b['status'],
            ], $own),
        ];
    }

    private function toolCancelBooking(string $bookingId): array
    {
        $service = new BookingService($this->businessId);
        $service->cancel($bookingId, 'Cancelado por bot');
        return ['success' => true];
    }
}
