# Webshop Project

## Description

This is a webshop for selling shoes, allowing customers to browse, filter, and purchase shoes, and admins to manage products, orders, and customers.

## Technologies Used

- HTML
- CSS (with Bootstrap)
- JavaScript
- PHP
- MySQL

## Installation

### Hostinger Setup

1. Upload all project files to your Hostinger account via FTP or the file manager.

2. Create a MySQL database in Hostinger's control panel.

3. Import the `database.sql` file into your database using phpMyAdmin.

4. Copy the `.env.example` file to `.env` and edit it with your database credentials provided by Hostinger, such as:

   ```
   DB_HOST=your_database_host
   DB_USER=your_database_user
   DB_PASS=your_database_password
   DB_NAME=your_database_name
   ```

### Local Development

1. Set up a local server environment, such as XAMPP or WAMP.

2. Create a MySQL database.

3. Import the `database.sql` file into your local database.

4. Copy the `.env.example` file to `.env` and edit it with your local database credentials, for example:

   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=webshop
   ```

5. Start your local server and access the project at `http://localhost/webshop`.

## Usage

- Access the webshop at `http://yourdomain.com` (Hostinger) or `http://localhost/webshop` (local).
- To log in as an admin, go to `http://yourdomain.com/admin` and use the default credentials: username `admin`, password `admin123`. **Change these immediately after your first login.**

## Project Structure

- The main PHP files are in the root directory, such as `index.php`, `product.php`, etc.
- `css/`: Custom CSS files
- `js/`: JavaScript files
- `images/`: Product images
- `includes/`: PHP includes for database connection, functions, etc.
- `database.sql`: SQL dump for the database
- `.env`: Environment variables for database credentials

## Contributing

If you find any issues or have suggestions, please open an issue in the project repository.
