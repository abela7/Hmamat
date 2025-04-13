# HMAMAT - Holy Week Spiritual Tracker

HMAMAT is a web application designed to help users track their spiritual activities during Holy Week, reflect on their challenges, and stay motivated through visual progress, leaderboards, and daily messages.

## Features

### User Features
- Anonymous registration using baptismal name
- Daily spiritual activity tracking
- Track completed activities and reasons for not completing activities
- 7-day progress tracker
- Leaderboard to compare progress with other users
- Daily spiritual messages for inspiration

### Admin Features
- Manage spiritual activities
- Define reasons for missing activities
- Create daily messages for different days of the week
- View user activity logs and statistics
- Monitor overall engagement and progress

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP, MySQL
- **Dependencies**: jQuery, Font Awesome

## Installation

1. **Database Setup**:
   - Import the `Database.sql` file into your MySQL database
   - This will create all the necessary tables and initial data

2. **Configuration**:
   - Edit the database connection details in `includes/db.php`
   - Update the configuration settings in `includes/config.php` if needed

3. **Server Requirements**:
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Apache or Nginx web server

4. **Deployment**:
   - Upload all files to your web server
   - Ensure the web server has write permissions to necessary directories

## Usage

### User Access
- Users can register and login at `/user/login.php`
- After login, users can:
  - View and track daily spiritual activities
  - Mark activities as completed or not completed
  - Provide reasons for not completing activities
  - View their progress over the past 7 days
  - Check the leaderboard to see how they compare with others

### Admin Access
- Administrators can access the admin panel at `/admin/login.php`
- Default admin credentials:
  - Username: Amhaslassie
  - Password: (check your database or create a new admin user)
- Admin features include:
  - Dashboard with statistics
  - Managing spiritual activities
  - Creating and editing reasons for missing activities
  - Setting up daily spiritual messages
  - Viewing user information and activity logs

## File Structure

```
/hmamat/
│
├── /admin/                   # Admin section
│   ├── index.php             # Admin dashboard
│   ├── login.php             # Admin login
│   ├── logout.php            # Admin logout
│   ├── manage_activities.php # Activity management
│   ├── manage_reasons.php    # Miss reasons management
│   ├── manage_messages.php   # Daily messages management
│   ├── view_users.php        # User management
│   ├── /css/                 # Admin CSS files
│   └── /js/                  # Admin JavaScript files
│
├── /user/                    # User section
│   ├── dashboard.php         # User dashboard
│   ├── login.php             # User login
│   ├── logout.php            # User logout
│   ├── register.php          # User registration
│   ├── leaderboard.php       # Leaderboard
│   ├── submit_activity.php   # Activity submission handler
│   ├── /css/                 # User CSS files
│   └── /js/                  # User JavaScript files
│
├── /includes/                # Shared files
│   ├── config.php            # Application configuration
│   ├── db.php                # Database connection
│   └── auth_check.php        # Authentication functions
│
├── index.php                 # Main entry point
├── Database.sql              # Database structure and initial data
└── README.md                 # Documentation
```

## License

This project is proprietary and confidential, created for the specific purpose of tracking spiritual activities during Holy Week.

## Support

For support, please contact the administrator or developer of the application. 