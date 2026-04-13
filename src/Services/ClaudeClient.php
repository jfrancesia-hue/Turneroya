<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Cliente HTTP puro para la API de Claude (Anthropic Messages API).
 * Soporta tool use (function calling) que usamos para que el bot
 * pueda consultar slots, crear/cancelar bookings, etc.
 */
final class ClaudeClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-haiku-4-5-20251001',
    ) {}

    /**
     * Envía una lista de mensajes al modelo y devuelve la respuesta completa.
     *
     * @param array<int, array{role:string, content:mixed}> $messages
     * @param array<int, array<string,mixed>> $tools
     */
    public function messages(
        array $messages,
        string $systemPrompt,
        array $tools = [],
        int $maxTokens = 1024
    ): array {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];
        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $ch = curl_init(self::BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Claude API cURL error: ' . $curlErr);
        }
        $data = json_decode((string) $response, true);
        if ($httpCode >= 400) {
            $msg = $data['error']['message'] ?? (string) $response;
            throw new \RuntimeException("Claude API error (HTTP $httpCode): $msg");
        }
        return is_array($data) ? $data : [];
    }
}
