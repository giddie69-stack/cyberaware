<?php
// ---- Settings: change these for your setup ----
// Database: PostgreSQL. On Render set DATABASE_URL (the database's Internal Database URL).
// Without it, DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS are used (second value = local default).
function env($k,$d){$v=getenv($k);return $v===false||$v===''?$d:$v;}
$dbu=parse_url(env('DATABASE_URL',''))?:[];
define('DB_HOST',$dbu['host']??env('DB_HOST','localhost'));define('DB_PORT',(int)($dbu['port']??env('DB_PORT','5432')));
define('DB_NAME',isset($dbu['path'])?ltrim($dbu['path'],'/'):env('DB_NAME','cyberaware'));
define('DB_USER',isset($dbu['user'])?urldecode($dbu['user']):env('DB_USER','postgres'));
define('DB_PASS',isset($dbu['pass'])?urldecode($dbu['pass']):env('DB_PASS',''));
unset($dbu);
const SYSTEM_NAME='Kenya School of TVET Cybersecurity Awareness and Incident Reporting System';
// Email (for one-time passwords and password reset). Leave SMTP_USER empty to save emails in the /outbox folder instead (testing).
define('SMTP_HOST',env('SMTP_HOST','smtp.gmail.com'));define('SMTP_PORT',(int)env('SMTP_PORT','587'));
define('SMTP_USER',env('SMTP_USER',''));define('SMTP_PASS',env('SMTP_PASS',''));define('SMTP_FROM',env('SMTP_FROM',''));
define('SMTP_VERIFY_SSL',env('SMTP_VERIFY_SSL','true')!=='false');     // set to false only if test_email.php reports a certificate error on your XAMPP
// Anyone who registers as Admin or Technician must type this access code. Give it only to ICT staff, and change it.
define('STAFF_ACCESS_CODE',env('STAFF_ACCESS_CODE','KSTVET-ICT-2026'));
const ROLES=['student'=>'Student','staff'=>'Staff','technician'=>'Technician','admin'=>'Admin'];
const ESCALATE_HOURS_HIGH=2;    // High/Critical reports not received within this many hours alert the admin
const ESCALATE_HOURS_OTHER=24;
const STATUSES=['Reported','Received by Admin','Assigned to Technician','In Progress','Handled'];
const SEVERITIES=['Low','Medium','High','Critical'];

