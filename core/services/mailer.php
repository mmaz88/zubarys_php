<?php
/**
 * Enhanced Email Service using PHPMailer with PDF support
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Mpdf\Mpdf;

/**
 * Send email using PHPMailer
 */
function send_mail($to, $subject, $body, $options = [])
{
    $mail = new PHPMailer(true);

    try {
        $config = config('mail');

        // Server settings
        if ($config['driver'] === 'smtp') {
            setup_smtp($mail, $config['smtp']);
        }

        // Recipients
        $mail->setFrom(
            $options['from'] ?? $config['from_address'],
            $options['from_name'] ?? $config['from_name']
        );

        // Add recipients
        if (is_array($to)) {
            foreach ($to as $email => $name) {
                if (is_numeric($email)) {
                    $mail->addAddress($name);
                } else {
                    $mail->addAddress($email, $name);
                }
            }
        } else {
            $mail->addAddress($to);
        }

        // CC and BCC
        if (isset($options['cc'])) {
            $cc_list = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
            foreach ($cc_list as $cc_email) {
                $mail->addCC($cc_email);
            }
        }

        if (isset($options['bcc'])) {
            $bcc_list = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
            foreach ($bcc_list as $bcc_email) {
                $mail->addBCC($bcc_email);
            }
        }

        // Reply-To
        if (isset($options['reply_to'])) {
            $mail->addReplyTo($options['reply_to']);
        }

        // Content
        $mail->isHTML($options['html'] ?? true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Plain text version for HTML emails
        if ($options['html'] ?? true) {
            $mail->AltBody = strip_tags($body);
        }

        // Attachments
        if (isset($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (is_string($attachment)) {
                    $mail->addAttachment($attachment);
                } elseif (is_array($attachment)) {
                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? '',
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? ''
                    );
                }
            }
        }

        // PDF attachments
        if (isset($options['pdf'])) {
            foreach ($options['pdf'] as $pdf) {
                $pdf_path = generate_pdf($pdf['template'], $pdf['data'] ?? [], $pdf['options'] ?? []);
                $mail->addAttachment($pdf_path, $pdf['filename'] ?? 'document.pdf');
            }
        }

        // Send email
        $result = $mail->send();

        write_log("Email sent successfully to: " . (is_array($to) ? implode(', ', array_keys($to)) : $to), 'info');

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];

    } catch (PHPMailerException $e) {
        write_log("Email sending failed: " . $e->getMessage(), 'error');

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Setup SMTP configuration for PHPMailer
 */
function setup_smtp($mail, $config)
{
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->Port = $config['port'];

    // Encryption
    switch ($config['encryption']) {
        case 'ssl':
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            break;
        case 'tls':
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            break;
    }

    // Debug mode
    if (env('APP_DEBUG', false)) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function ($str, $level) {
            write_log("SMTP Debug: $str", 'debug');
        };
    }

    // Office 365 specific settings
    if (strpos($config['host'], 'office365') !== false || strpos($config['host'], 'outlook') !== false) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
    }
}

/**
 * Send templated email with PDF attachment
 */
function send_template_mail_with_pdf($to, $template, $data = [], $pdf_template = null, $options = [])
{
    // Render email template
    $email_body = render_email_template($template, $data);

    // Add PDF if template provided
    if ($pdf_template) {
        $options['pdf'] = [
            [
                'template' => $pdf_template,
                'data' => $data,
                'filename' => $options['pdf_filename'] ?? 'document.pdf'
            ]
        ];
    }

    $subject = $options['subject'] ?? extract_subject_from_template($template, $data);

    return send_mail($to, $subject, $email_body, $options);
}

/**
 * Generate PDF from template using mPDF
 */
function generate_pdf($template, $data = [], $options = [])
{
    try {
        // Default mPDF configuration
        $config = [
            'tempDir' => STORAGE_PATH . '/temp',
            'mode' => $options['mode'] ?? 'utf-8',
            'format' => $options['format'] ?? 'A4',
            'orientation' => $options['orientation'] ?? 'P',
            'margin_left' => $options['margin_left'] ?? 15,
            'margin_right' => $options['margin_right'] ?? 15,
            'margin_top' => $options['margin_top'] ?? 16,
            'margin_bottom' => $options['margin_bottom'] ?? 16,
        ];

        // Create temp directory if not exists
        if (!file_exists($config['tempDir'])) {
            mkdir($config['tempDir'], 0755, true);
        }

        $mpdf = new Mpdf($config);

        // Set document info
        $mpdf->SetTitle($options['title'] ?? 'PDF Document');
        $mpdf->SetAuthor($options['author'] ?? env('APP_NAME', 'PHP Mini Framework'));
        $mpdf->SetCreator(env('APP_NAME', 'PHP Mini Framework'));

        // Render PDF template
        $html = render_pdf_template($template, $data);

        // Add CSS if provided
        if (isset($options['css'])) {
            $mpdf->WriteHTML($options['css'], 1);
        }

        $mpdf->WriteHTML($html, 2);

        // Generate filename
        $filename = $options['filename'] ?? uniqid('pdf_') . '.pdf';
        $filepath = STORAGE_PATH . '/temp/' . $filename;

        // Save PDF
        $mpdf->Output($filepath, 'F');

        return $filepath;

    } catch (Exception $e) {
        write_log("PDF generation failed: " . $e->getMessage(), 'error');
        throw new Exception("Failed to generate PDF: " . $e->getMessage());
    }
}

