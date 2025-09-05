<?php

/**
 * core/services/whatsapp.php - WhatsApp Business API Service.
 *
 * Provides a unified function to send WhatsApp messages through various providers.
 */

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Sends a WhatsApp message using the configured provider.
 *
 * @param string $to The recipient's phone number.
 * @param string $message The message text.
 * @param array<string, mixed> $options Additional options (e.g., for templates or media).
 * @return array<string, mixed> The result of the send operation.
 * @throws Exception if the service is disabled or misconfigured.
 */
function send_whatsapp(string $to, string $message, array $options = []): array
{
    $config = config('services.whatsapp');

    if (!($config['enabled'] ?? false)) {
        throw new Exception("WhatsApp service is disabled.");
    }

    $provider = $config['provider'] ?? 'twilio';

    return match ($provider) {
        'twilio' => send_whatsapp_twilio($to, $message, $options),
        'meta' => send_whatsapp_meta($to, $message, $options),
        'custom' => send_whatsapp_custom($to, $message, $options),
        default => throw new Exception("Unsupported WhatsApp provider: {$provider}"),
    };
}

/**
 * Sends a WhatsApp message via Twilio.
 *
 * @param string $to Recipient's phone number.
 * @param string $message Message text.
 * @param array<string, mixed> $options Additional options like 'media_url'.
 * @return array<string, mixed>
 * @throws Exception On failure.
 */
function send_whatsapp_twilio(string $to, string $message, array $options = []): array
{
    $config = config('services.whatsapp.twilio');

    if (empty($config['account_sid']) || empty($config['auth_token']) || empty($config['from'])) {
        throw new Exception("Twilio WhatsApp configuration is incomplete.");
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json";
    $payload = [
        'From' => "whatsapp:{$config['from']}",
        'To' => "whatsapp:" . format_phone_number($to),
        'Body' => $message,
    ];

    if (!empty($options['media_url'])) {
        $payload['MediaUrl'] = $options['media_url'];
    }

    try {
        $client = new Client();
        $response = $client->post($url, [
            'auth' => [$config['account_sid'], $config['auth_token']],
            'form_params' => $payload
        ]);

        $body = json_decode((string) $response->getBody(), true);

        write_log("WhatsApp message sent via Twilio to: {$to}", 'info');

        return [
            'success' => true,
            'message_id' => $body['sid'] ?? null,
            'status' => $body['status'] ?? 'sent'
        ];
    } catch (GuzzleException $e) {
        write_log("Twilio WhatsApp failed: " . $e->getMessage(), 'error');
        throw new Exception("Failed to send WhatsApp message via Twilio.", 0, $e);
    }
}

/**
 * Sends a WhatsApp message via Meta (Facebook).
 * This is a stub function for future implementation.
 *
 * @param string $to Recipient's phone number.
 * @param string $message Message text.
 * @param array<string, mixed> $options Additional options.
 * @return array<string, mixed>
 * @throws Exception Always throws as it's not implemented.
 */
function send_whatsapp_meta(string $to, string $message, array $options = []): array
{
    // This provider is not yet implemented.
    // A developer would add the logic to interact with the Meta Graph API here.
    write_log('Attempted to use unimplemented WhatsApp provider: meta', 'warning');
    throw new Exception('WhatsApp provider "meta" is not yet implemented.');
}

/**
 * Sends a WhatsApp message via a custom webhook or API.
 * This is a stub function for future implementation.
 *
 * @param string $to Recipient's phone number.
 * @param string $message Message text.
 * @param array<string, mixed> $options Additional options.
 * @return array<string, mixed>
 * @throws Exception Always throws as it's not implemented.
 */
function send_whatsapp_custom(string $to, string $message, array $options = []): array
{
    // This provider is not yet implemented.
    // A developer would add logic to call their custom webhook URL here.
    write_log('Attempted to use unimplemented WhatsApp provider: custom', 'warning');
    throw new Exception('WhatsApp provider "custom" is not yet implemented.');
}