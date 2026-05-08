# Deprecated Files Archive

This folder contains deprecated and debug files that were used during development and troubleshooting. These files are not part of the active plugin and can be safely deleted.

## Contents:

### Debug/Setup Scripts (Root level)
- **activate.php, force-activate.php, force-database-activation.php** - Manual activation helpers (not needed - use WordPress admin)
- **check-database-status.php, database-diagnostic.php** - Database diagnostic tools
- **check-pages.php** - Page checking utility
- **database-setup-sql.php** - Manual SQL setup helper
- **debug-submission.php** - Report submission debugging
- **fix-*.php** - Various attempt at fixing specific issues
- **final-*.php** - Final fixes and CSV export attempts
- **setup-jwt-auth.php** - JWT authentication setup helper
- **test-functionality.php** - Functionality testing script
- **cleanup-unused-files.php** - Cleanup utility
- **create-tables-now.php** - Manual table creation
- **init-database-complete.php** - Database initialization
- **restore-original-submission.php** - Data restoration helper

### Duplicate API Files
- **class-rest-reports-v2.php** - Older version of reports API
- **class-rest-reports-fixed.php** - Previous fix attempt of reports API
- **class-rest-salary-v2.php** - Older version of salary API

## Notes:
- The active plugin uses the main versions in the `api/` and `includes/` directories
- These files represent development iterations and are no longer needed
- The plugin is now properly structured and functional without these debug files

## Recommendation:
These files can be safely deleted or archived in version control if needed.
