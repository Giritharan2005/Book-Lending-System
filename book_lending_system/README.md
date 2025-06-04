# Book Lending System

A web-based book lending management system that allows users to borrow and return books while providing administrative controls for managing books and users.

## Features

### User Features
- Browse available books
- Borrow books
- View borrowing history
- Return books
- Generate reports
- Manage personal information
- Reset password

### Admin Features
- Manage books (Add/Delete)
- Manage users (Add/Delete)
- View borrowed books
- View borrowing history
- Generate reports
- Dashboard overview

## Technical Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache Web Server
- XAMPP (Recommended)

## Installation

1. Clone the repository to your web server directory (e.g., `htdocs`)
2. Import the database schema from `database.sql`
3. Configure database connection in `db.php`
4. Create an uploads directory with proper permissions
5. Access the system through your web browser

## Directory Structure

```
book_lending_system/
├── admin/                 # Admin panel files
├── uploads/              # Directory for uploaded files
├── add_book.php          # Add new books
├── add_user.php          # Add new users
├── admin_dashboard.php   # Admin dashboard
├── borrow_book.php       # Borrow book functionality
├── database.sql          # Database schema
├── db.php               # Database connection file
├── index.php            # Main login page
├── style.css            # CSS styles
└── ... (other files)
```

## Usage

1. Access the system through your web browser
2. Login as admin or user
3. Use the navigation menu to access different features
4. Admin users have additional controls for managing the system

## Security

- Password protection for all users
- Separate admin and user interfaces
- Secure file upload handling
- SQL injection prevention
- XSS protection

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact the project maintainer or create an issue in the repository.
