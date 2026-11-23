<?php require 'config.php';
if(user()){header('Location: index.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=q("SELECT * FROM users WHERE username=?",[trim($_POST['username']??'')])->fetch();
  if($u&&password_verify($_POST['password']??'',$u['password_hash'])){
    $chosen=$_POST['role']??'';
    if($chosen!==$u['role'])$err='This account is not registered as '.(ROLES[$chosen]??'that role').'. Choose the role you registered with.';
    else{$_SESSION['pending']=['id'=>$u['id'],'sent'=>time()];send_code($u,'login');header('Location: otp.php');exit;}
  }else $err='Wrong ID or password. Check both and try again.';
}
$title='Log in';$bodyclass='auth';include 'includes/auth_top.php'; ?>
<section class="auth-card"><h2>Log in</h2><p class="muted">Choose your role and enter your ID. We will email you a one-time code to finish logging in.</p>
<?php if($f=flash()): ?><p class="flash"><?=h($f)?></p><?php endif; ?>
<?php if($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
<form method="post"><?=csrf()?>
<label class="req" for="role">Log in as</label><select id="role" name="role" required><?php foreach(ROLES as $k=>$v): ?><option value="<?=$k?>" <?=($_POST['role']??'student')===$k?'selected':''?>><?=$v?></option><?php endforeach; ?></select>
<label class="req" for="username">Student / staff ID</label><input id="username" name="username" required autofocus autocomplete="username">
<label class="req" for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password">
<button>Log in</button></form>
<p class="alt muted"><a href="forgot.php">Forgot password?</a> &middot; New here? <a href="register.php">Create an account</a></p>
<p class="muted req-note">* required</p></section>
<?php include 'includes/auth_bottom.php';
