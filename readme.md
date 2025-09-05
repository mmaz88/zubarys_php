# PHP Functional Mini-Framework

A lightweight, API-first web application framework built using a functional programming paradigm in PHP. This project serves as a lean, understandable, and multi-tenant capable foundation for modern web applications.

## About This Framework

This project is an exploration of building a modern web application without relying on a traditional MVC structure. The core is built with simple, reusable helper functions to promote clarity, performance, and ease of use. It's designed to be a solid starting point for developers who appreciate a minimalist and flexible architecture.

## Key Features

- **Functional Core**: A lean codebase built with simple, reusable helper functions for clarity and performance.
- **API-First Design**: Clean separation between the backend logic and the frontend, perfect for modern web apps.
- **Component-Based UI**: Quickly build consistent interfaces with a library of view helpers for cards, forms, and buttons.
- **Database Agnostic**: Powered by PDO with a simple query builder and Phinx for version-controlled migrations.
- **Multi-Tenant Ready**: Designed with a shared database tenancy model for scalable, isolated client environments.
- **Security Focused**: Includes built-in CSRF protection, password hashing, and a flexible middleware pipeline.

## Requirements

- PHP 8.1 or higher
- Composer
- A database server (MySQL, PostgreSQL, or SQLite)
- Node.js and npm (for frontend asset management)

## Installation

1.  **Clone the repository:**

    ```bash
    git clone <your-repository-url>
    cd php-functional-mini-framework
    ```

2.  **Install PHP dependencies:**

    ```bash
    composer install
    ```

3.  **Install Node.js dependencies:**

    ```bash
    npm install
    ```

4.  **Create your environment file:**
    Copy the example environment file and customize it for your local setup.

    ```bash
    cp .env.example .env
    ```

    Update the `.env` file with your database credentials and application settings.

5.  **Run database migrations and seed the database:**
    This will create the necessary tables and populate them with initial data.

    ```bash
    composer migrate
    composer seed
    ```

6.  **Compile frontend assets:**
    ```bash
    npm run build:css
    ```

## Usage

### Running the Development Server

You can use the built-in PHP server for local development:

```bash
composer serve
```
