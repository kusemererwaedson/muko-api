<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Student;
use App\Models\FeeAllocation;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class CommunicationController extends Controller
{
    public function emailLogs(): JsonResponse
    {
        $emailLogs = EmailLog::orderBy('created_at', 'desc')->limit(100)->get();
        return response()->json($emailLogs);
    }

    public function bulkReminders(Request $request): JsonResponse
    {
        $request->validate([
            'class' => 'nullable|string',
            'section' => 'nullable|string',
            'message_type' => 'required|in:due_reminder,overdue_notice',
            'custom_message' => 'nullable|string'
        ]);

        $query = Student::with(['feeAllocations.feeGroup.feeType'])
            ->where('active', true);

        if ($request->class) {
            $query->where('class', $request->class);
        }

        if ($request->section) {
            $query->where('section', $request->section);
        }

        $students = $query->get();
        $emailsSent = 0;
        $emailsFailed = 0;

        foreach ($students as $student) {
            if (!$student->guardian_email) continue;

            $balance = $this->calculateStudentBalance($student);
            
            if ($balance <= 0) continue;

            $subject = $request->message_type === 'due_reminder' 
                ? "Fee Due Reminder - {$student->first_name} {$student->last_name}"
                : "Overdue Fee Notice - {$student->first_name} {$student->last_name}";
                
            $message = $this->generateMessage($student, $balance, $request->message_type, $request->custom_message);

            try {
                Mail::send([], [], function ($mail) use ($student, $subject, $message) {
                    $mail->to($student->guardian_email, $student->guardian_name)
                         ->subject($subject)
                         ->html($message);
                });

                EmailLog::create([
                    'recipient_email' => $student->guardian_email,
                    'recipient_name' => $student->guardian_name,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'email_type' => $request->message_type
                ]);

                $emailsSent++;
            } catch (\Exception $e) {
                EmailLog::create([
                    'recipient_email' => $student->guardian_email,
                    'recipient_name' => $student->guardian_name,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'email_type' => $request->message_type
                ]);

                $emailsFailed++;
            }
        }

        return response()->json([
            'message' => "Bulk email completed. Sent: {$emailsSent}, Failed: {$emailsFailed}",
            'sent_count' => $emailsSent,
            'failed_count' => $emailsFailed
        ]);
    }

    public function smsLogs(): JsonResponse
    {
        $smsLogs = SmsLog::orderBy('created_at', 'desc')->limit(100)->get();
        return response()->json($smsLogs);
    }

    public function sendSms(Request $request): JsonResponse
    {
        $request->validate([
            'recipients' => 'required|in:all,class,level,defaulters',
            'message' => 'required|string|max:160',
            'class_id' => 'nullable|exists:classes,id',
            'level_id' => 'nullable|exists:levels,id',
            'schedule_date' => 'nullable|date|after:now',
        ]);

        $query = Student::where('active', true);

        switch ($request->recipients) {
            case 'class':
                $query->where('class_id', $request->class_id);
                break;
            case 'level':
                $query->whereHas('class', function($q) use ($request) {
                    $q->where('level_id', $request->level_id);
                });
                break;
            case 'defaulters':
                $query->whereHas('feeAllocations', function($q) {
                    $q->where('status', '!=', 'paid');
                });
                break;
        }

        $students = $query->get();
        $recipientCount = $students->count();
        $deliveredCount = 0;

        foreach ($students as $student) {
            if (!$student->guardian_phone) continue;

            try {
                $this->sendSmsMessage($student->guardian_phone, $request->message);
                $deliveredCount++;
            } catch (\Exception $e) {
                // Log failed SMS
            }
        }

        $smsLog = SmsLog::create([
            'message' => $request->message,
            'recipient_count' => $recipientCount,
            'delivered_count' => $deliveredCount,
            'status' => $request->schedule_date ? 'scheduled' : 'sent',
            'scheduled_at' => $request->schedule_date,
        ]);

        return response()->json([
            'message' => 'SMS sent successfully',
            'recipients' => $recipientCount,
            'delivered' => $deliveredCount,
            'sms_log' => $smsLog
        ]);
    }

    private function sendSmsMessage($phone, $message)
    {
        // Integrate with SMS provider here
        return true;
    }

    private function calculateStudentBalance(Student $student)
    {
        $totalAllocated = $student->feeAllocations->sum('amount');
        $totalPaid = $student->feePayments->sum('amount');
        
        return $totalAllocated - $totalPaid;
    }

    private function generateMessage(Student $student, $balance, $messageType, $customMessage = null)
    {
        $urgencyColor = $messageType === 'overdue_notice' ? '#dc3545' : '#ffc107';
        $urgencyText = $messageType === 'overdue_notice' ? 'OVERDUE NOTICE' : 'PAYMENT REMINDER';
        
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4B49AC; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; font-weight: bold; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; }
        .student-info { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .amount { font-size: 24px; font-weight: bold; color: {$urgencyColor}; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏫 MUKO HIGH SCHOOL</h1>
            <p>Excellence in Education</p>
        </div>
        
        <div class='content'>
            <h2>Dear {$student->guardian_name},</h2>
            
            <div class='alert " . ($messageType === 'overdue_notice' ? 'alert-danger' : 'alert-warning') . "'>
                📧 {$urgencyText}
            </div>
            
            <p>We hope this message finds you well. This is a " . ($messageType === 'overdue_notice' ? 'formal notice' : 'friendly reminder') . " regarding your child's school fees.</p>
            
            <div class='student-info'>
                <h3>👨🎓 Student Information</h3>
                <p><strong>Name:</strong> {$student->first_name} {$student->last_name}</p>
                <p><strong>Class:</strong> {$student->class}</p>
                <p><strong>Register No:</strong> {$student->register_no}</p>
                
                <hr>
                
                <h3>💰 Fee Details</h3>
                <p><strong>Outstanding Balance:</strong></p>
                <p class='amount'>UGX " . number_format($balance) . "</p>
            </div>
            " . ($customMessage ? "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'><strong>Additional Message:</strong><br>{$customMessage}</div>" : "") . "
            
            <p>" . ($messageType === 'overdue_notice' ? 
                "⚠️ <strong>URGENT:</strong> Please settle this amount immediately to avoid any inconvenience or further action." : 
                "We kindly request you to make the payment at your earliest convenience.") . "</p>
            
            <div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h4>💳 Payment Methods:</h4>
                <p>• Visit the school bursar's office<br>
                • Mobile Money: Contact school for details<br>
                • Bank Transfer: Contact school for details</p>
            </div>
            
            <p>For any inquiries or payment arrangements, please contact the school administration office.</p>
            
            <div class='footer'>
                <p><strong>Thank you for your cooperation!</strong></p>
                <p>📞 Contact: School Office | 📧 Email: admin@mukoschool.com</p>
                <p><em>Muko High School Administration</em></p>
            </div>
        </div>
    </div>
</body>
</html>";
    }
}