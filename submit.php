<?php
/**
 * DDIG Ghana - Form Submission Handler
 * Single file that handles ALL form submissions
 */

// ================= CONFIGURATION =================
define('ADMIN_EMAIL', 'info@ddig-group.com');
define('SITE_NAME', 'DDIG Ghana');
define('SITE_URL', 'https://yourdomain.com'); // Change this

// ================= SECURITY =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ================= HELPER FUNCTIONS =================
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sendEmail($to, $subject, $body, $from = null) {
    if ($from === null) {
        $from = ADMIN_EMAIL;
    }
    
    $headers = "From: " . SITE_NAME . " <" . $from . ">\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

function jsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// ================= MAIN HANDLER =================
try {
    // Get action from POST or GET
    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : 
              (isset($_GET['action']) ? sanitizeInput($_GET['action']) : '');
    
    // Handle different actions
    switch ($action) {
        case 'register_event':
            handleEventRegistration();
            break;
        case 'subscribe_newsletter':
            handleNewsletterSubscription();
            break;
        case 'contact_form':
            handleContactForm();
            break;
        case 'support_donation':
            handleSupportNotification();
            break;
        case 'test':
            // Test endpoint
            jsonResponse(true, 'Backend is working!', [
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => phpversion()
            ]);
            break;
        default:
            jsonResponse(false, 'Please specify an action. Available: register_event, subscribe_newsletter, contact_form, support_donation');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}

// ================= EVENT REGISTRATION =================
function handleEventRegistration() {
    // Check required fields
    $required = ['fullName', 'email', 'eventName'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse(false, "Missing required field: $field");
        }
    }
    
    // Sanitize data
    $full_name = sanitizeInput($_POST['fullName']);
    $email = sanitizeInput($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $location = isset($_POST['location']) ? sanitizeInput($_POST['location']) : '';
    $profession = isset($_POST['profession']) ? sanitizeInput($_POST['profession']) : '';
    $diaspora_status = isset($_POST['diasporaStatus']) ? sanitizeInput($_POST['diasporaStatus']) : '';
    $attendee_type = isset($_POST['attendeeType']) ? sanitizeInput($_POST['attendeeType']) : '';
    $event_name = sanitizeInput($_POST['eventName']);
    $additional_info = isset($_POST['additionalInfo']) ? sanitizeInput($_POST['additionalInfo']) : '';
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $terms = isset($_POST['terms']) ? 1 : 0;
    
    if (!$terms) {
        jsonResponse(false, 'You must agree to the terms and conditions');
    }
    
    // Generate unique registration ID
    $registration_id = 'DDIG-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // ======= SEND EMAIL TO USER =======
    $user_subject = "DDIG Event Registration Confirmation: " . $event_name;
    $user_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #006b3f; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { background: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Registration Confirmed!</h1>
                </div>
                <div class='content'>
                    <h2>Thank you, $full_name!</h2>
                    <p>Your registration for <strong>$event_name</strong> has been received successfully.</p>
                    
                    <div class='details'>
                        <h3>📋 Registration Details:</h3>
                        <p><strong>Registration ID:</strong> $registration_id</p>
                        <p><strong>Event:</strong> $event_name</p>
                        <p><strong>Attendee Type:</strong> $attendee_type</p>
                        <p><strong>Date Registered:</strong> " . date('F j, Y') . "</p>
                    </div>
                    
                    <p>📅 You will receive event details and access instructions 24 hours before the event starts.</p>
                    <p>📞 Need help? Contact us at info@ddig-group.com or call +233 24 354 5222</p>
                </div>
                <div class='footer'>
                    <p>Diaspora Development Initiative Ghana</p>
                    <p>Connecting Talent • Driving Development</p>
                    <p><small>This is an automated confirmation. Please do not reply to this email.</small></p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $user_email_sent = sendEmail($email, $user_subject, $user_body);
    
    // ======= SEND EMAIL TO ADMIN =======
    $admin_subject = "📋 New Event Registration: " . $event_name;
    $admin_body = "
        <!DOCTYPE html>
        <html>
        <body>
            <h2>New Event Registration Received</h2>
            <p><strong>Event:</strong> $event_name</p>
            <p><strong>Registration ID:</strong> $registration_id</p>
            <p><strong>Name:</strong> $full_name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Location:</strong> $location</p>
            <p><strong>Profession:</strong> $profession</p>
            <p><strong>Diaspora Status:</strong> $diaspora_status</p>
            <p><strong>Attendee Type:</strong> $attendee_type</p>
            <p><strong>Additional Info:</strong> $additional_info</p>
            <p><strong>Newsletter:</strong> " . ($newsletter ? 'Yes' : 'No') . "</p>
            <p><strong>Registration Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <hr>
            <p><em>This is an automated notification from the DDIG website.</em></p>
        </body>
        </html>
    ";
    
    $admin_email_sent = sendEmail(ADMIN_EMAIL, $admin_subject, $admin_body);
    
    if ($user_email_sent || $admin_email_sent) {
        jsonResponse(true, 'Registration successful! Check your email for confirmation.', [
            'registration_id' => $registration_id,
            'email_sent' => $user_email_sent
        ]);
    } else {
        jsonResponse(false, 'Registration received but email notification failed. Please contact us directly.');
    }
}

// ================= NEWSLETTER SUBSCRIPTION =================
function handleNewsletterSubscription() {
    if (empty($_POST['email'])) {
        jsonResponse(false, 'Email address is required');
    }
    
    $email = sanitizeInput($_POST['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email address');
    }
    
    // Save to simple text file (no database needed)
    $subscriber_data = [
        'email' => $email,
        'subscribe_date' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    
    // Append to file
    $file = 'newsletter_subscribers.txt';
    $current = file_exists($file) ? file_get_contents($file) : '';
    $current .= json_encode($subscriber_data) . "\n";
    file_put_contents($file, $current, LOCK_EX);
    
    // Send welcome email
    $subject = "Welcome to DDIG Ghana Newsletter!";
    $body = "
        <!DOCTYPE html>
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #006b3f;'>Welcome to DDIG Ghana!</h2>
            <p>Thank you for subscribing to our newsletter. You'll now receive:</p>
            <ul>
                <li>📰 Latest diaspora news and updates</li>
                <li>📅 Upcoming event announcements</li>
                <li>💼 Development project opportunities</li>
                <li>🌟 Success stories from our community</li>
            </ul>
            <p>We're excited to have you as part of our diaspora community!</p>
            <p>Best regards,<br><strong>DDIG Ghana Team</strong></p>
            <hr>
            <p style='font-size: 12px; color: #666;'>
                To unsubscribe, reply to this email with 'UNSUBSCRIBE' in the subject.
            </p>
        </body>
        </html>
    ";
    
    $email_sent = sendEmail($email, $subject, $body);
    
    // Notify admin
    $admin_subject = "New Newsletter Subscriber";
    $admin_body = "New newsletter subscriber: $email";
    sendEmail(ADMIN_EMAIL, $admin_subject, $admin_body);
    
    jsonResponse(true, 'Successfully subscribed to newsletter! Check your email for confirmation.');
}

// ================= CONTACT FORM =================
function handleContactForm() {
    $required = ['name', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse(false, "Missing required field: $field");
        }
    }
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : 'New Contact Inquiry';
    $message = sanitizeInput($_POST['message']);
    
    // Generate inquiry ID
    $inquiry_id = 'INQ-' . date('Ymd') . '-' . rand(100, 999);
    
    // Save to file
    $contact_data = [
        'id' => $inquiry_id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'date' => date('Y-m-d H:i:s')
    ];
    
    $file = 'contact_inquiries.txt';
    $current = file_exists($file) ? file_get_contents($file) : '';
    $current .= json_encode($contact_data) . "\n";
    file_put_contents($file, $current, LOCK_EX);
    
    // Send confirmation to user
    $user_subject = "Thank you for contacting DDIG Ghana";
    $user_body = "
        <html>
        <body>
            <h2>Thank you for your message!</h2>
            <p>Dear $name,</p>
            <p>We have received your inquiry and will respond within 24-48 hours.</p>
            <p><strong>Inquiry Reference:</strong> $inquiry_id</p>
            <p><strong>Subject:</strong> $subject</p>
            <p>Best regards,<br>DDIG Ghana Team</p>
        </body>
        </html>
    ";
    
    $user_email_sent = sendEmail($email, $user_subject, $user_body);
    
    // Send notification to admin
    $admin_subject = "New Contact Inquiry: $subject";
    $admin_body = "
        <html>
        <body>
            <h2>New Contact Inquiry</h2>
            <p><strong>Reference:</strong> $inquiry_id</p>
            <p><strong>From:</strong> $name ($email)</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
            <p><strong>Received:</strong> " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>
    ";
    
    $admin_email_sent = sendEmail(ADMIN_EMAIL, $admin_subject, $admin_body);
    
    jsonResponse(true, 'Message sent successfully! We\'ll respond soon. Reference: ' . $inquiry_id);
}

// ================= SUPPORT NOTIFICATION =================
function handleSupportNotification() {
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : 'Anonymous Supporter';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $method = isset($_POST['method']) ? sanitizeInput($_POST['method']) : 'Unknown';
    $amount = isset($_POST['amount']) ? sanitizeInput($_POST['amount']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Save to file
    $support_data = [
        'name' => $name,
        'email' => $email,
        'method' => $method,
        'amount' => $amount,
        'message' => $message,
        'date' => date('Y-m-d H:i:s')
    ];
    
    $file = 'support_pledges.txt';
    $current = file_exists($file) ? file_get_contents($file) : '';
    $current .= json_encode($support_data) . "\n";
    file_put_contents($file, $current, LOCK_EX);
    
    // Send to admin
    $subject = "💚 New Support Pledge: " . $method;
    $body = "
        <html>
        <body>
            <h2 style='color: #006b3f;'>New Support Pledge Received</h2>
            <p><strong>Supporter:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Method:</strong> $method</p>
            <p><strong>Amount/Reference:</strong> $amount</p>
            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
            <p><strong>Date:</strong> " . date('F j, Y, g:i a') . "</p>
            <hr>
            <p><em>Note: This is a notification of pledge. Follow up for actual payment.</em></p>
        </body>
        </html>
    ";
    
    $admin_sent = sendEmail(ADMIN_EMAIL, $subject, $body);
    
    // Send thank you to supporter if email provided
    if (!empty($email)) {
        $thank_you_subject = "Thank you for supporting DDIG Ghana!";
        $thank_you_body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color: #006b3f;'>Thank you for your generosity!</h2>
                <p>Dear $name,</p>
                <p>We have received your support pledge via <strong>$method</strong>.</p>
                <p>Your contribution helps empower diaspora-led development in Ghana.</p>
                <p>We will contact you if additional information is needed.</p>
                <p>With gratitude,<br><strong>DDIG Ghana Team</strong></p>
            </body>
            </html>
        ";
        
        sendEmail($email, $thank_you_subject, $thank_you_body);
    }
    
    jsonResponse(true, 'Thank you for your support! We\'ve received your pledge.');
}

// Fallback if no action specified
jsonResponse(false, 'No action specified. Use ?action=test to check if working');
?>