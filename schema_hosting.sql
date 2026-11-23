-- For InfinityFree: select your database in phpMyAdmin first, then import this file.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS otp_codes, quiz_attempts, questions, content, topics, notifications, incident_updates, attachments, incidents, incident_types, labs, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users(
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(40) NOT NULL UNIQUE,   -- student or staff ID
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(100) NOT NULL UNIQUE,     -- used for one-time passwords and password reset
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('student','staff','technician','admin') NOT NULL DEFAULT 'student',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE otp_codes(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 purpose ENUM('login','reset') NOT NULL,
 code_hash VARCHAR(255) NOT NULL,
 expires_at DATETIME NOT NULL,
 attempts TINYINT DEFAULT 0,
 used TINYINT DEFAULT 0,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE labs(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL);
CREATE TABLE incident_types(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, weight TINYINT NOT NULL DEFAULT 1);
CREATE TABLE incidents(
 id INT AUTO_INCREMENT PRIMARY KEY,
 ref VARCHAR(12),
 reporter_id INT NOT NULL,
 incident_type_id INT NOT NULL,
 lab_id INT NOT NULL,
 device_id VARCHAR(60) DEFAULT '',
 gadget VARCHAR(40) DEFAULT '',
 occurred_at DATETIME,
 description TEXT NOT NULL,
 people_affected INT DEFAULT 1,
 impact TEXT,
 severity ENUM('Low','Medium','High','Critical') DEFAULT 'Low',
 status ENUM('Reported','Received by Admin','Assigned to Technician','In Progress','Handled') DEFAULT 'Reported',
 assigned_to INT NULL,
 escalated TINYINT DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 acknowledged_at DATETIME NULL,
 resolved_at DATETIME NULL,
 FOREIGN KEY(reporter_id) REFERENCES users(id),
 FOREIGN KEY(incident_type_id) REFERENCES incident_types(id),
 FOREIGN KEY(lab_id) REFERENCES labs(id)
);
CREATE TABLE attachments(id INT AUTO_INCREMENT PRIMARY KEY, incident_id INT NOT NULL, filename VARCHAR(100), original VARCHAR(150), FOREIGN KEY(incident_id) REFERENCES incidents(id) ON DELETE CASCADE);
CREATE TABLE incident_updates(id INT AUTO_INCREMENT PRIMARY KEY, incident_id INT NOT NULL, user_id INT NOT NULL, status VARCHAR(30), note TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(incident_id) REFERENCES incidents(id) ON DELETE CASCADE);
CREATE TABLE notifications(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message VARCHAR(255), link VARCHAR(120), is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE topics(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL);
CREATE TABLE content(id INT AUTO_INCREMENT PRIMARY KEY, topic_id INT NOT NULL, title VARCHAR(150) NOT NULL, type ENUM('tip','video','poster','faq','guide') NOT NULL, body TEXT, media VARCHAR(255) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(topic_id) REFERENCES topics(id));
CREATE TABLE questions(id INT AUTO_INCREMENT PRIMARY KEY, topic_id INT NOT NULL, question VARCHAR(255) NOT NULL, a VARCHAR(150), b VARCHAR(150), c VARCHAR(150), d VARCHAR(150), correct CHAR(1) NOT NULL, explanation VARCHAR(255), FOREIGN KEY(topic_id) REFERENCES topics(id));
CREATE TABLE quiz_attempts(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, topic_id INT NOT NULL, score INT, total INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

INSERT INTO labs(name) VALUES('Computer Lab 1'),('Computer Lab 2'),('Computer Lab 3'),('Computer Lab 4'),('ODeL / e-Learning Unit');
INSERT INTO incident_types(name,weight) VALUES
('Malware / virus infection',2),('Phishing / suspicious link',1),('Account hijacking (e.g. WhatsApp)',2),
('Unauthorized software installation',1),('Unauthorized access',2),('Data loss or exposure',3),
('Infected or suspicious USB device',1),('Weak password / account issue',1),('Other',1);
INSERT INTO topics(name) VALUES
('Malware and viruses'),('Phishing and suspicious links'),('Account hijacking and WhatsApp scams'),('Safe USB use'),
('Password security'),('Data protection'),('Unauthorized software and access'),('How to report an incident');
INSERT INTO content(topic_id,title,type,body) VALUES
(1,'Spot the signs of a virus','tip','Slow computer, pop-ups, programs you did not install or files that will not open can all mean malware. Stop using the computer and report it.'),
(2,'Think before you click','tip','A message that rushes you, offers free money or asks for a code is probably a scam. Do not click. Report it.'),
(3,'Someone is asking for money on my WhatsApp','guide','Attackers hack a number, then message its contacts asking for money. Never share the 6-digit WhatsApp code. Turn on two-step verification. If you are hacked, warn your contacts and report it.'),
(4,'Scan every flash disk','tip','Never plug in an unknown USB. Scan your own flash disk before opening files on a lab computer.'),
(5,'Make a strong password','tip','Use at least 12 characters, mix words and numbers, and never reuse a password. Never share it, even with friends.'),
(6,'Back up your work','tip','Save important files in two places. Do not leave personal data on shared lab computers. Log out when you finish.'),
(7,'Only install approved software','faq','Q: Can I install software on a lab computer? A: No. Ask the lab technician. Unapproved programs often carry malware.'),
(8,'How to report in two minutes','guide','Log in, choose Report incident, pick the type and lab, say what happened and attach a screenshot if you have one. You will get a reference number and updates.');
INSERT INTO questions(topic_id,question,a,b,c,d,correct,explanation) VALUES
(1,'A lab PC suddenly runs very slowly and shows pop-ups. What should you do?','Ignore it','Stop using it and report it','Restart and keep working','Install a free cleaner','b','Possible malware. Stop and report so ICT can isolate it.'),
(2,'You get a link saying you won money. What do you do?','Click quickly','Forward to friends','Do not click and report it','Enter your details','c','Prize messages are a common phishing trick.'),
(3,'A contact on WhatsApp asks for money urgently. First step?','Send it','Call them on a normal phone call to confirm','Send your code','Reply with your PIN','b','Their account may be hacked. Confirm by another channel.'),
(3,'Who should you give your WhatsApp 6-digit code to?','A friend','Support staff','Nobody','Your lecturer','c','That code lets someone take over your account.'),
(4,'Before opening files on an unknown flash disk you should:','Open directly','Scan it first','Format the PC','Share it','b','USB devices spread malware easily.'),
(5,'Which is the strongest password?','12345678','student01','Maji!Kahawa7Nyeusi','password','c','Long, mixed and not guessable.'),
(6,'Where should important coursework be saved?','Only the desktop','Two places, such as cloud and flash disk','Nowhere','Shared folder for all','b','Backups protect against data loss.'),
(8,'Why must reports not be anonymous?','To punish people','So incidents can be tracked and followed up','To collect fees','No reason','b','The technician needs to ask for details and send feedback.');
