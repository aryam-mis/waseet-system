# waseet-system
Dispute &amp; Mediation Management System — PHP / MySQL
Waseet — Dispute & Mediation Management System

وسيط — نظام إدارة النزاعات والوساطة

A full-stack web application for managing legal mediation cases, built as a graduation project at Taibah University.

📌 Project Overview
Waseet is a dispute management platform designed for legal departments. It supports the full lifecycle of a mediation case — from submission and assignment to session scheduling, settlement, and final decision — across multiple user roles with independent dashboards and access controls.

✨ Key Features

Multi-role authentication — 5 user roles with independent dashboards and permissions
Case lifecycle management — from OPEN → UNDER_REVIEW → IN_MEDIATION → CLOSED
Session scheduling — create and manage mediation sessions with conflict detection
Settlement tracking — record and respond to settlement proposals
Case escalation — escalate unresolved cases to upper management
Final decisions — issue binding decisions on escalated cases
Reports — case statistics and activity reports
Admin panel — user management and account approval system
Arabic RTL interface — fully localized for Arabic-speaking users


👥 User Roles
RoleDescriptionAdminManages users, approves accounts, full system accessEmployeeSubmits disputes, views assigned casesLegal DepartmentHandles cases, schedules sessions, records settlementsUpper ManagementReviews escalated cases, issues final decisionsSystem ManagerMonitors system activity and generates reports

🗄️ Database Schema
8 tables — MySQL
TableDescriptionemployeesSystem users with role-based accesscasesDispute cases with status trackingcasehandlersCase assignments to legal/management staffcasepartiesParties involved in each casesessionsMediation sessions with schedulingsession_partiesSession participantssettlementresponsesSettlement proposals and responsesfinaldecisionsFinal rulings on escalated cases

🛠️ Technologies Used
TechnologyPurposePHP 8.2Backend logic and server-side renderingMySQL 8.3Relational databaseHTML5 / CSS3Frontend structure and stylingJavaScriptClient-side interactionsphpMyAdminDatabase managementXAMPPLocal development environment

📁 Project Structure
waseet-system/
│
├── index.php                  # Landing / login redirect
├── login.php                  # Authentication
├── register.php               # New user registration
├── logout.php                 # Session termination
│
├── admin_dashboard.php        # Admin home
├── admin_requests.php         # User approval management
├── manage_users.php           # User CRUD
│
├── employee_dashboard.php     # Employee home
├── submit_dispute.php         # New case submission
│
├── legal_dashboard.php        # Legal dept home
├── legal_view_cases.php       # Case list and management
├── create_session.php         # Schedule mediation session
├── create_settlement.php      # Record settlement proposal
├── view_sessions.php          # Session overview
├── view_settlements.php       # Settlement overview
│
├── system_dashboard.php       # System manager home
├── reports.php                # Case statistics
│
├── view_escalated.php         # Escalated cases
├── view_recommendations.php   # Recommendations log
├── view_responses.php         # Settlement responses
│
└── wasit_system.sql           # Full database schema + seed data

🚀 How to Run Locally
Requirements

XAMPP (PHP 8.2 + MySQL 8.3)
phpMyAdmin

Setup Steps
bash# 1. Clone the repository
git clone https://github.com/aryam-mis/waseet-system.git

# 2. Copy to XAMPP htdocs
cp -r waseet-system/ C:/xampp/htdocs/

# 3. Import the database
# Open phpMyAdmin → Create database 'wasit_system' → Import wasit_system.sql

# 4. Start Apache + MySQL in XAMPP

# 5. Open in browser
http://localhost/waseet-system/
Default Admin Login
Username: admin
Password: password

📸 Case Status Flow
OPEN → UNDER_REVIEW → IN_MEDIATION → CLOSED_SETTLED
                                   ↘ ESCALATED → CLOSED_DECIDED

📚 Academic Context

Project Type: Graduation Project
University: Taibah University — College of Business Administration
Program: Bachelor's in Management Information Systems
Developer: Aryam Mohammed Almohammedi
Year: 2025–2026



Built as a graduation project demonstrating full-stack development, database design, and role-based access control in a real-world legal context.