session_start();
require_once __DIR__.'/includes/mailer.php';
function db(){static $p;if(!$p){$p=new PDO("pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$p->exec("SET TIME ZONE 'Africa/Nairobi'");}return $p;}
function q($sql,$args=[]){$s=db()->prepare($sql);$s->execute($args);return $s;}
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function user(){return $_SESSION['user']??null;}
function is_admin(){return user()&&user()['role']==='admin';}
function is_tech(){return user()&&user()['role']==='technician';}
function is_reporter(){return user()&&in_array(user()['role'],['student','staff']);}
function login_required(){if(!user()){header('Location: login.php');exit;}}
function role_required(...$r){login_required();if(!in_array(user()['role'],$r)){http_response_code(403);exit('Access denied');}}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">';}
function flash($m=null){if($m!==null){$_SESSION['flash']=$m;return '';}$f=$_SESSION['flash']??'';unset($_SESSION['flash']);return $f;}
function chip($v){return '<span class="chip c-'.strtolower(str_replace(' ','-',$v)).'">'.h($v).'</span>';}
function ago($m){return $m<60?$m.' min':($m<1440?round($m/60).' h':round($m/1440).' d');}
function row_class($status){return $status==='Handled'?'done':'pending';}
function notify($uid,$msg,$link=''){q("INSERT INTO notifications(user_id,message,link) VALUES(?,?,?)",[$uid,$msg,$link]);}
function notify_role($roles,$msg,$link=''){foreach(q("SELECT id FROM users WHERE role IN (".implode(',',array_fill(0,count($roles),'?')).")",$roles) as $u)notify($u['id'],$msg,$link);}
function log_update($iid,$uid,$status,$note){q("INSERT INTO incident_updates(incident_id,user_id,status,note) VALUES(?,?,?,?)",[$iid,$uid,$status,$note]);}

// Progress tracker shown to the person who reported the case
function stage_index($s){return ['Reported'=>0,'Received by Admin'=>1,'Assigned to Technician'=>2,'In Progress'=>2,'Handled'=>3][$s]??0;}
function tracker($s){
  $labels=['Reported','Received by Admin','Assigned to Technician','Handled'];$i=stage_index($s);$o='<ol class="steps">';
  foreach($labels as $k=>$l){$c=($k<$i||($k==3&&$i==3))?'done':($k==$i?'now':'');$o.='<li class="'.$c.'">'.h($l).($k==2&&$s==='In Progress'?'<br><small>work in progress</small>':'').'</li>';}
  return $o.'</ol>';
}
// Rule-based severity suggestion (technician or admin can override)
function suggest_severity($weight,$people){$p=(int)$weight+($people>=10?2:($people>=3?1:0));return $p>=5?'Critical':($p>=4?'High':($p>=2?'Medium':'Low'));}
// Escalation: reports nobody has received in time alert the admin (runs when the admin opens the home page)
function run_escalation(){
  foreach(q("SELECT id,ref,severity FROM incidents WHERE status='Reported' AND escalated=0 AND created_at<=NOW()-make_interval(hours=>CASE WHEN severity IN ('High','Critical') THEN ?::int ELSE ?::int END)",[ESCALATE_HOURS_HIGH,ESCALATE_HOURS_OTHER])->fetchAll() as $r){
    q("UPDATE incidents SET escalated=1 WHERE id=?",[$r['id']]);notify_role(['admin'],"ESCALATION: {$r['ref']} ({$r['severity']}) has not been received yet",'incident.php?id='.$r['id']);}
}

// ---- One-time codes (login OTP and password reset) ----
function send_code($u,$purpose){
  q("UPDATE otp_codes SET used=1 WHERE user_id=? AND purpose=? AND used=0",[$u['id'],$purpose]);
  $code=sprintf('%06d',random_int(0,999999));
  q("INSERT INTO otp_codes(user_id,purpose,code_hash,expires_at) VALUES(?,?,?,NOW()+INTERVAL '10 minutes')",[$u['id'],$purpose,password_hash($code,PASSWORD_DEFAULT)]);
  $what=$purpose==='login'?'login code':'password reset code';
  $r=send_mail($u['email'],$u['full_name'],SYSTEM_NAME.': your '.$what,"Hello {$u['full_name']},\n\nYour $what is: $code\n\nIt expires in 10 minutes. If you did not ask for it, ignore this email and do not share the code.\n\n".SYSTEM_NAME);
  if($r==='logged')flash('Email sending is not set up yet, so no email was sent. Add your SMTP details in config.php (see README, step "Real email"). For now the code is in the outbox folder.');
  elseif($r==='failed')flash('The email could not be sent. Open test_email.php on the host computer to see the exact reason.');
  return $r;
}
function check_code($uid,$purpose,$code){ // true, false, 'expired' or 'locked'
  $r=q("SELECT * FROM otp_codes WHERE user_id=? AND purpose=? AND used=0 AND expires_at>NOW() ORDER BY id DESC LIMIT 1",[$uid,$purpose])->fetch();
  if(!$r)return 'expired';if($r['attempts']>=5)return 'locked';
  q("UPDATE otp_codes SET attempts=attempts+1 WHERE id=?",[$r['id']]);
  if(password_verify(trim($code),$r['code_hash'])){q("UPDATE otp_codes SET used=1 WHERE id=?",[$r['id']]);return true;}
  return false;
}
function mask_email($e){[$a,$b]=explode('@',$e)+[1=>''];return substr($a,0,2).str_repeat('*',max(2,strlen($a)-2)).'@'.$b;}
if($_SERVER['REQUEST_METHOD']==='POST'&&(($_POST['csrf']??'')!==($_SESSION['csrf']??'x'))){http_response_code(400);exit('Invalid form token. Go back and reload the page.');}
