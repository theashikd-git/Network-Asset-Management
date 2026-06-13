# NAMIAS — Network Asset Management Information System

A PHP + MySQL application to track network assets, IP allocations, and a purchase
wishlist, with **role-based access control**.

## Roles

| Capability | Admin | User |
|---|---|---|
| View dashboard, assets, report, IP allocation, wishlist | ✅ | ✅ (read-only) |
| Add / edit / delete assets, IPs, wishlist items | ✅ | ❌ |
| User Management (create users, reset passwords, change roles, delete) | ✅ | ❌ |

A regular **User** can see all the data but cannot change anything. Only an
**Admin** can modify data and manage accounts. Access is enforced on the server
(not just by hiding buttons), so users cannot bypass it.

## Setup

1. Create the database and tables:
   ```sql
   SOURCE SQL.sql;
   ```
   (or import `SQL.sql` via phpMyAdmin).
2. Check the credentials in `db.php` (defaults: host `localhost`, user `root`,
   no password, database `namias_db`).
3. Put the folder in your web root (e.g. `htdocs/`) and open `login.php`.

## Default login

On the very first visit, if the `users` table is empty, the app automatically
creates an administrator:

```
username: admin
password: admin123
```

**Log in and change this password immediately** from the *User Management* page.

## Creating users & resetting passwords (Admin)

Go to **User Management** in the sidebar:
- **Create new user** — set username, password, and role (User or Admin).
- **Reset password** — set a new password for any account.
- **Role** — switch an account between User and Admin.
- **Delete** — remove an account (you cannot delete or demote your own account).

## What changed in this update

- **New role system** — Admin (full control) and User (read-only), enforced
  server-side on every page and write action.
- **New User Management page** (admin only) — create users, reset passwords,
  change roles, delete accounts.
- **Cleaner, unified UI** — a single consistent sidebar app shell, refined
  theme and typography, dashboard stat cards, and tidied tables/forms across
  every page.
- **Security hardening**
  - Passwords are now **hashed** with `password_hash` / `password_verify`
    (the password column was widened to `VARCHAR(255)`).
  - All database writes use **prepared statements** (the old code was open to
    SQL injection).
  - **CSRF tokens** on every form and AJAX write.
  - All output is escaped with `htmlspecialchars` to prevent XSS.
  - Session ID is regenerated on login.

## Files

| File | Purpose |
|---|---|
| `db.php` | Database connection |
| `auth.php` | Session, role guards, CSRF, escaping, first-run admin seed |
| `layout.php` | Shared sidebar + theme (header/footer helpers) |
| `login.php` / `logout.php` | Authentication |
| `dashboard.php` | Overview with stat cards |
| `assets.php` | Asset management (admin CRUD / user read-only) |
| `assets_report.php` | Asset quantity report |
| `ip_allocation.php` | IP allocation (admin CRUD / user read-only) |
| `wishlist.php` | Wishlist (admin CRUD / user read-only) |
| `users.php` | **User management (admin only)** |
| `SQL.sql` | Database schema |
