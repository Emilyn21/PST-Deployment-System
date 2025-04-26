<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://smtp.gmail.com:587");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CAINFO, 'C:/xampp/php/extras/ssl/cacert.pem');
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    echo 'cURL connection successful.';
}
curl_close($ch);
?>
