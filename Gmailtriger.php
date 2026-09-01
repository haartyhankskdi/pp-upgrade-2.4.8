<?php
ini_set( 'display_errors', 1 );

error_reporting( E_ALL );

$from = "headoffice@pharmacyplanet.com"; // Sender Email

$to = "dranjan848@gmail.com"; // Send to

$subject = "Checking Gmai mail";

$message = "Gmail works just fine";

$headers = "From:" . $from;

if(mail($to,$subject,$message, $headers)) {

    echo "The email message was sent.";

} else {

    echo "The email message was not sent.";
}

?>
