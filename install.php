<?php // Run once after importing schema.sql, then DELETE this file.
require 'config.php';
if(q("SELECT COUNT(*) c FROM users")->fetch()['c']>0)exit('Already installed. Delete install.php.');
$pw=password_hash('Password@123',PASSWORD_DEFAULT);
foreach([['ict.admin','ICT Administrator','admin@example.com','admin'],['lab.tech','Lab Technician','tech@example.com','technician'],['staff01','Demo Staff Member','staff@example.com','staff'],['student01','Demo Student','student@example.com','student']] as $u)
  q("INSERT INTO users(username,full_name,email,password_hash,role) VALUES(?,?,?,?,?)",[$u[0],$u[1],$u[2],$pw,$u[3]]);
echo 'Demo users created (password: Password@123): ict.admin (admin), lab.tech (technician), staff01 (staff), student01 (student). Login codes are saved in the outbox folder until you set up email in config.php. <b>Delete install.php now.</b> <a href="login.php">Log in</a>';
