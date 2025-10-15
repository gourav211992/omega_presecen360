<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\PlantMaintWo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Mailers\Mailer;
use App\Helpers\Helper;
use App\Models\MailBox;
use App\Models\AuthUser;
use App\Models\Organization;

class WorkOrderDueDateReminder extends Command
{
    protected $signature = 'work-order:due-date-reminder';
    protected $description = 'Send email reminders for work orders due within 24 hours';

    public function handle()
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $this->info("Running Work Order Due Date Reminder for tomorrow: {$tomorrow->format('Y-m-d')}");

        $workOrders = PlantMaintWo::withoutGlobalScopes()
            ->whereIn('document_status', ['submitted', 'approved', 'approval_not_required'])
            ->select('id', 'document_number', 'document_status', 'created_by', 'equipment_details', 'final_remark', 'organization_id', 'equipment_id', 'maintenance_type_id')
            ->get();

        $this->info("Found {$workOrders->count()} active work orders to check");

        $remindersSent = 0;

        foreach ($workOrders as $workOrder) {
            $equipmentDetails = json_decode($workOrder->equipment_details, true);
            $dueDate = $equipmentDetails['due_date'] ?? null;

            $this->info("Checking WO {$workOrder->document_number} - Due Date: " . ($dueDate ?? 'Not set') . " - Created By: " . ($workOrder->created_by ?? 'Not set'));

            if ($dueDate) {
                $dueDateCarbon = Carbon::parse($dueDate);

                if ($dueDateCarbon->isSameDay($tomorrow)) {
                    $this->info("WO {$workOrder->document_number} is due tomorrow, sending reminder...");
                    $this->sendReminderEmail($workOrder, $dueDateCarbon);
                    $remindersSent++;
                } else {
                    $this->info("WO {$workOrder->document_number} due date {$dueDateCarbon->format('Y-m-d')} is not tomorrow");
                }
            }
        }

        $this->info("Work Order Due Date Reminder completed. Sent {$remindersSent} reminder emails.");
        Log::info("Work Order Due Date Reminder: Processed {$workOrders->count()} work orders, sent {$remindersSent} reminders");
    }

    private function sendReminderEmail($workOrder, Carbon $dueDate)
    {
        $equipmentDetails = json_decode($workOrder->equipment_details, true);
        $orgName = Organization::find($workOrder->organization_id)?->name ?? 'Organization';
        $dateo = date('d-m-Y');
        $subject = "Work Order Due Date Reminder - {$workOrder->document_number} | {$dateo}";
        $emailRecipients = $this->getEmailRecipients($workOrder);

        if (!empty($emailRecipients['to'])) {
            try {
                $mailBox = new MailBox();
                $mailBox->mail_to = implode(',', $emailRecipients['to']);
                $mailBox->mail_cc = !empty($emailRecipients['cc']) ? implode(',', $emailRecipients['cc']) : null;
                $mailBox->layout = 'emails.work_order_reminder';
                $mailBox->subject = $subject;

                $equipmentName = $equipmentDetails['equipment_name'] ?? 'N/A';
                $maintenanceType = $equipmentDetails['maintenance_type_name'] ?? $equipmentDetails['equipment_maintenance_type_name'] ?? 'N/A';
                $location = $equipmentDetails['location'] ?? null;

                $mailBox->mail_body = json_encode([
                    'document_number' => $workOrder->document_number,
                    'equipment_name' => $equipmentName,
                    'due_date' => $dueDate->format('d-m-Y'),
                    'maintenance_type' => $maintenanceType,
                    'location' => $location,
                    'priority' => $equipmentDetails['priority'] ?? null,
                    'remarks' => $workOrder->final_remark ?? null,
                    'assigned_to' => 'Maintenance Team',
                    'orgName' => $orgName,
                ]);

                $mailer = new Mailer();
                $mailer->emailTo($mailBox);

                Log::info("Work order reminder email sent for {$workOrder->document_number}");
                $this->info("Reminder sent for WO: {$workOrder->document_number}");

                $toEmails = implode(', ', $emailRecipients['to']);
                $ccEmails = !empty($emailRecipients['cc']) ? implode(', ', $emailRecipients['cc']) : 'None';

                $this->info("Reminder email queued for WO {$workOrder->document_number}");
                $this->info("CC: {$ccEmails}");

                Log::info("Work Order reminder email queued", [
                    'work_order_id' => $workOrder->id,
                    'document_number' => $workOrder->document_number,
                    'mail_to' => $toEmails,
                    'mail_cc' => $ccEmails,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'mailbox_id' => $mailBox->id
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to send email for WO {$workOrder->document_number}: {$e->getMessage()}");
                Log::error("Failed to send work order reminder email", [
                    'work_order_id' => $workOrder->id,
                    'document_number' => $workOrder->document_number,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $this->warn("No valid email recipients found for WO {$workOrder->document_number}");
            Log::warning("No email recipients for work order reminder", [
                'work_order_id' => $workOrder->id,
                'document_number' => $workOrder->document_number
            ]);
        }
    }

    private function getEmailRecipients($workOrder)
    {
        $recipients = [
            'to' => [],
            'cc' => []
        ];

        
        if ($workOrder->created_by) {
            try {
                $creator = AuthUser::find($workOrder->created_by);
                if ($creator && $creator->email && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                    $recipients['to'][] = $creator->email;
                    $recipients['cc'][] = $creator->email;
                    Log::info("Added creator email to CC: {$creator->email} for WO {$workOrder->document_number}");
                    $creatorName = $creator->name ?? 'Unknown';
                    $this->info("Creator found: {$creatorName} ({$creator->email})");
                } else {
                    Log::info("Creator found but no valid email for WO {$workOrder->document_number}, creator ID: {$workOrder->created_by}");
                }
            } catch (\Exception $e) {
                Log::warning("Could not fetch creator email for WO {$workOrder->document_number}: " . $e->getMessage());
            }
        } else {
            Log::info("No created_by field for WO {$workOrder->document_number}");
        }

        $recipients['to'] = array_unique(array_filter($recipients['to']));
        $recipients['cc'] = array_unique(array_filter($recipients['cc']));

        return $recipients;
    }
}
