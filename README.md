# Tournament Manager

A comprehensive web-based tournament management system built with PHP that allows users to organize, manage, and track various types of tournaments.

## Features

- User Authentication System
- Tournament Creation and Management
- Team Registration
- Bracket Generation and Management
- Leaderboard System
- Tournament Details Tracking
- Game Master Controls
- Category Management
- PDF Generation Support
- Email Notification System

## Requirements

- PHP 7.4 or higher
- MySQL Database
- Web Server (Apache/Nginx)
- Composer for dependency management

## Dependencies

- TCPDF (^6.8) - For PDF generation
- PHPMailer (^6.9) - For email notifications

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/Tournament-manager.git
```

2. Install dependencies using Composer:
```bash
composer install
```

3. Configure your database:
   - Create a new MySQL database
   - Import the provided SQL schema file
   - Update database credentials in the configuration file

4. Configure your web server:
   - Point your web server to the project's root directory
   - Ensure proper permissions are set

## Project Structure

```
Tournament-manager/
├── admin/           # Administrative controls
├── auth/            # Authentication system
├── config/          # Configuration files
├── css/            # Stylesheets
├── gamemaster/     # Game master controls
├── includes/       # PHP includes
├── js/             # JavaScript files
├── vendor/         # Composer dependencies
└── images/         # Image assets
```

## Usage

1. Access the application through your web browser
2. Create an account or log in
3. Create a new tournament
4. Manage teams and participants
5. Generate and manage brackets
6. Track tournament progress
7. View leaderboards and statistics

## Security

- User authentication and authorization
- Secure password hashing
- Input validation and sanitization
- Protection against SQL injection
- XSS prevention measures

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team. 