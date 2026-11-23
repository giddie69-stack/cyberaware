<?php require 'config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=trim($_POST['identifier']??'');$u=q("SELECT * FROM users WHERE email=? OR username=?",[$id,$id])->fetch();
  $_SESSION['reset_user']=$u?$u['id']:0;
  if($u)send_code($u,'reset');
  if(!$u)flash('If that account exists, a reset code has been sent to its email.');
  header('Location: reset.php');exit;
}
$title='Forgot password';$bodyclass='auth';include 'includes/auth_top.php'; ?>
<section class="auth-card"><h2>Forgot password</h2><p class="muted">Enter your email or ID. We will send a reset code to the email you registered with.</p>
<form method="post"><?=csrf()?><label class="req">Email or student / staff ID</label><input name="identifier" required autofocus>
<button>Send reset code</button></form><p class="alt muted"><a href="login.php">Back to log in</a></p></section>
<?php include 'includes/auth_bottom.php';