/**
 * Render email template
 */
function render_email_template($template, $data = [])
{
    $template_path = APP_PATH . '/views/emails/' . str_replace('.', '/', $template) . '.php';

    if (!file_exists($template_path)) {
        throw new Exception("Email template not found: {$template}");
    }

    extract($data);

    ob_start();
    require $template_path;
    return ob_get_clean();
}

/**
 * Render PDF template
 */
function render_pdf_template($template, $data = [])
{
    $template_path = APP_PATH . '/views/pdf/' . str_replace('.', '/', $template) . '.php';

    if (!file_exists($template_path)) {
        throw new Exception("PDF template not found: {$template}");
    }

    extract($data);

    ob_start();
    require $template_path;
    return ob_get_clean();
}

/**
 * Extract subject from email template
 */
function extract_subject_from_template($template, $data = [])
{
    $template_path = APP_PATH . '/views/emails/' . str_replace('.', '/', $template) . '.php';

    if (!file_exists($template_path)) {
        return 'No Subject';
    }

    extract($data);

    ob_start();
    require $template_path;
    $content = ob_get_clean();

    // Try to extract subject from PHP variable or HTML title
    if (isset($email_subject)) {
        return $email_subject;
    }

    if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
        return $matches[1];
    }

    return 'No Subject';
}

/**
 * Send invoice email with PDF
 */
function send_invoice_email($to, $invoice_data, $options = [])
{
    return send_template_mail_with_pdf(
        $to,
        'invoice',
        $invoice_data,
        'invoice',
        array_merge($options, [
            'subject' => 'Invoice #' . $invoice_data['invoice_number'],
            'pdf_filename' => 'invoice-' . $invoice_data['invoice_number'] . '.pdf'
        ])
    );
}

/**
 * Send report email with PDF
 */
function send_report_email($to, $report_data, $options = [])
{
    return send_template_mail_with_pdf(
        $to,
        'report',
        $report_data,
        'report',
        array_merge($options, [
            'subject' => $report_data['title'] ?? 'Report',
            'pdf_filename' => 'report-' . date('Y-m-d') . '.pdf'
        ])
    );
}

/**
 * Generate and download PDF
 */
function download_pdf($template, $data = [], $filename = null, $options = [])
{
    $pdf_path = generate_pdf($template, $data, $options);
    $filename = $filename ?: basename($pdf_path);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdf_path));

    readfile($pdf_path);

    // Clean up temp file
    unlink($pdf_path);

    exit;
}

/**
 * Stream PDF to browser
 */
function stream_pdf($template, $data = [], $filename = null, $options = [])
{
    $pdf_path = generate_pdf($template, $data, $options);
    $filename = $filename ?: basename($pdf_path);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdf_path));

    readfile($pdf_path);

    // Clean up temp file
    unlink($pdf_path);

    exit;
}

/**
 * Bulk email with PDF attachments
 */
function send_bulk_email_with_pdf($recipients, $template, $pdf_template, $options = [])
{
    $results = [];

    foreach ($recipients as $recipient) {
        $email = $recipient['email'];
        $data = $recipient['data'] ?? [];

        try {
            $result = send_template_mail_with_pdf($email, $template, $data, $pdf_template, $options);
            $results[$email] = $result;

        } catch (Exception $e) {
            $results[$email] = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        // Small delay to prevent overwhelming SMTP server
        usleep(100000); // 0.1 seconds
    }

    return $results;
}

/**
 * Clean up temp PDF files
 */
function cleanup_temp_pdfs($older_than_hours = 24)
{
    $temp_dir = STORAGE_PATH . '/temp';

    if (!file_exists($temp_dir)) {
        return 0;
    }

    $files = glob($temp_dir . '/*.pdf');
    $cleaned = 0;
    $cutoff_time = time() - ($older_than_hours * 3600);

    foreach ($files as $file) {
        if (filemtime($file) < $cutoff_time) {
            unlink($file);
            $cleaned++;
        }
    }

    return $cleaned;
}