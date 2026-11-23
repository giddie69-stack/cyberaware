<?php
// Builds the SMTP mailer (PHPMailer) from the settings in config.php
function make_mailer(){
  require_once __DIR__.'/phpmailer/Exception.php';require_once __DIR__.'/phpmailer/PHPMailer.php';require_once __DIR__.'/phpmailer/SMTP.php';
  $m=new \PHPMailer\PHPMailer\PHPMailer(true);$m->isSMTP();$m->Host=SMTP_HOST;$m->SMTPAuth=true;$m->Username=SMTP_USER;$m->Password=SMTP_PASS;
  $m->SMTPSecure=SMTP_PORT==465?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;$m->Port=SMTP_PORT;$m->CharSet='UTF-8';$m->Timeout=15;
  if(!SMTP_VERIFY_SSL)$m->SMTPOptions=['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
  $m->setFrom(SMTP_FROM?:SMTP_USER,'Kenya School of TVET');return $m;
}
// With no SMTP_USER set, saves the email as a text file in /outbox for testing.
function send_mail($to,$name,$subject,$body){
  if(SMTP_USER===''){
    @file_put_contents(__DIR__.'/../outbox/'.date('Ymd_His').'_'.preg_replace('/[^a-z0-9]/i','_',$to).'.txt',"To: $to\nSubject: $subject\n\n$body");
    return 'logged';
  }
  try{$m=make_mailer();$m->addAddress($to,$name);$m->Subject=$subject;$m->Body=$body;$m->send();return 'sent';}
  catch(\Throwable $e){@file_put_contents(__DIR__.'/../outbox/errors.txt',date('c').' '.$e->getMessage()."\n",FILE_APPEND);return 'failed';}
}
