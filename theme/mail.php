<?php
// Include PHPMailer classes for email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include Composer's autoload file
require '../vendor/autoload.php';  // Adjust the path to your vendor/autoload.php if necessary

// Start the session to manage CSRF tokens
session_start();

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

// Check if the form was submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate form inputs
    $service = !empty($_POST['service']) ? htmlspecialchars(trim($_POST['service'])) : null;
    $subject = !empty($_POST['subject']) ? htmlspecialchars(trim($_POST['subject'])) : null;
    $name = !empty($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : null;
    $email = !empty($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
    $message = !empty($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : null;

    // Basic validation for required fields
    if (empty($service) || empty($subject) || empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate email address format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format. Please enter a valid email address.']);
        exit;
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();                                            // Use SMTP for sending
        $mail->Host       = 'live.smtp.mailtrap.io';                // Mailtrap SMTP server
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'api';                                  // Mailtrap API username
        $mail->Password   = 'a1491c090c13999a47841f8331df64b2';     // Mailtrap API key
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable STARTTLS encryption
        $mail->Port       = 587;                                    // TCP port for connection

        // Sender and recipient settings
        $mail->setFrom('no-reply@mailtrap.io', 'Ongoti Tech');       // Sender email (use Mailtrap's domain for testing)
        $mail->addReplyTo($email, $name);                           // Reply-to the user's email
        $mail->addAddress('info@ongotitech.co.za', 'Ongoti Tech');   // Recipient email (your business email)

        // Email content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = htmlspecialchars($subject);                // Email subject
        $mail->Body    = "<strong>Service:</strong> " . htmlspecialchars($service) . "<br>
                          <strong>Name:</strong> " . htmlspecialchars($name) . "<br>
                          <strong>Email:</strong> " . htmlspecialchars($email) . "<br><br>
                          <strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)); // HTML body
        $mail->AltBody = "Service: $service\nName: $name\nEmail: $email\n\nMessage:\n$message"; // Plain text alternative

        // Attempt to send the email
        $mail->send();
        
        // Send success response
        echo json_encode(['status' => 'success', 'message' => 'Your message was sent successfully.']);
        
    } catch (Exception $e) {
        // Log the error message for debugging
        error_log("Mailer Error: {$mail->ErrorInfo}", 3, '/var/log/phpmailer_error.log');

        // Send a user-friendly error message
        echo json_encode(['status' => 'error', 'message' => 'There was an issue sending your message. Please try again later.']);
    }
}

// Regenerate a new CSRF token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
