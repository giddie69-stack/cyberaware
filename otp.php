<?php require 'config.php';
if(empty($_SESSION['pending'])){header('Location: login.php');exit;}
$u=q("SELECT * FROM users WHERE id=?",[$_SESSION['pending']['id']])->fetch();$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['action']??'')==='resend'){
    if(time()-$_SESSION['pending']['sent']<60)$err='Please wait a minute before asking for another code.';
    else{$_SESSION['pending']['sent']=time();send_code($u,'login');if(!flash())flash('A new code has been sent.');header('Location: otp.php');exit;}
  }else{
    $r=check_code($u['id'],'login',$_POST['code']??'');
    if($r===true){session_regenerate_id(true);unset($u['password_hash']);$_SESSION['user']=$u;unset($_SESSION['pending']);header('Location: index.php');exit;}
    $err=$r==='expired'?'That code has expired. Ask for a new one.':($r==='locked'?'Too many wrong tries. Ask for a new code.':'Wrong code. Check the email and try again.');
  }
}
$title='Enter code';$bodyclass='auth';include 'includes/auth_top.php'; ?>
<section class="auth-card"><h2>Check your email</h2><p class="muted">We sent a 6-digit code to <b><?=h(mask_email($u['email']))?></b>. It expires in 10 minutes.</p>
<?php if($f=flash()): ?><p class="flash"><?=h($f)?></p><?php endif; ?>
<?php if($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
<form method="post"><?=csrf()?><label class="req" for="code">One-time code</label><input id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code" class="code-input">
<button>Verify and log in</button></form>
<form method="post"><?=csrf()?><input type="hidden" name="action" value="resend"><button class="ghost-btn">Send a new code</button></form>
<p class="alt muted"><a href="login.php">Back to log in</a></p></section>
<?php include 'includes/auth_bottom.php';
