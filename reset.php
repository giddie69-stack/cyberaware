<?php require 'config.php';
if(!isset($_SESSION['reset_user'])){header('Location: forgot.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $pw=$_POST['password']??'';
  if(strlen($pw)<8)$err='Password must be at least 8 characters.';
  elseif($pw!==($_POST['confirm']??''))$err='The two passwords do not match.';
  else{
    $r=$_SESSION['reset_user']?check_code($_SESSION['reset_user'],'reset',$_POST['code']??''):false;
    if($r===true){q("UPDATE users SET password_hash=? WHERE id=?",[password_hash($pw,PASSWORD_DEFAULT),$_SESSION['reset_user']]);unset($_SESSION['reset_user']);flash('Password changed. Log in with your new password.');header('Location: login.php');exit;}
    $err=$r==='expired'?'That code has expired. Request a new one.':($r==='locked'?'Too many wrong tries. Request a new code.':'Wrong code. Check the email and try again.');
  }
}
$title='Reset password';$bodyclass='auth';include 'includes/auth_top.php'; ?>
<section class="auth-card"><h2>Reset password</h2><p class="muted">Enter the 6-digit code from your email and choose a new password.</p>
<?php if($f=flash()): ?><p class="flash"><?=h($f)?></p><?php endif; ?>
<?php if($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
<form method="post"><?=csrf()?><label class="req">Reset code</label><input name="code" inputmode="numeric" maxlength="6" required autofocus class="code-input">
<label class="req">New password (8+ characters)</label><input type="password" name="password" required>
<label class="req">Confirm new password</label><input type="password" name="confirm" required>
<button>Change password</button></form><p class="alt muted"><a href="forgot.php">Send a new code</a> &middot; <a href="login.php">Back to log in</a></p></section>
<?php include 'includes/auth_bottom.php';
