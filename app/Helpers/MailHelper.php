<?php

namespace App\Helpers;

use App\Jobs\SendEmailJob;
use App\Exceptions\ApiGenericException;

class MailHelper
{
    public static function sendDocumentStatusEmail(array $params): void
    {

        $callback = function () use ($params) {
            try {
                $receiver = $params['receiver'] ?? null;
                if (!empty($receiver)) {
                    $url           = $params['link'] ?? "";
                    $remarks       = $params['remarks'] ?? "";
                    $content       = $params['content'] ?? "";
                    $documentType  = ucfirst($params['document'] ?? 'Document');
                    $status        = strtolower($params['status'] ?? 'updated');
                    $title         = $params['title'] ?? "{$documentType} - " . ucfirst($status);
                    $cc            = !empty($params['cc']) ? array_values((array)$params['cc']) : null;
                    $bcc           = !empty($params['bcc']) ? array_values((array)$params['bcc']) : null;
                    $attachments   = !empty($params['attachments']) ? (array)$params['attachments'] : null;
                    $sender        = $params['sender'] ?? request()->user()?->email ?? config('mail.from.address');
                    $senderName    = $params['sender_name'] ?? request()->user()?->name ?? config('mail.from.name', 'P360');
                    $description   = $params['description'] ?? SELF::buildDefaultEmailTemplate($documentType, $receiver->name, $content, $remarks, $url);

                    dispatch(new SendEmailJob($receiver, $sender, $senderName, $title, $description, $cc, $bcc, null));
                }
            } catch (\Throwable $e) {
                throw new ApiGenericException($e->getMessage());
            }
        };

        if (\DB::transactionLevel() > 0) {
            \DB::afterCommit($callback);
        } else {
            $callback();
        }
    }

    /**
     * Build a clean default HTML template (can be extended).
     */
    private static function buildDefaultEmailTemplate(string $documentType, ?string $receiverName, string $content = "", string $remarks = "", string $url = ""): string
    {
        $nameText = $receiverName ? "Dear {$receiverName}," : "Hello,";
        $buttonHtml = $url
            ? <<<HTML
                <p style="text-align: center; margin: 20px 0;">
                    <a href="{$url}" target="_blank" style="background-color: #7415ae; color: #ffffff; padding: 12px 24px; border-radius: 5px; font-size: 16px; text-decoration: none; font-weight: bold;">
                        View {$documentType}
                    </a>
                </p>
              HTML
            : '';

        return <<<HTML
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-family: Arial, sans-serif;">
            <tr>
                <td>
                    <p style="font-size: 16px; color: #555;">{$nameText}</p>
                    <p style="font-size: 15px; color: #333;">{$content}</p>
                    <p style="font-size: 15px; color: #333;">{$remarks}</p>

                    {$buttonHtml}
                </td>
            </tr>
        </table>
        HTML;
    }
}
