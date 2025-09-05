# PHP Starter Kit

A modern, feature-rich starter kit for building powerful PHP applications. Designed with a functional, API-first approach, this kit provides a lightweight yet robust foundation that is multi-tenant capable, secure, and easy to extend.

## About This Starter Kit

This project accelerates the development of modern web applications by providing a comprehensive set of pre-built features and a clean, function-based architecture. It deliberately avoids the complexity of traditional MVC frameworks, opting instead for simple, reusable helper functions that enhance clarity, performance, and developer productivity.

Whether you're building a SaaS platform, an internal tool, or a complex API, this starter kit gives you the secure and scalable foundation you need to focus on what matters most: your application's features.

---

## Core Features

This starter kit is packed with features to handle the most common application requirements out of the box.

### Backend & Architecture

- **Functional Core**: A lean codebase built with simple, reusable helper functions instead of complex classes.
- **API-First Design**: Clean separation between the backend logic and the frontend, making it perfect for SPAs or mobile clients.
- **Multi-Tenancy Ready**: Designed with a shared database, schema-based tenancy model for scalable and isolated client environments.
- **Role-Based Access Control (RBAC)**: Manage user permissions with a flexible system of roles and permissions that can be assigned on a global or per-tenant basis.
- **Database Agnostic Query Builder**: A powerful, fluent query builder built on PDO that supports MySQL, PostgreSQL, and SQLite.
- **Database Migrations & Seeding**: Uses **Phinx** for robust, version-controlled schema management and easy database seeding.
- **Secure Middleware Pipeline**: Protect routes with authentication, CORS, and permission-based middleware.
- **Service Scaffolding**: Includes a fully working **PHPMailer** integration and placeholder code for services like **Twilio SMS** and **WhatsApp**, saving you setup time.

### UI & Frontend

- **Advanced DataTables.net Integration**: Server-side powered tables with a suite of PHP helpers for custom renderers (status badges, user profiles), sorting, filtering, and one-click exporting to **Excel** and **PDF**.
- **Component-Based UI Helpers**: A rich library of PHP functions to rapidly build consistent and themeable interfaces for **Cards**, **Modals**, **Forms**, **Buttons**, and **Alerts**.
- **Secure Form Builder**: A full suite of helpers for creating secure forms with inputs, textareas, selects, and toggle switches, all with automatic CSRF protection.
- **Themeable Design System**: A modern UI built with **SASS** and CSS variables. It includes a **dark mode** and multiple color themes that can be switched on the fly.
- **Responsive Application Layout**: A clean application shell featuring a collapsible sidebar, dynamic page titles, and a separate layout for public-facing pages.

---

## Requirements

- PHP 8.1+
- Composer
- Node.js & npm
- A database server (MySQL/MariaDB, PostgreSQL, or SQLite)

---

## Installation Guide

Follow these steps to get your development environment up and running.

### 1. Clone the Repository

First, clone the project to your local machine.

```bash
git clone https://github.com/mmaz88/zubarys_php.git
```

Navigate into the new project directory.

```bash
cd php-starter-kit
```

### 2. Install Dependencies

Install the required PHP and Node.js packages.

**PHP Dependencies (via Composer):**

```bash
composer install
```

**Node.js Dependencies (via npm):**

```bash
npm install
```

### 3. Configure Your Database and Environment

Copy the example `.env` file to create your local configuration.

```bash
cp .env.example .env
```

Next, **choose one of the database options below** and follow its instructions to set up your database and configure your `.env` file.

---

## Database Setup (Choose One)

### Option 1: MySQL / MariaDB (Recommended for XAMPP)

This is the recommended setup for a development environment that closely mirrors a production server.

**A. Create the Database:**

1.  Ensure the Apache and MySQL services are running in your XAMPP Control Panel.
2.  Navigate to `http://localhost/phpmyadmin` in your browser.
3.  Click on the **"New"** button in the left sidebar.
4.  Enter a database name (e.g., `php_starter_kit`) and set the collation to `utf8mb4_unicode_ci`.
5.  Click **"Create"**.

**B. Configure `.env` File:**
Open your `.env` file and set the following variables. A standard XAMPP setup uses `root` with no password.

```env
# --- General Settings ---
APP_URL=http://localhost/php-starter-kit
APP_DEBUG=true

# --- Database Settings for MySQL/MariaDB ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=php_starter_kit
DB_USERNAME=root
DB_PASSWORD=
```

