<?php

declare(strict_types=1);

/**
 * SMS Service for sending text messages.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Sends an SMS message using the configured provider.
 *
 * @param string $to The recipient's phone number.
 * @param string $message The message text.
 * @param array<string, mixed> $options Additional provider-specific options.
 * @return array<string, mixed> The result of the send operation.
 * @throws Exception if the service is disabled or misconfigured.
 */
function send_sms(string $to, string $message, array $options = []): array
{
    $config = config('services.sms');
    if (!($config['enabled'] ?? false)) {
        throw new Exception("SMS service is disabled.");
    }

    $provider = $config['provider'] ?? 'twilio';

    return match ($provider) {
        'twilio' => send_sms_twilio($to, $message, $options),
        // Add other providers like nexmo, aws here
        default => throw new Exception("Unsupported SMS provider: {$provider}"),
    };
}

/**
 * Sends an SMS via Twilio.
 *
 * @param string $to Recipient's phone number.
 * @param string $message Message text.
 * @param array<string, mixed> $options Additional options.
 * @return array<string, mixed>
 * @throws Exception On failure.
 */
function send_sms_twilio(string $to, string $message, array $options = []): array
{
    $config = config('services.sms.twilio');
    if (empty($config['account_sid']) || empty($config['auth_token']) || empty($config['from'])) {
        throw new Exception("Twilio SMS configuration is incomplete.");
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json";

    $payload = [
        'From' => $config['from'],
        'To' => format_phone_number($to),
        'Body' => $message,
    ];

    try {
        $client = new Client();
        $response = $client->post($url, [
            'auth' => [$config['account_sid'], $config['auth_token']],
            'form_params' => $payload
        ]);

        $body = json_decode((string) $response->getBody(), true);
        write_log("SMS sent via Twilio to: {$to}", 'info');

        return [
            'success' => true,
            'message_id' => $body['sid'] ?? null,
            'status' => $body['status'] ?? 'sent'
        ];
    } catch (GuzzleException $e) {
        write_log("Twilio SMS failed: " . $e->getMessage(), 'error');
        throw new Exception("Failed to send SMS via Twilio.", 0, $e);
    }
}