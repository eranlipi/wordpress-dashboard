Updates System
A comprehensive WordPress theme for managing medical updates and communications, designed for healthcare organizations to keep their representatives informed with the latest updates and announcements.
Overview
Med-Master Updates System is a custom WordPress theme that creates a centralized platform for medical professionals to share and track updates. The system features user management, update categorization with custom tags, read status tracking, and an intuitive dashboard interface with Bootstrap styling.
Features
Core Functionality

Custom Updates Post Type: Centralized content management for medical updates
User Role Management: Administrative and representative user roles with distinct permissions
Read Status Tracking: Monitor which updates have been read by which users
Custom Taxonomies: Categorize updates with color-coded tags
Scheduled Publishing: Support for future-dated posts with automatic publishing

User Experience

Responsive Dashboard: Bootstrap 5-powered interface with RTL support
AJAX-Powered Interactions: Seamless user experience without page reloads
Read More Functionality: Expandable content for longer updates
Auto-Read Detection: Intelligent marking of updates as read based on scroll behavior
Modal Management: Comprehensive modal system for all admin tasks

Administrative Features

User Management: Add, edit, delete users and reset passwords
Update Management: Create, edit, delete updates with rich text editor
Tag Management: Create custom update tags with color coding
Read Status Analytics: Track engagement across all representatives
Email Notifications: Automatic email notifications for new users and password resets

System Requirements

WordPress: 5.0 or higher
PHP: 7.4 or higher
MySQL: 5.6 or higher
Browser Support: Modern browsers with JavaScript enabled

Installation

Upload Theme Files
bash# Upload the theme folder to your WordPress installation
/wp-content/themes/medmaster/

Activate Theme

Go to WordPress Admin → Appearance → Themes
Find "Med-Master Updates System" and click "Activate"


Initial Setup

The system will automatically create required database tables
A dashboard page will be created and set as the homepage
The representative user role will be registered


Create Admin User (if needed)

Ensure you have at least one administrator user to manage the system



Theme Structure
medmaster/
├── css/
│   └── medmaster-style.css          # Custom styles with Bootstrap integration
├── js/
│   └── medmaster-scripts.js         # Core JavaScript functionality
├── page-templates/
│   ├── page-dashboard.php           # Main dashboard interface
│   ├── page-add-user.php            # User creation form
│   ├── page-add-update.php          # Update creation form
│   ├── page-manage-users.php        # User management interface
│   └── login-page.php               # Custom login page
├── functions.php                    # Theme functions and AJAX handlers
├── header-minimal.php               # Minimal header for login pages
├── footer-minimal.php               # Minimal footer for login pages
└── style.css                        # Main stylesheet (required by WordPress)
User Roles
Administrator

Full system access
User management capabilities
Create and manage updates
View read status analytics
Manage update tags and categories

Representative

View published updates
Mark updates as read
Restricted admin access
Auto-redirect to dashboard on login

Configuration
Database Tables
The theme creates one custom table:

wp_update_read_status: Tracks which users have read which updates

Custom Post Types

updates: For managing medical updates with custom fields and taxonomies

Custom Taxonomies

update_tag: For categorizing updates with color-coded tags

Usage
For Administrators

Adding Updates

Use the modal interface or dedicated page template
Set publish dates for immediate or scheduled posting
Assign tags for better organization


Managing Users

Add new representatives with automatic email notifications
Reset passwords with email notifications
Monitor user engagement through read status


Analytics

View read status for each update
Track engagement across all representatives
Export data via admin interface



For Representatives

Viewing Updates

Access the dashboard to see all published updates
Use read more/less functionality for longer content
Updates are marked as read automatically when scrolled


Read Status

Manual read confirmation via checkbox
Automatic detection based on scroll behavior
Visual indicators for read/unread status