> **Important Note on MySQL `GROUP BY` Mode:**
> This starter kit is configured to use the `ONLY_FULL_GROUP_BY` SQL mode. This is a modern standard that prevents ambiguous `GROUP BY` queries. The connection wrapper in `core/db/DatabaseWrapper.php` automatically sets this mode for every connection, ensuring consistent behavior and preventing common errors.

### Option 2: PostgreSQL

For developers who prefer PostgreSQL.

**A. Create the Database:**

1.  Ensure your PostgreSQL server is running.
2.  Open a terminal or SQL client and run the following command to create a new database:
    ```sql
    CREATE DATABASE php_starter_kit;
    ```
    _(You may also need to create a dedicated user and grant privileges.)_

**B. Configure `.env` File:**
Open your `.env` file and update the database section to match your PostgreSQL credentials.

```env
# --- General Settings ---
APP_URL=http://localhost:8000
APP_DEBUG=true

# --- Database Settings for PostgreSQL ---
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=php_starter_kit
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password
```

### Option 3: SQLite (Easiest for Quick Setup)

SQLite is a serverless database that stores everything in a single file, making it incredibly easy for a quick start.

**A. Create the Database:**
No action needed! The database file will be created automatically in the `storage/` directory when you run the migrations.

**B. Configure `.env` File:**
Open your `.env` file and set the following variables. Note that host, port, username, and password are not needed and should be commented out.

````env
# --- General Settings ---
APP_URL=http://localhost:8000
APP_DEBUG=true

# --- Database Settings for SQLite ---
DB_CONNECTION=sqlite
DB_DATABASE=storage/database.sqlite

# The following are not used for SQLite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_USERNAME=root
# DB_PASSWORD=```

---

## Final Installation Steps

After configuring your database, continue with these final steps.

### 4. Run Database Migrations

This command will create all the necessary tables in your chosen database.

```bash
composer migrate```

### 5. Seed the Database

This command will populate the database with default data, including the Super Admin and Tenant Admin accounts.

```bash
composer seed
````

### 6. Compile Frontend Assets

Compile the SASS files into a single CSS stylesheet.

```bash
npm run build:css
```

**Setup is complete!** You can now access your application.

---

## Usage

### Running the Built-in Dev Server

For quick development (especially with SQLite or PostgreSQL), you can use PHP's built-in server.

```bash
composer serve
```

Your application will be available at `http://localhost:8000`. If you are using XAMPP, access it via `http://localhost/php-starter-kit/`.

### Default Login Credentials

The database seeder creates two default user accounts for you to get started:

- **Super Admin**: Manages the entire application.
  - **Email**: `superadmin@dev.com`
- **Tenant Admin**: Manages the "Acme Corporation" tenant.
  - **Email**: `admin@acme.com`

> **Password** for both accounts is: `123456789`

---

## Service Integrations

This starter kit includes foundational code and configuration for popular third-party services.

### Email (PHPMailer) - **Ready to Use**

- **Status:** This integration is fully functional.
- **Setup:** Configure your SMTP settings in the `.env` file (e.g., `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`).
- **Usage:** The `send_mail()` helper function in `core/services/mailer.php` is ready to be called from your application logic.

### SMS & WhatsApp (Twilio) - **Integration Pending**

- **Status:** The helper functions (`send_sms()`, `send_whatsapp()`) and `.env` configuration options are present as a scaffold. The core logic for making API calls to Twilio is a placeholder and **needs to be implemented**.
- **Setup:** Add your Twilio Account SID, Auth Token, and phone numbers to the `.env` file (`TWILIO_*` variables).
- **Next Steps:** You will need to edit the functions in `core/services/sms.php` and `core/services/whatsapp.php` to include the actual API request logic using Guzzle HTTP or the official Twilio PHP SDK.

---

## Helpful Tips for Users

- **Live CSS Reloading**: During development, run `npm run watch:css` in a separate terminal. This will automatically recompile your CSS file whenever you make a change in the `src/scss/` directory.
- **Understanding the Code**: Since this is a functional project, most of the core logic resides in the `app/helpers/` and `app/api/` directories. Look for functions, not classes!
- **Creating New Database Tables**: To add a new table or modify an existing one, create a new migration file using Phinx:
  ```bash
  composer migrate:create MyNewTable
  ```
  Edit the generated file in `database/migrations/` and then run `composer migrate`.
- **Customizing the UI**: The entire look and feel can be easily modified. The main theme colors are defined as CSS variables in `src/scss/core/_variables.scss`. Change them there to update the UI globally.
- **Enabling Debug Mode**: To see detailed error messages during development, make sure to set `APP_DEBUG=true` in your `.env` file.
