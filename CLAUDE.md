# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based Americano Padel Tournament Registration web application that replaces WhatsApp-based tournament signups. It's designed for traditional web hosting (shared hosting compatible) and provides a clean, mobile-friendly interface for tournament registration.

## Technology Stack

- **Backend**: PHP 7.4+ with MySQL
- **Frontend**: Vanilla HTML/CSS/JavaScript with responsive design
- **Database**: MySQL with PDO
- **Hosting**: Compatible with traditional shared hosting providers

## Development Setup

Since this is a traditional PHP application, development can be done with:
- Local server: XAMPP, WAMP, or MAMP
- Or any web server with PHP and MySQL support

### Database Setup
1. Create MySQL database
2. Import `database/schema.sql`
3. Configure `includes/config.php` with database credentials

### Default Admin Access
- Email: admin@padelapp.com
- Password: admin123

## Architecture Overview

### Core Structure
- **MVC Pattern**: Simplified MVC with functions.php containing business logic
- **Database Layer**: PDO-based database class with prepared statements
- **Authentication**: Session-based with secure password hashing
- **Security**: Input sanitization, CSRF protection considerations

### Key Files
- `includes/functions.php`: Core business logic and helper functions
- `includes/database.php`: Database connection and query methods
- `includes/config.php`: Configuration and constants
- `index.php`: Main tournament listing page
- `admin/`: Administrative functions

### Database Schema
- `users`: User accounts with country flags and admin roles
- `tournaments`: Tournament details matching WhatsApp format
- `registrations`: Player registrations with waitlist support
- `sessions`: Session management for security

## Key Features to Understand

### Tournament Registration Logic
- Automatic waitlist when tournaments are full
- Registration status tracking (registered/waitlist/cancelled)
- Real-time participant counting

### WhatsApp Format Replication
The UI closely matches the WhatsApp tournament format with:
- Emoji indicators (🕊, 📅, 🏆, etc.)
- Country flags for participants
- Participant numbering (1/, 2/, etc.)
- Clear waiting list display

### Mobile-First Design
- Responsive CSS Grid and Flexbox
- Touch-friendly buttons and forms
- Optimized for mobile tournament registration

## Security Considerations

- All user input is sanitized via `sanitizeInput()`
- Passwords are hashed using PHP's password_hash()
- Database queries use prepared statements
- Admin functions require authentication checks
- Session management with database tracking

## Common Development Tasks

### Adding New Tournament Fields
1. Update database schema in `schema.sql`
2. Modify tournament creation form in `admin/create-tournament.php`
3. Update display logic in `index.php`
4. Add validation in form processing

### Modifying Registration Logic
- Main logic in `functions.php`: `registerForTournament()`, `cancelRegistration()`
- Handle in `tournament-action.php`
- Update UI feedback as needed

### Styling Changes
- Primary styles in `css/style.css`
- CSS custom properties for theming
- Mobile-responsive design patterns

## Template Guidelines

- **ALWAYS use the simplified template structure** when creating or editing pages
- Reference `layout-template-simple.php` for the standard template structure
- Keep CSS minimal and organized (Navigation, Content, Mobile responsive)
- Avoid redundant styles and duplicate code
- Use consistent class naming conventions
- Maintain clean, readable HTML structure

## Mobile Responsiveness Requirements

- **EVERY page MUST be fully responsive** with proper mobile breakpoints
- **EVERY page MUST include the hamburger menu (≡) for mobile navigation**
- Include mobile menu overlay with proper navigation links
- Ensure all form elements, buttons, and interactive elements work on mobile
- Test share/copy functionality on mobile devices
- Stack elements vertically on small screens when appropriate
- Maintain consistent mobile experience across all pages

## Important Notes

- Built for traditional hosting (no Node.js/build tools required)
- Session-based authentication (not JWT)
- Direct MySQL connection (no ORM)
- Server-side rendered (no SPA framework)
- Country flag support via Unicode emojis