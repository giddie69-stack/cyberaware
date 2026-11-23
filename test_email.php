<?php // Checks your email settings. Open as test_email.php?key=YOUR_ACCESS_CODE. Delete before going live.
require 'config.php';
if(($_GET['key']??'')!==STAFF_ACCESS_CODE){http_response_code(403);exit('Open this page as test_email.php?key=YOUR_ACCESS_CODE (the STAFF_ACCESS_CODE in config.php).');}
$out='';$log='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(SMTP_USER==='')$out='SMTP_USER is empty in config.php, so no email can be sent. Fill in SMTP_USER, SMTP_PASS and SMTP_FROM, save, and try again.';
  else try{
    $m=make_mailer();$m->SMTPDebug=2;$m->Debugoutput=function($s)use(&$log){$log.=trim($s)."\n";};
    $m->addAddress(trim($_POST['to']));$m->Subject='Test email: '.SYSTEM_NAME;$m->Body="If you can read this, login codes will reach your inbox.";$m->send();
    $out='SENT. Check the inbox (and Spam) of '.trim($_POST['to']).'.';
  }catch(\Throwable $e){$out='FAILED: '.$e->getMessage();}
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Email test</title><link rel="stylesheet" href="assets/style.css"></head><body><main style="max-width:760px">
<h1>Email test</h1><p class="muted">Sending as: <b><?=h(SMTP_USER?:'(not set)')?></b> via <?=h(SMTP_HOST)?>:<?=SMTP_PORT?></p>
<?php if($out): ?><p class="<?=strpos($out,'SENT')===0?'flash':'err'?>"><?=h($out)?></p><?php endif; ?>
<form class="panel" method="post"><?=csrf()?><label class="req">Send a test email to</label><input type="email" name="to" required><p><button>Send test</button></p></form>
<?php if($log): ?><details class="panel"><summary>Technical log</summary><pre style="white-space:pre-wrap;font-size:.8rem"><?=h($log)?></pre></details><?php endif; ?>
<p class="muted">Common problems: Gmail needs a 16-character <b>App Password</b> (not your normal password) with 2-Step Verification on. A certificate error on XAMPP: set SMTP_VERIFY_SSL to false in config.php. "Could not connect": check your internet and that port 587 is not blocked.</p>
</main></body></html>
