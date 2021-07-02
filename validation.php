<?php
error_reporting(-1);
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = $_POST;
    $res['isOK'] = FALSE;
    $message = null;

    $good = true;
    $clean_name = filter_var($data['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
    if (!$clean_name) :
        $message .= "First Name required!\n";
        $good = FALSE;
    endif;
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) :
        $message .= "Email address required!\n";
        $good = FALSE;
    endif;
    $clean_referredby = filter_var($data['referredby'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
    $clean_subject = filter_var($data['subject'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
    $clean_message = filter_var($data['message'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH);
    /*if (strlen($message) > 5):
        $message = substr($message, 0, -5);
    endif;*/
    $res['msg'] = $message;
    if ($good) :
        $res['msg'] = serialize($good);
        if ($good) {
            define('SENDER', 'noreply@row.net');

            // Replace recipient@example.com with a "To" address. If your account
            // is still in the sandbox, this address must be verified.
            //define('RECIPIENT', ['sales@ifeedtech.com, noreply@row.net, sales@ifeednaturally.com']);

            // Replace smtp_username with your Amazon SES SMTP user name.
            define('USERNAME', 'AKIA2MWLYONTZ3H7FKX4');

            // Replace smtp_password with your Amazon SES SMTP password.
            define('PASSWORD', 'BEM5Zxn9q4SUZe4qG2G4JAaGVZA+LCelHWrlUTk7+FPF');

            // If you're using Amazon SES in a region other than US West (Oregon),
            // replace email-smtp.us-west-2.amazonaws.com with the Amazon SES SMTP
            // endpoint in the appropriate region.
            define('HOST', 'email-smtp.us-east-1.amazonaws.com');

            // The port you will connect to on the Amazon SES SMTP endpoint.
            define('PORT', '587');

            // Other message information
            define('SUBJECT', 'New message for Economy Signs');

            require __DIR__ . '/vendor/autoload.php';

            $transport = (new Swift_SmtpTransport(HOST, PORT, 'tls'))
                ->setUsername(USERNAME)
                ->setPassword(PASSWORD);
            // Create the Mailer using your created Transport
            $mailer = new Swift_Mailer($transport);
            // Create a message
            $message = (new Swift_Message(SUBJECT))
                ->setFrom(array('noreply@row.net' => 'New message for Economy Signs'))
                ->setTo(array(
                    'economysigns.sa@gmail.com'
                ))
                ->setBody("<html><head></head><body>You've received a new message for Economy Signs.<br/><br/>Name : {$clean_name} <br/>Email : {$data['email']}<br/>Subject: {$clean_subject}<br/>Referred by: {$clean_referredby}<br/>Message: {$clean_message}</body></html>", 'text/html');

            $result = $mailer->send($message);
            $res['msg'] = $result;
            $res['isOK'] = $good;
        }
    endif;
    echo json_encode($res);
    exit(1);
}
