<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; 

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function hantarEmel($penerima, $subjek, $isi) {
    $mail = new PHPMailer(true);

    try {
        // Tutup Debug (PENTING: Set 0)
        $mail->SMTPDebug = 0; 

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@keda.gov.my'; 
        $mail->Password   = 'pdbp eawb ixvv hjyp'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@keda.gov.my', 'Sistem MyApps');
        $mail->addAddress($penerima);

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = $isi;
        $mail->AltBody = strip_tags($isi);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Jika gagal, pulangkan false (tanpa paparkan error pada user)
        return false;
    }
}
?>
