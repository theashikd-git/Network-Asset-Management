<div align="center">

<img src="https://img.shields.io/badge/NAMIAS-Network%20Asset%20Management-4f46e5?style=for-the-badge&logoColor=white" alt="NAMIAS Banner"/>

# 🖧 NAMIAS   Network Asset Management Information System

**A web-based administration system for managing IT assets, network IP allocations,**
**and procurement wishlists within an organisation   with role-based access control.**

Gives IT administrators a centralised dashboard to track hardware, monitor warranty
status, run filtered reports, and manage network configurations   while regular staff
get a clean read-only view.

<br/>

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![jQuery](https://img.shields.io/badge/jQuery-3.7-0769AD?style=flat-square&logo=jquery&logoColor=white)](https://jquery.com)
[![Apache](https://img.shields.io/badge/Server-XAMPP-D22128?style=flat-square&logo=apache&logoColor=white)](https://apachefriends.org)

</div>

## 📋 Table of Contents

- [Overview](#overview)
- [Roles & Access Control](#roles--access-control)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Database Structure](#database-structure)
- [Getting Started](#getting-started)
- [Default Login](#default-login)
- [Project Structure](#project-structure)
- [Security](#security)

---

## Overview

NAMIAS is a functional IT management system built with PHP and MySQL. Administrators log
in securely and manage everything from a single interface: asset inventory, filtered asset
and warranty reports, IP/VLAN allocation, a procurement wishlist, and user accounts.
Regular users can view all the data but cannot change it. The system demonstrates
full-stack web development using core web technologies, with role-based access control and
standard security practices (hashed passwords, prepared statements, CSRF protection).

---

## Roles & Access Control

There are two roles. Access is enforced **on the server**, not just by hiding buttons, so
a read-only user cannot bypass it by crafting requests.

| Capability | Admin | User |
|---|:---:|:---:|
| View dashboard, assets, reports, IP allocation, wishlist | ✅ | ✅ (read-only) |
| Add / edit / delete assets, IPs, wishlist items | ✅ | ❌ |
| Run, filter, and export reports | ✅ | ✅ |
| User Management (create users, reset passwords, change roles, delete) | ✅ | ❌ |

---

## Features

### 🔐 Authentication & Users
- Secure login with **hashed passwords** (`password_hash` / `password_verify`)
- Session-based access control   every page is protected; session ID regenerated on login
- **User Management (admin only):** create users, reset any user's password, change a
  user's role, and delete accounts (you cannot delete or demote your own account)
- Default admin account is created automatically on first run

### 📦 Asset Management
- Add, edit, and delete IT assets (laptops, monitors, peripherals, etc.)
- Track product name, category, quantity, department, physical location, and warranty dates
- Dynamic dropdowns for Category, Department, and Place   with inline add (no page reload)
- Inline edit via modal, delete with confirmation prompt
- Read-only users see the data without the editing controls

### 📊 Reports
- **Asset Report**   assets grouped by **Department, Category, or Place** (selectable),
  with per-group subtotals and a grand total quantity
- **Warranty Expiry Report**   assets sorted by warranty end date with colour-coded status
  (expired / under 30 days / under 90 days / healthy)
- **Filters** on both reports: Category, Department, Place, and a **date range** (added date
  for assets, expiry date for warranty)
- **Export to CSV** (opens cleanly in Excel) and **Print / Save as PDF**, with the current
  filters applied

### 🌐 IP Allocation
- Add and manage IP address allocations with CIDR notation
- Track VLAN ID, VLAN Name, purpose, and description per entry
- Duplicate IP and VLAN ID validation before saving
- Inline edit and delete with AJAX (no page reload)

### 🛒 Wishlist
- Submit procurement requests with product name, brand, quantity, and target department
- Status tracking: `Pending`, `Approved`, `Ordered`, `Rejected`
- Admin can update status and details via edit modal

### 🖥️ Dashboard
- Stat cards: total assets, IP allocations, pending wishlist items, user accounts
- Recent assets, recent IP allocations, and latest wishlist requests
- Warranty remaining countdown (skips already-expired items)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (procedural) |
| Database | MySQL |
| Frontend | HTML5, Bootstrap 5.3, custom theme |
| JavaScript | jQuery 3.7, Bootstrap JS |
| Server | Apache (XAMPP / WAMP recommended) |

---

## Database Structure

The system uses a single database (`namias_db`) with the following tables:

| Table | Description |
|---|---|
| `users` | Login credentials (hashed password) and role (`ADMIN` / `USER`) |
| `assets` | All IT asset records |
| `categories` | Asset category options |
| `departments` | Department options |
| `places` | Physical location options |
| `ip_allocations` | IP address and VLAN records |
| `wishlist` | Procurement request items |

> **Note:** the `users.password` column is `VARCHAR(255)` to hold bcrypt hashes. No
> plaintext admin is seeded in SQL   the app creates the default admin on first run.

---

## Getting Started

### Prerequisites
- XAMPP, WAMP, or any Apache + PHP + MySQL stack
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation

1. **Place the project in your server root**
   ```
   XAMPP: C:/xampp/htdocs/namias
   WAMP:  C:/wamp64/www/namias
   ```

2. **Import the database**
   - Open **phpMyAdmin**
   - Import `SQL.sql` (it creates the `namias_db` database and all tables)

3. **Configure the database connection**
   - Open `db.php` and update if your credentials differ:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $db   = "namias_db";
     ```

4. **Run the project**
   - Start Apache and MySQL from XAMPP/WAMP
   - Open: `http://localhost/namias/login.php`

---

## Default Login

On the first visit, if the `users` table is empty, the app automatically creates an
administrator:

```
Username: admin
Password: admin123
```

**Log in and change this password immediately** from the *User Management* page, then
create accounts for your team (Admin for full control, User for read-only).

---

## Project Structure

```
namias/
├── db.php                  # Database connection
├── auth.php                # Session, role guards, CSRF, escaping, first-run admin seed
├── layout.php              # Shared sidebar + theme (header/footer helpers)
├── login.php               # Login page (hashed-password verification)
├── logout.php              # Session destroy & redirect
├── dashboard.php           # Overview with stat cards
├── assets.php              # Asset management (admin CRUD / user read-only)
├── assets_report.php       # Reports: grouped asset report + warranty expiry, filters, CSV/print
├── ip_allocation.php       # IP & VLAN management (admin CRUD / user read-only)
├── wishlist.php            # Wishlist / procurement requests
├── users.php               # User management (admin only)
└── SQL.sql                 # Database schema
```

---

## Security

- **Hashed passwords** with `password_hash` / `password_verify` (bcrypt)
- **Prepared statements** on all database queries to prevent SQL injection
- **CSRF tokens** on every form and AJAX write
- **Output escaping** with `htmlspecialchars` to prevent XSS
- **Role checks enforced server-side** on every page and write action
- **Session ID regenerated** on login

---

## Author

Developed as an academic project.
© 2026 NAMIAS. All rights reserved.
