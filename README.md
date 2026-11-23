# Kenya School of TVET Cybersecurity Awareness and Incident Reporting System (PHP + MySQL)

## Run it on XAMPP
1. Copy the `cyberaware` folder into `C:\xampp\htdocs\` (replace the old one).
2. Start **Apache** and **MySQL**.
3. phpMyAdmin > **Import** > `schema.sql`. **This recreates the tables, so old test data is cleared.**
4. Open http://localhost/cyberaware/install.php once, then delete `install.php`.
5. Log in at http://localhost/cyberaware/

## Roles
| Role | Sees |
|---|---|
| Student / Staff | Home, Awareness, Quizzes, Report incident, **Track my reports**, Alerts |
| Admin | Reported incidents (assign to technician), Dashboard, Awareness, Manage content, Users, Alerts |
| Technician | My assigned cases (update status, mark handled), Awareness, Alerts |

Case stages: Reported > Received by Admin > Assigned to Technician (In Progress) > Handled. Pending rows are red, handled rows turn green.

## Demo accounts (password `Password@123`, change them)
`ict.admin` (admin), `lab.tech` (technician), `staff01`, `student01`.

## Login codes and password reset (email)
Every login asks for a 6-digit code sent to the email given at registration. Forgot password sends a reset code the same way.

**Real email (so codes reach the inbox), using Gmail:**
1. Use a Gmail account for the system. Turn on **2-Step Verification**, then create an **App Password** (Google Account > Security > App passwords). You get a 16-character password.
2. Open `config.php` and fill in: `SMTP_USER` = that Gmail address, `SMTP_PASS` = the App Password, `SMTP_FROM` = the same Gmail address. Save.
3. Open http://localhost/cyberaware/test_email.php, type your own email and press **Send test**. It tells you SENT or shows the exact reason it failed. Fix that first, then try logging in.
4. If it reports a certificate error on XAMPP, set `SMTP_VERIFY_SSL` to `false` in `config.php`.

Until step 2 is done, no email is sent: each code is saved as a text file in the `outbox/` folder instead.
Codes expire after 10 minutes and allow 5 tries. Delete `test_email.php` before going live.

## Roles at registration and login
Both pages have a role dropdown (Student, Staff, Technician, Admin). At login the role must match the account, and you land in that role's module.
Registering as **Admin or Technician needs the access code** `STAFF_ACCESS_CODE` in `config.php` (default `KSTVET-ICT-2026`). Change it and give it only to ICT staff, otherwise anyone could make themselves an admin.

## Notes
- Demo accounts use example.com emails. Change them in Users, or create real admin/technician accounts there.
- Escalation (unreceived reports) runs when the admin opens the home page.
- Before real use: set a MySQL password, use HTTPS, delete the demo accounts and `install.php`.
