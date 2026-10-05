# WorkShift — Freelance Client CRM

**WorkShift** is a client relationship and project management tool built from scratch for freelancers and independent contractors by **Studio Click Up**.

Every client receives their own dedicated workspace board that the freelancer and client use together. Unlike generic tools (Trello, Asana), WorkShift solves the primary reason projects stall: **it clearly shows whether a deliverable is blocked on the freelancer or waiting on the client, and why**, backed by built-in time tracking, deliverables proof, and in-app invoicing.

Target market: Freelancers & independent contractors in the Philippines (designers, developers, consultants, coaches, photographers). Currency is Philippine Peso (PHP ₱).

---

## 🚀 Quick Start (Run Locally in 2 Minutes)

### Requirements
- **PHP 8.1+** with `pdo_mysql` (or `pdo_sqlite`), `fileinfo`, `session`, `openssl` enabled.
- **MySQL 5.7+ or 8.0+ / MariaDB 10.3+** running locally (e.g., via XAMPP, Laragon, or standalone service).
- No Node, no Composer dependencies, and no build tools required.

### 1. Configure Database Credentials
Edit `config/config.php` (already preconfigured with local MySQL defaults):
```php
'database' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'workshift_db',
    'username' => 'root',
    'password' => '',
],
```

### 2. Initialize Database & Seed Demo Data
Run the initializer script from the project root:
```bash
php database/init_db.php
```
*Alternatively, you can visit `http://localhost:8000/install` in your browser to run the 1-click database setup.*

### 3. Start the PHP Built-in Server
```bash
php -S localhost:8000 -t public
```

Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## ⚡ Default Demo Login & Client Portals

