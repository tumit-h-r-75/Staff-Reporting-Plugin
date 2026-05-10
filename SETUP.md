# Staff Reporting Plugin - Setup & Installation Guide

## ⚙️ Prerequisites

Before deploying this plugin to production, ensure the following requirements are met:

### 1. **PHP Version**
- Minimum: PHP 8.0 or higher
- Recommended: PHP 8.1+

### 2. **WordPress Version**
- Minimum: WordPress 6.0
- Required plugins: None (built-in support for REST API)

### 3. **Database Permissions**
- WordPress database user must have permissions to:
  - CREATE TABLE
  - ALTER TABLE
  - INSERT, UPDATE, DELETE on plugin tables

---

## 📦 Installation Steps

### Step 1: Clone/Upload Plugin
```bash
# Copy plugin files to WordPress plugins directory
/wp-content/plugins/Staff\ Reporting\ Plugin/
```

### Step 2: Install Composer Dependencies
```bash
cd /wp-content/plugins/Staff\ Reporting\ Plugin/
composer install
```

This installs the **PhpSpreadsheet** library required for Excel export functionality.

### Step 3: Activate Plugin
1. Go to WordPress Admin Dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find **Bassmah Staff Reports**
4. Click **Activate**

The plugin will automatically:
- Create required database tables
- Create custom user roles (`bassmah_staff`, `bassmah_manager`)
- Initialize default options
- Flush rewrite rules

### Step 4: Install JWT Authentication Plugin (IMPORTANT)
The plugin relies on JWT tokens for secure API authentication. You MUST install the JWT Authentication plugin:

1. Go to **Plugins > Add New**
2. Search for: **JWT Authentication for WP REST API**
3. Install and Activate the plugin
4. Configure JWT settings if needed

**Without this plugin**, staff member form submissions will fail.

---

## 🔧 Configuration (Optional)

### User Roles
The plugin creates two custom roles:
- **Staff Member** (`bassmah_staff`) - Can submit reports
- **Report Manager** (`bassmah_manager`) - Can approve/reject reports

### Assigning Roles
1. Go to **Users > All Users**
2. Click on user
3. Scroll to **Role** section
4. Select appropriate role
5. Save changes

---

## 📋 Database Tables Created

The plugin automatically creates these tables on activation:

| Table | Purpose |
|-------|---------|
| `wp_staff_reports` | Stores daily work reports |
| `wp_staff_salary_settings` | Stores salary configuration per user |
| `wp_staff_working_days` | Stores holidays and working days |
| `wp_staff_salary_history` | Archives monthly salary calculations |

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Plugin shows as "Active" in Plugins list
- [ ] No PHP errors in debug log (`wp-content/debug.log`)
- [ ] Database tables created (check via phpMyAdmin or CLI)
- [ ] JWT Authentication plugin is active
- [ ] Staff member can access report form
- [ ] Managers can view the report dashboard
- [ ] Excel export works (downloads .xlsx file)

---

## 🆘 Troubleshooting

### Error: "PhpSpreadsheet library is not installed"
**Solution:** Run `composer install` in plugin directory

### Error: "Class not found: Bassmah_Staff_Reports_REST_Working_Days"
**Solution:** Ensure all plugin files are uploaded correctly

### Error: "Failed to open stream: class-bassmah-staff-reports-rest-api.php"
**Solution:** File was removed. Error should be resolved in latest version.

### JWT Token Issues
**Solution:** Verify JWT Authentication plugin is installed and activated

### Report Form Not Submitting
**Solution:** 
1. Check JWT Authentication plugin is active
2. Verify user has `bassmah_staff` role
3. Check browser console for API errors

---

## 📝 File Structure

```
Staff Reporting Plugin/
├── admin/                    # Admin dashboard
├── api/                     # REST API endpoints
├── includes/                # Core classes
├── public/                  # Public-facing functionality
├── views/                   # View templates
├── bassmah-staff-reports.php # Main plugin file
├── composer.json            # Composer dependencies
└── uninstall.php           # Cleanup on uninstall
```

---

## 🔐 Security Notes

- ✅ SQL injection protection via `$wpdb->prepare()`
- ✅ JWT token sanitization
- ✅ Deprecated folder protected via .htaccess
- ✅ Nonce verification on sensitive operations
- ✅ User capability checks on all endpoints

---

## 📞 Support

For issues or questions, contact the plugin author.
