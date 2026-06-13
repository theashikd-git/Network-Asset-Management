<div align="center">

<img src="https://img.shields.io/badge/Xpie-Network%20Asset%20Management-0d6efd?style=for-the-badge&logoColor=white" alt="Xpie Banner"/>

# 🖧 Xpie  Network Asset Management System

**A web-based administration system built for managing IT assets, network IP allocations,**  
**and procurement wishlists within an organisation.**

Designed to give IT administrators a centralised dashboard to track hardware,  
monitor warranty status, and manage network configurations.

<br/>

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![jQuery](https://img.shields.io/badge/jQuery-3.7-0769AD?style=flat-square&logo=jquery&logoColor=white)](https://jquery.com)
[![Apache](https://img.shields.io/badge/Server-XAMPP-D22128?style=flat-square&logo=apache&logoColor=white)](https://apachefriends.org)

</div>

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Database Structure](#database-structure)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)

---

## Overview

Xpie is a simple yet functional IT management system developed using PHP and MySQL. It allows administrators to log in securely and manage the following areas from a single interface: asset inventory, asset reporting, IP/VLAN allocation, and a wishlist for procurement requests. The system was built as part of an academic project to demonstrate full-stack web development skills using core web technologies.

---

## Features

### 🔐 Authentication
- Secure login with username and password
- Session-based access control — all pages are protected from unauthorized access
- Logout functionality that destroys the session completely

### 📦 Asset Management
- Add, edit, and delete IT assets (laptops, monitors, peripherals, etc.)
- Track product name, category, brand, quantity, department, and physical location
- Record warranty start and end dates with a note field
- Dynamic dropdowns for Category, Department, and Place — with inline add functionality (no page reload)
- Inline edit via modal, delete with confirmation prompt

### 📊 Asset Report
- Consolidated report showing quantity per product grouped by department and location
- Displays per-row quantity alongside the total quantity across all departments for that product
- Color-coded rows for easy reading

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
- Overview of recent assets added
- Quick view of IP allocations
- Warranty remaining countdown (skips already-expired items)
- Latest wishlist requests at a glance

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (procedural) |
| Database | MySQL |
| Frontend | HTML5, Bootstrap 5.3 |
| JavaScript | jQuery 3.7, Bootstrap JS |
| Server | Apache (XAMPP / WAMP recommended) |

---

## Database Structure

The system uses a single database (`xpie_dp`) with the following tables:

| Table | Description |
|---|---|
| `users` | Stores login credentials |
| `assets` | Stores all IT asset records |
| `categories` | Asset category options |
| `departments` | Department options |
| `places` | Physical location options |
| `ip_allocations` | IP address and VLAN records |
| `wishlist` | Procurement request items |

---

## Getting Started

### Prerequisites
- XAMPP, WAMP, or any Apache + PHP + MySQL stack
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Project Structure

```
xpie/
├── db.php                  # Database connection
├── login.php               # Login page
├── logout.php              # Session destroy & redirect
├── dashboard.php           # Admin dashboard overview
├── assets.php              # Asset management (CRUD)
├── assets_report.php       # Asset quantity report
├── ip_allocation.php       # IP & VLAN management (CRUD)
├── wishlist.php            # Wishlist/procurement requests
└── sql.sql                 # Database schema and seed data
```

---
### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/theashikd-git/xpie.git
   ```

2. **Move the project to your server root**
   ```
   XAMPP: C:/xampp/htdocs/xpie
   WAMP:  C:/wamp64/www/xpie
   ```

3. **Import the database**
   - Open **phpMyAdmin**
   - Create a new database named `xpie_dp`
   - Import the `sql.sql` file from the project root

4. **Configure the database connection**
   - Open `db.php`
   - Update the credentials if needed:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $db   = "xpie_dp";
     ```

5. **Run the project**
   - Start Apache and MySQL from XAMPP/WAMP
   - Open your browser and go to:
     ```
     http://localhost/xpie/login.php
     ```

6. **Default login credentials**
   ```
   Username: admin
   Password: admin123
   ```

---

## Author

Developed as an academic project.  
© 2026 Xpie. All rights reserved.
