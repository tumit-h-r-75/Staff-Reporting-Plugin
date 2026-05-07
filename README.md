# Bassmah Staff Reports Plugin

A comprehensive WordPress plugin for staff daily work reporting system with salary deduction integration for Bassmah.

## Features

### For Staff Members
- **Daily Report Submission**: Submit detailed daily work reports with multiple tasks
- **Duplicate Prevention**: Cannot submit more than one report per day
- **Salary Dashboard**: View salary deductions based on missing reports
- **Report History**: Access and view all past reports
- **Auto-save**: Draft reports are automatically saved

### For Managers
- **Report Monitoring**: View all staff reports with filtering options
- **Comments**: Add comments to staff reports
- **Export Functionality**: Export reports to CSV/Excel format
- **Salary Management**: Configure salary settings for staff
- **Working Days Management**: Set working days and holidays
- **Staff Management**: Add/remove staff and manage roles

### Security Features
- Role-based access control
- WordPress nonce protection
- Input sanitization and validation
- SQL injection prevention
- XSS protection

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher
- SSL certificate (HTTPS)

## Installation

1. **Download the Plugin**
   ```bash
   # Clone or download the plugin folder
   git clone <repository-url>
   # Or download the ZIP file and extract
   ```

2. **Upload to WordPress**
   - Copy the `bassmah-staff-reports` folder to `wp-content/plugins/`
   - Or upload via WordPress Admin: Plugins → Add New → Upload Plugin

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Bassmah Staff Reports" and click "Activate"

4. **Configure User Roles**
   - Go to WordPress Admin → Users
   - Assign users to appropriate roles:
     - `bassmah_staff` for staff members
     - `bassmah_manager` for managers
   - Or use existing WordPress roles (Editor → Manager, Subscriber → Staff)

5. **Create Frontend Pages**
   - Create a new page titled "Daily Report" and add shortcode:
     ```
     [bassmah_report_form]
     ```
   - Create a new page titled "My Dashboard" and add shortcode:
     ```
     [bassmah_staff_dashboard]
     ```
   - Create a new page titled "My Reports" and add shortcode:
     ```
     [bassmah_my_reports]
     ```

6. **Configure Salary Settings**
   - Go to WordPress Admin → Bassmah Reports → Salary Settings
   - Set monthly salary for each staff member
   - Configure working days per month (default: 22)

## Configuration

### Plugin Settings
Access via WordPress Admin → Bassmah Reports → Settings:

- **Task Categories**: Customize available task categories
- **Task Statuses**: Define completion status options
- **Email Notifications**: Configure notification settings
- **Default Currency**: Set default currency (default: CAD)
- **Working Days**: Set default working days per month

### Salary Configuration
1. Go to Bassmah Reports → Salary Settings
2. Click "Add Salary Settings" for a staff member
3. Enter:
   - Monthly Salary
   - Working Days per Month
   - Currency
   - Effective Date

### Working Days Management
1. Go to Bassmah Reports → Working Days
2. Add working days or mark holidays
3. Holidays will be excluded from salary calculations

## Shortcodes

### `[bassmah_report_form]`
Displays the daily report submission form for staff members.

**Usage:**
```html
[bassmah_report_form]
```

### `[bassmah_staff_dashboard]`
Displays the staff dashboard with salary information and recent reports.

**Usage:**
```html
[bassmah_staff_dashboard]
```

### `[bassmah_my_reports]`
Displays a list of the user's submitted reports.

**Usage:**
```html
[bassmah_my_reports]
```

## User Roles and Permissions

### bassmah_staff (Staff Member)
- Submit daily reports
- View own reports
- View own salary information

### bassmah_manager (Manager)
- Submit reports (own)
- View all reports
- Add comments to reports
- Export reports
- View all salary information
- Manage salary settings
- Manage working days
- Manage staff roles

### administrator
- All capabilities of Manager plus plugin settings

## Salary Calculation

The plugin calculates salary deductions based on:

```
Daily Rate = Monthly Salary ÷ Working Days Per Month
Missing Days = Total Working Days - Days with Submitted Reports
Total Deduction = Missing Days × Daily Rate
Net Salary = Monthly Salary - Total Deduction
```

### Example:
- Monthly Salary: CAD 3,500
- Working Days: 22
- Daily Rate: CAD 159.09
- Reports submitted: 18 days
- Missing: 4 days
- Deduction: CAD 636.36
- Net Salary: CAD 2,863.64

## REST API Endpoints

The plugin provides REST API endpoints at `/wp-json/bassmah/v1/`:

### Reports
- `POST /reports` - Submit new report
- `GET /reports` - Get all reports (Manager only)
- `GET /reports/mine` - Get current user's reports
- `GET /reports/{id}` - Get specific report
- `PUT /reports/{id}/comment` - Add comment to report
- `GET /reports/today` - Get today's report
- `GET /reports/statistics` - Get report statistics

### Salary
- `GET /salary/me` - Get current user's salary info
- `GET /salary/{user_id}` - Get user's salary info (Manager only)
- `PUT /salary/{user_id}` - Update salary settings (Manager only)
- `GET /salary/{user_id}/history` - Get salary history
- `GET /salary/dashboard/{user_id}` - Get dashboard statistics
- `GET /salary/batch` - Get batch salary summary
- `POST /salary/export` - Export salary data

## Database Tables

The plugin creates three custom tables:

### `wp_staff_reports`
Stores daily work reports with tasks and comments.

### `wp_staff_salary_settings`
Stores salary configuration for each staff member.

### `wp_staff_working_days`
Stores working days and holidays configuration.

## Customization

### Adding Custom Fields
To add custom fields to the report form:

1. Modify the form template in `public/views/report-form.php`
2. Update the JavaScript in `public/js/public-scripts.js`
3. Modify the report class in `includes/class-report.php`
4. Update the REST API in `api/class-rest-reports.php`

### Custom Styling
Override the default styles by adding CSS to your theme:

```css
/* Override plugin styles */
.bassmah-report-form {
    /* Your custom styles */
}
```

### Custom Notifications
Implement custom email notifications by hooking into the plugin actions:

```php
add_action('bassmah_report_submitted', 'my_custom_notification', 10, 2);
function my_custom_notification($report_id, $report) {
    // Custom notification logic
}
```

## Troubleshooting

### Common Issues

1. **Reports not submitting**
   - Check user permissions
   - Verify database tables exist
   - Check for JavaScript errors

2. **Salary calculations incorrect**
   - Verify salary settings are configured
   - Check working days configuration
   - Ensure reports are marked as submitted

3. **Email notifications not working**
   - Check WordPress email configuration
   - Verify notification settings are enabled
   - Check spam folder

### Debug Mode
Enable WordPress debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Support
For support and feature requests:
- Email: tumit@bassmah.ca
- Website: https://bassmah.ca

## Changelog

### Version 1.0.0
- Initial release
- Core reporting functionality
- Salary calculation system
- Admin dashboard
- REST API endpoints
- Frontend shortcodes

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Author**: Tumit
- **Company**: Bassmah
- **Website**: https://bassmah.ca

---

*This plugin was specifically developed for Bassmah's staff reporting needs with salary integration.*