### Freelancer Account
- **URL**: [http://localhost:8000/login](http://localhost:8000/login)
- **Email**: `alex@studioclickup.com`
- **Password**: `password123`
- **Plan**: `Pro` (You can toggle between Basic and Pro in Settings &rarr; Developer Testing Mode)

### Demo Client Portals (No Password Required)
Each client has a dedicated unguessable token link:
1. **Acme Tech PH (Maria Santos)**:
   - `http://localhost:8000/portal/acme-portal-demo-token-12345`
2. **Boutique Brew Cafe (Carlos Mendoza)**:
   - `http://localhost:8000/portal/brew-portal-demo-token-67890`
3. **Island Peak Resorts (Elena Gomez)**:
   - `http://localhost:8000/portal/island-portal-demo-token-54321`

---

## 📂 Project Architecture & Folder Structure

```
workshift-3/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, register, password reset, rate limiting
│   │   ├── BoardController.php         # Dedicated client board & stages
│   │   ├── ClientController.php        # CRM profiles, pipeline stages, portal token generator
│   │   ├── DashboardController.php     # Freelancer overview: blocked tasks, hours, invoices
│   │   ├── FileController.php          # Secure deliverable downloads with auth checks
│   │   ├── InstallController.php       # 1-Click web database installer
│   │   ├── InvoiceController.php       # Invoicing, line items builder, print view
│   │   ├── LandingController.php       # High-converting public landing page
│   │   ├── NotificationController.php  # In-app alerts (approvals, uploads, payments)
│   │   ├── PortalController.php        # Secure client portal with strict data isolation
│   │   ├── SettingsController.php      # Profile, plan limits, and dev mode toggle
│   │   ├── TaskController.php          # Task drawer, blocker engine, comments, files
│   │   └── TimeController.php          # Live timer & manual time logging
│   ├── Core/
│   │   ├── Controller.php              # Base controller with view and auth helpers
│   │   ├── Database.php                # PDO connection singleton with prepared statements
│   │   ├── Model.php                   # Base model
│   │   ├── Request.php                 # HTTP request, CSRF verification, input parsing
│   │   ├── Response.php                # JSON responses, redirects, error renderers
│   │   ├── Router.php                  # Regex-based URL router with route parameters
│   │   ├── Session.php                 # Secure cookies, session regeneration, flash messages
│   │   └── View.php                    # Server-rendered PHP templates with layout wrapping
│   ├── Helpers/
│   │   ├── Auth.php                    # Authentication state and current user provider
│   │   └── functions.php               # e(), format_currency(), blocker_badge(), logo_svg()
│   ├── Models/
│   │   ├── Board.php                   # One dedicated board per client
│   │   ├── Client.php                  # CRM client records & lead pipeline
│   │   ├── Comment.php                 # Task discussion thread
│   │   ├── Invoice.php                 # Invoices with auto-overdue tracking
│   │   ├── InvoiceItem.php             # Itemized invoice line items
│   │   ├── Notification.php            # In-app notification alerts
│   │   ├── PortalToken.php             # Unguessable client access tokens
│   │   ├── Stage.php                   # Board columns (To Do, In Progress, In Review, Done)
│   │   ├── Task.php                    # Task cards with review status and blockers
│   │   ├── TaskBlocker.php             # Signature blocker labels & days waiting
│   │   ├── TaskFile.php                # Deliverables and proof of work
│   │   ├── TimeEntry.php               # Time logs (billable / private)
│   │   └── User.php                    # Freelancers, passwords, quotas, rate limiting
│   ├── Services/
│   │   ├── FileStorageService.php      # MIME verification, randomized filenames, safe headers
│   │   ├── Mailer.php                  # PHP mail(), SMTP, or file logging (storage/logs/mail.log)
│   │   ├── Payment/
│   │   │   ├── PaymentProviderInterface.php
│   │   │   ├── MockPaymentProvider.php # Simulated instant in-app checkout
│   │   │   └── MayaPaymentProvider.php # Maya Business (QR Ph) integration architecture
│   │   ├── PlanLimitService.php        # Basic vs Pro tier restrictions
│   │   └── ReminderService.php         # Automated email alerts for stalled blockers
│   └── Views/
│       ├── layouts/ (main.php, auth.php, portal.php, public.php)
│       ├── auth/ (login, register, forgot, reset)
│       ├── boards/ (show.php)
│       ├── clients/ (index.php, pipeline.php, form.php, view.php)
│       ├── dashboard/ (index.php)
│       ├── errors/ (403.php, 404.php, 500.php)
│       ├── install/ (index.php)
│       ├── invoices/ (index.php, create.php, view.php, print.php)
│       ├── landing/ (index.php)
│       ├── notifications/ (index.php)
│       ├── portal/ (board.php, invoice_pay.php, pay_success.php)
│       ├── settings/ (index.php)
│       └── tasks/ (modal_content.php)
├── config/
│   ├── config.php                      # Application configuration
│   └── config.example.php              # Production template
├── cron/
│   └── send_reminders.php              # Stalled blocker reminder cron runner
├── database/
│   ├── schema.sql                      # Complete MySQL schema with FKs and indexes
│   ├── seed.sql                        # Realistic demo data
│   ├── Migrator.php                    # Migration utility class
│   └── init_db.php                     # CLI setup runner
├── public/
│   ├── .htaccess                       # Apache rewrite rules and security headers
│   ├── favicon.svg                     # Dual slanted blue blocks logo
│   ├── index.php                       # Front controller & routing
│   └── assets/
│       ├── css/ (app.css, print.css)   # Pure CSS variables, modern blue theme
│       └── js/ (app.js, board.js, timer.js, portal.js) # Vanilla JS + SortableJS CDN
└── storage/
    ├── .htaccess                       # Blocks direct web access
    ├── uploads/                        # Deliverables stored outside public root
    └── logs/                           # Mail and application logs
```

---

## 🌟 Core Features Built

### 1. Signature Blocker Engine
- Each task can carry **ONE** active blocker label:
  - **Feedback**: Waiting on client review or approval.
  - **Content**: Waiting on assets, copy, or credentials from client.
  - **Payment**: Waiting on deposit or invoice payment before next milestone.
  - **Scheduling**: Waiting on client to book onboarding/review call.
- Color-coded badges with live days-waiting counter (e.g. `Waiting on Feedback · 3d ago`).
- Blockers are informational and do **not** freeze the board.
- Dashboard highlights tasks blocked on client vs blocked on freelancer.

### 2. Client CRM & Pipelines
- Profiles with contact info, company name, billing type (`hourly` or `fixed`), rates, and internal notes.
- Lead pipeline stages: `Inquiry` &rarr; `Active` &rarr; `Completed` &rarr; `Archived`.
- Table list with stage filters and search, plus a drag-and-drop / select Kanban pipeline view.
- Adding a client automatically generates their dedicated workspace board with default stages: *To Do*, *In Progress*, *In Review* (review approval enabled), and *Done*.

### 3. Dedicated Boards & SortableJS Drag-and-Drop
- One board per client.
- Drag tasks between stage columns and reorder them within a column; position changes save instantly via AJAX.
- Task detail modal drawer with inline title/description editing, blocker configuration, deliverable file management, and discussion thread.
- Board filter to show only tasks currently waiting on client input.
- Summary strip: *"3 tasks waiting on client · 1 waiting on you"*.

### 4. Built-in Time Tracker
- Live start/stop timer widget in the top header with real-time seconds ticker and pulsing indicator.
- Manual time entry (date, minutes/hours, notes).
- For hourly clients: hours appear transparently on the client's board.
- For fixed-price clients: freelancer-only visibility toggle (`is_private`) hides internal time logs from client view.
- Weekly hours metric on the dashboard.

### 5. Client Portal (Strict Query Isolation)
- Unguessable invite link (`/portal/{token}`). No client signup or passwords required.
- Real-time polling (~10 seconds) keeps the board live.
- Clients can:
  - View board and visible time log.
  - Upload deliverable assets and files.
  - **Approve work** or **Request revisions** (with feedback notes) on tasks in "In Review".
  - Pay invoices online via simulated Maya Business / QR Ph checkout (Pro plan).
  - Click "Book a Call" linking directly to the freelancer's Calendly / Cal.com link.
- **Strict Server-Side Isolation**: All portal queries are scoped strictly by the token-resolved `client_id`. Clients can never access internal notes, private time logs, or other clients' boards.

### 6. Invoices & Billing
- Create invoices from tracked time or fixed milestone amounts.
- Dynamic line items builder with live subtotal and tax calculation.
- Statuses: `Draft`, `Sent`, `Paid`, `Overdue` (auto-marked overdue when past due date).
- Clean browser print / PDF export stylesheet (`/invoices/{id}/print`).
- Payment provider abstraction (`PaymentProviderInterface`, `MockPaymentProvider`, and `MayaPaymentProvider` stub with webhook handling).

### 7. Automated Stalled Reminders
- Pro plan includes automated reminder emails sent to clients when a blocker has been waiting for more than 3 days (configurable in `config/config.php`).
- Run via server cron: `php cron/send_reminders.php`.
- Creates an in-app notification for the freelancer when sent.

### 8. Plans & Feature Gating
- **Basic (Free)**: Up to 3 active clients, 2 GB storage limit, full blocker system, time tracker, basic portal.
- **Pro (₱499/mo)**: Unlimited active clients, 50 GB storage, in-app client payments, automated reminder emails, custom scheduling link integration.
- Upgrade prompt banners appear when exceeding 3 active clients or accessing Pro features.
- Built-in Developer Testing Switch in **Settings** allows toggling between Basic and Pro with one click for testing.

### 9. Secure File Uploads
- Stored outside the public directory in `storage/uploads/`.
- Downloaded exclusively via authenticated streaming script (`/files/{id}/download`).
- Validates MIME type and extension against an allowlist.
- Randomized filenames (`bin2hex(random_bytes(16))`) prevent execution of malicious uploads.
- Secure HTTP headers: `X-Content-Type-Options: nosniff`, `Content-Disposition: attachment`.

---

## 🌐 Deploying to Shared Hosting (Hostinger / cPanel)

### Step 1: Upload Files
1. In Hostinger hPanel or cPanel File Manager, upload all files to your domain directory (e.g. `public_html/`).
2. If your host allows setting the Document Root to `/public`, set it to `public_html/public`.
3. If your host forces Document Root to `public_html`, the included root `.htaccess` will automatically rewrite requests to the `public/` directory while protecting `config/`, `app/`, and `storage/`.

### Step 2: Create MySQL Database
1. In hPanel &rarr; **Databases**, create a new MySQL Database (e.g., `u123456789_workshift`) and user with a strong password.
2. Edit `config/config.php` on the server and update your database credentials:
   ```php
   'database' => [
       'driver' => 'mysql',
       'host' => 'localhost',
       'port' => '3306',
       'database' => 'u123456789_workshift',
       'username' => 'u123456789_admin',
       'password' => 'YourStrongDbPassword',
   ],
   ```

### Step 3: Run Database Installer
Visit `https://yourdomain.com/install` in your browser and click **Run Database Setup Now**. It will create all 16 tables and load the demo freelancer account and sample client boards.

### Step 4: Configure Email & Cron Job
1. In `config/config.php`, configure Hostinger SMTP or PHP mail:
   ```php
   'mail' => [
       'driver' => 'mail', // or 'smtp'
       'from_address' => 'notifications@yourdomain.com',
       'from_name' => 'WorkShift',
   ],
   ```
2. Set up the automated blocker reminder Cron Job in hPanel &rarr; **Cron Jobs**:
   - Schedule: Daily at 9:00 AM (`0 9 * * *`)
   - Command:
     ```bash
     /usr/bin/php /home/u123456789/public_html/cron/send_reminders.php > /dev/null 2>&1
     ```

---

## 🧪 Security & Verification Checklist
- [x] Prepared statements on all database queries via PDO (no raw SQL injection vectors).
- [x] Output escaping (`htmlspecialchars`) across all template views.
- [x] CSRF protection tokens verified on all state-changing `POST`/`DELETE` requests.
- [x] Login rate limiting (5 attempts per 15 minutes tracked in `login_attempts`).
- [x] Secure session cookie settings (`HttpOnly`, `SameSite=Lax`).
- [x] Strict client portal isolation via token-resolved client IDs.
- [x] Uploads stored outside web root with randomized filenames and MIME validation.
- [x] All PHP files linted cleanly (`php -l`).
