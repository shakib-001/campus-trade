# 📌 Campus Trade

A web-based marketplace where university students can buy, sell, and rent secondhand items — books, gadgets, cycles, furniture, and more — directly with each other on campus. No shipping, no middlemen: just post, chat, agree on a meetup, and trade.

> Built with **PHP, MySQL, HTML5, CSS3, JavaScript, Bootstrap 5**.

---

## ✨ Features

**Authentication**
- Secure registration & login (passwords hashed with `password_hash()`)
- Role-based access: Admin / Student
- CSRF-protected forms across the app
- Login rate-limiting (blocks repeated failed attempts for 15 minutes)
- Forgot/reset password flow with real email delivery (see Setup below)
- Hardened session cookies (HttpOnly, SameSite, Secure-on-HTTPS)

**Marketplace**
- Post, edit, and delete item listings (with image upload)
- Browse with category filter, keyword search, and pagination
- Item detail pages with seller profile & ratings

**Transactions**
- Send a buy/rent request with a proposed meetup location & time
- Seller can Accept / Reject requests
- Mark a transaction Completed → item automatically marked Sold
- Buyer and seller can rate & review each other after a completed transaction

**Community & Trust**
- Wishlist — save items to revisit later
- In-app chat between buyer and seller, tied to each item
- Report a listing → Admin can review and remove it

**Admin Panel**
- Manage users (block/unblock)
- Manage categories
- Review and act on reported listings
- Dashboard with quick stats

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3 (custom design system), JavaScript, Bootstrap 5.3 |
| Backend | PHP (Core PHP, PDO) |
| Database | MySQL |
| Fonts | Space Grotesk + Inter (Google Fonts) |

---

## 📂 Folder Structure

```
campus-trade/
├── admin/          # Admin panel (users, categories, reports)
├── auth/           # Register, login, logout
├── student/        # Browse, post, requests, chat, wishlist, reviews
├── includes/       # Shared header/footer
├── config/         # DB connection + CSRF helper
├── assets/         # CSS, JS, uploaded images
├── schema.sql      # Full database schema (import this first)
└── index.php       # Landing page
```

---

## 🚀 Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** + **MySQL**.
2. Copy this folder into `htdocs/campus-trade`.
3. Open `http://localhost/phpmyadmin`, create a database named **`campus_trade`**.
4. Go to the **Import** tab and import `schema.sql` — this creates all tables (including rate-limiting and password-reset support) and seeds default categories.
5. Visit `http://localhost/campus-trade/` in your browser.
6. Register an account. To make an account an admin, open the `users` table in phpMyAdmin and change that row's `role` from `student` to `admin`.
7. (Optional but recommended) Open `config/mailer_config.php` and fill in a Gmail address + App Password so Forgot Password actually emails a reset link instead of just showing it on screen. Instructions are in the file's comments.

### Default DB connection (`config/db.php`)
```
host: localhost
username: root
password: (empty — default for XAMPP)
dbname: campus_trade
```

---

## 🗄 Database Overview

| Table | Purpose |
|---|---|
| `users` | Accounts, roles, block status |
| `categories` | Item categories |
| `products` | Listings (sell/rent, condition, status) |
| `requests` | Buy/rent requests between buyer & seller |
| `messages` | Chat between buyer & seller, per item |
| `reviews` | Post-transaction ratings |
| `wishlist` | Saved items per user |
| `reports` | Flagged listings for admin review |
| `login_attempts` | Failed login tracking for rate-limiting |

---

## 🗺 Roadmap

This started as a university course project and is now being actively developed further as a real product for students to use. Planned next steps include an AJAX/JSON API layer, an MVC-style refactor, and further security hardening.

## 📄 License

Personal project, currently unlicensed for public redistribution. (Consider adding an [MIT License](https://choosealicense.com/licenses/mit/) here once you're ready to open it up.)
