CHARUSAT STUDENT PORTAL + STUDENT MANAGEMENT
===========================================

Files:
- database.sql       Database and tables
- db.php             MySQLi connection, escaping, CSRF and flash helpers
- auth.php           Login/session protection
- Login.php          Login form
- Login.html         Compatibility redirect page (see note below)
- login.php          Login handler
- Register.html      Registration form
- register.php       Registration handler
- register.js        Client-side password confirmation
- login.js           Login form validation (no localStorage authentication)
- Login.css          Login styles
- Register.css       Registration styles
- darktheme.css/js   Dark mode
- admin.php          Admin-only route
- dashboard.php      Student landing page
- students.php       Admin-only student CRUD/search/filter module
- portal.css         Student management styling
- logout.php         Logout handler
- database.sql       Database schema

EXECUTION (XAMPP):
1. Extract the folder to C:\xampp\htdocs\charusat-student-portal\
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin and import database.sql.
4. Check db.php credentials (default root with blank password).
5. Open http://localhost/charusat-student-portal/Register.html and register a user.
6. Promote your own account to admin in phpMyAdmin SQL tab:
   UPDATE users SET role='admin' WHERE email='YOUR-EMAIL-HERE';
   Do not let users select their own role in public registration.
7. Login at http://localhost/charusat-student-portal/Login.php
8. Admin users are redirected to admin.php, then students.php.
   Student users are redirected to dashboard.php.

IMPORTANT:
- Login.html is kept as a legacy compatibility redirect, but a PHP page is needed to generate CSRF tokens.
  Use Login.php as the login URL. If you want the exact URL Login.html to work, replace Login.html with:
  <?php header('Location: Login.php'); exit; ?>
  and rename it from .html to .php, or just use Login.php.
- If charusat_portal already has a users table, BACK IT UP and compare its columns to database.sql first.
  Existing table definitions are not changed by CREATE TABLE IF NOT EXISTS. Ensure role, remember_token_hash,
  remember_token_expires and required registration columns exist. Do not import the SQL blindly into a live database.
- The students table is separate from users. It stores academic/student directory records. Student CRUD is admin-only.
- This project is for local coursework/development. Before public deployment, add login rate limiting, HTTPS,
  careful production session settings, and a least-privilege database account.

EVENT MANAGEMENT (NEW)
----------------------
- Sign in with an account whose users.role is 'admin'.
- Open Admin home, then Event Management, or visit events.php.
- Add, view, edit, and delete events. Posters are optional and can be replaced while editing.
- Allowed poster image types: JPG/JPEG, PNG, and WEBP. Maximum file size: 5 MB.
- Poster files are stored under uploads/event-posters/ with random filenames. Do not remove the .htaccess file in that folder.
- Import the updated database.sql to create the events table. If your database already exists, you can run only the CREATE TABLE IF NOT EXISTS events statement from that file in phpMyAdmin.
- If uploads larger than your PHP limit are rejected, update upload_max_filesize to at least 5M and post_max_size to at least 6M in php.ini, then restart Apache. Ensure uploads/event-posters is writable by the web server.
