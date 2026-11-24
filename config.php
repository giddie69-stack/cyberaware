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
// Anyone who registers as Admin or Technician must type this access code. Give it only to ICT staff, and change it.
define('STAFF_ACCESS_CODE',env('STAFF_ACCESS_CODE','KSTVET-ICT-2026'));
const ROLES=['student'=>'Student','staff'=>'Staff','technician'=>'Technician','admin'=>'Admin'];
const ESCALATE_HOURS_HIGH=2;    // High/Critical reports not received within this many hours alert the admin
const ESCALATE_HOURS_OTHER=24;
const STATUSES=['Reported','Received by Admin','Assigned to Technician','In Progress','Handled'];
const SEVERITIES=['Low','Medium','High','Critical'];

session_start();
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

if(($_SERVER['REQUEST_METHOD']??'')==='POST'&&(($_POST['csrf']??'')!==($_SESSION['csrf']??'x'))){http_response_code(400);exit('Invalid form token. Go back and reload the page.');}
