# Padel Tournament Registration App

A modern, mobile-friendly web application for registering and managing Americano Padel tournaments, replacing tedious WhatsApp-based registration processes.

## Features

### For Players
- 🏓 **Simple Registration**: Clean interface to view and register for tournaments
- 📱 **Mobile-Friendly**: Optimized for mobile devices and traditional web hosting
- 🌍 **Multi-Country Support**: Flag display and country selection
- ⏳ **Waiting List**: Automatic waiting list management when tournaments are full
- ✅ **Registration Status**: Clear indication of registration status and ability to cancel

### For Administrators
- 🏆 **Tournament Management**: Create and manage tournaments with all details
- 📊 **Dashboard**: Overview of statistics and recent activity
- 👥 **User Management**: View and manage registered users
- 📝 **Registration Tracking**: Monitor tournament registrations and waiting lists

## Tournament Display

The app replicates the familiar WhatsApp tournament format:

```
🕊 Silver Americano Registration / Early birds session 🕊

📅 Friday, August 15th, 08.30-10.45
🎾 Level: Mixed Level
🏧 Fee: 250 THB - Court + Balls included
🔔 Host: Mads 🇩🇰

Players (12/12):
1/ Mads 🇩🇰
2/ Kasper 🇩🇰
...

Waiting list:
1/ Fanta 🇹🇭
...
```

## Technical Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Traditional web hosting compatible

### Installation

1. **Upload Files**: Upload all files to your web hosting directory
2. **Create Database**: Create a MySQL database and import `database/schema.sql`
3. **Configure**: Edit `includes/config.php` with your database credentials
4. **Set Permissions**: Ensure proper file permissions for your hosting environment

### Configuration

Edit `includes/config.php`:

```php
define('DB_HOST', 'your_database_host');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

### Default Admin Account

- **Email**: admin@padelapp.com
- **Password**: admin123

⚠️ **Important**: Change the default admin password after installation!

## File Structure

```
/
├── index.php                 # Main tournament listing
├── register.php             # User registration
├── login.php               # User login
├── tournament-action.php   # Tournament registration logic
├── admin/
│   ├── dashboard.php       # Admin dashboard
│   └── create-tournament.php # Create tournaments
├── includes/
│   ├── config.php         # Configuration
│   ├── database.php       # Database connection
│   ├── functions.php      # Core functions
│   ├── header.php         # HTML header
│   └── footer.php         # HTML footer
├── css/
│   └── style.css          # Responsive styles
└── database/
    └── schema.sql         # Database structure
```

## Future Enhancements

- 🏆 Tournament bracket generation and management
- 📊 Live scoring system during tournaments
- 📈 Player statistics and history
- 📧 Email/SMS notifications
- 💳 Payment integration
- 📱 Progressive Web App (PWA) support

## Support

This app is designed to be simple, reliable, and easy to host on traditional web hosting providers. Perfect for local padel communities and clubs.

## License

Open source - feel free to modify and use for your padel community!