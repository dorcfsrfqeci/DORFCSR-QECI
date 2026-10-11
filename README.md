# DORCFSRF QECI Clearance System

This repository contains the PHP/MySQL web application for the DORCFSRF QECI clearance workflow, including admin dashboard features, student clearance tracking, notifications, and audit tools.

## Project type

This is a PHP application intended to run in a local XAMPP / Apache + MySQL environment. It is not a static GitHub Pages site, so GitHub is used for source control and repository hosting rather than direct page hosting.

## Requirements

- PHP 8.x
- Apache or XAMPP
- MySQL / MariaDB
- Node.js (for frontend asset support)
- Git

## Local setup

1. Clone the repository.
2. Start Apache and MySQL in XAMPP.
3. Create the MySQL database and import the SQL scripts as needed.
4. Configure the database connection in `db_connect.php` if your local credentials differ.
5. Open the project in your browser through the local web root.

## GitHub deployment notes

GitHub can host the code repository, but this app requires a PHP runtime and MySQL database. For live deployment, publish this project to a PHP-capable host such as:

- Azure App Service
- cPanel / shared hosting
- DigitalOcean App Platform
- a VPS with Apache + MySQL

## Recommended GitHub remote

```bash
git remote add origin https://github.com/dorcfsrfqeci/DORFCSR-QECI.git
```

## Important configuration

- `db_connect.php` contains the local DB connection. Adjust credentials for your environment before deployment.
- `config/firebase-config.php` contains Firebase placeholders; replace them with real values for notifications or auth features.

## Notes

This application includes operational scripts for data migration, DB repair, and admin tasks. These scripts are useful for maintenance but should be reviewed before running in production.
