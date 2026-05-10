# Bassmah Staff Reports - Enterprise Architecture

## System Overview

Bassmah Staff Reports is a comprehensive SaaS-style WordPress plugin for staff daily work reporting with automated salary deduction tracking. Built with enterprise-grade architecture for scalability, security, and maintainability.

## Architecture Principles

1. **Separation of Concerns**: Clear separation between data, business logic, and presentation layers
2. **Dependency Injection**: Service container for loose coupling
3. **Event-Driven**: Hook-based architecture for extensibility
4. **RESTful API**: Complete REST API for frontend and external integrations
5. **Security First**: Multi-layered security with capability-based access control
6. **Scalability**: Designed for multi-tenant expansion
7. **Testability**: Dependency injection and mocking support

## Core Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
├─────────────────────────────────────────────────────────────┤
│  Admin Interface (React/Gutenberg)                     │
│  Public Interface (Modern JS/HTML)                    │
│  REST API Endpoints                                   │
├─────────────────────────────────────────────────────────────┤
│                    Business Layer                       │
├─────────────────────────────────────────────────────────────┤
│  Report Service                                       │
│  Salary Service                                       │
│  User Service                                        │
│  Notification Service                                   │
│  Export Service                                       │
├─────────────────────────────────────────────────────────────┤
│                     Data Layer                        │
├─────────────────────────────────────────────────────────────┤
│  Database Abstraction (wpdb)                           │
│  Cache Layer (WordPress Object Cache)                   │
│  File System                                          │
└─────────────────────────────────────────────────────────────┘
```

## Database Schema Design

### wp_staff_reports
```sql
CREATE TABLE wp_staff_reports (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    submission_time DATETIME NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'submitted',
    tasks_json LONGTEXT NOT NULL,
    manager_comment TEXT NULL,
    manager_id BIGINT(20) UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_date (user_id, report_date),
    KEY idx_user_id (user_id),
    KEY idx_report_date (report_date),
    KEY idx_status (status),
    KEY idx_manager_id (manager_id),
    FOREIGN KEY (user_id) REFERENCES wp_users(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES wp_users(id) ON DELETE SET NULL
);
```

### wp_staff_salary_settings
```sql
CREATE TABLE wp_staff_salary_settings (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    working_days_per_month INT(11) NOT NULL DEFAULT 22,
    daily_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'CAD',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_by BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_effective (user_id, effective_from),
    KEY idx_user_id (user_id),
    KEY idx_effective_range (effective_from, effective_to),
    FOREIGN KEY (user_id) REFERENCES wp_users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES wp_users(id) ON DELETE SET NULL
);
```

### wp_staff_working_days
```sql
CREATE TABLE wp_staff_working_days (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    work_date DATE NOT NULL,
    is_holiday TINYINT(1) NOT NULL DEFAULT 0,
    holiday_name VARCHAR(100) NULL,
    holiday_type ENUM('public', 'company', 'custom') DEFAULT 'public',
    created_by BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_work_date (work_date),
    KEY idx_is_holiday (is_holiday),
    KEY idx_holiday_type (holiday_type),
    FOREIGN KEY (created_by) REFERENCES wp_users(id) ON DELETE SET NULL
);
```

### wp_staff_salary_audit
```sql
CREATE TABLE wp_staff_salary_audit (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- YYYY-MM format
    monthly_salary DECIMAL(10,2) NOT NULL,
    daily_rate DECIMAL(10,2) NOT NULL,
    total_working_days INT(11) NOT NULL,
    submitted_days INT(11) NOT NULL,
    missing_days INT(11) NOT NULL,
    total_deduction DECIMAL(10,2) NOT NULL,
    net_salary DECIMAL(10,2) NOT NULL,
    calculation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    calculated_by BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_month (user_id, month_year),
    KEY idx_user_id (user_id),
    KEY idx_calculation_date (calculation_date),
    FOREIGN KEY (user_id) REFERENCES wp_users(id) ON DELETE CASCADE,
    FOREIGN KEY (calculated_by) REFERENCES wp_users(id) ON DELETE SET NULL
);
```

## Service Architecture

### Core Services

1. **ReportService**: Handle all report operations
2. **SalaryService**: Salary calculations and management
3. **UserService**: User management and role operations
4. **NotificationService**: Email and system notifications
5. **ExportService**: Data export functionality
6. **WorkingDaysService**: Working days and holidays management
7. **AuditService**: Audit trail and logging

### Supporting Classes

1. **DatabaseManager**: Database operations abstraction
2. **CacheManager**: Caching layer
3. **SecurityManager**: Security operations
4. **ValidationManager**: Input validation
5. **FileManager**: File operations
6. **ConfigManager**: Plugin configuration

## REST API Design

### Authentication & Authorization
- WordPress nonce for web requests
- Application passwords for API access
- JWT tokens for external integrations
- Rate limiting per user/IP

### Response Format
```json
{
    "success": true,
    "data": {},
    "message": "Operation completed successfully",
    "meta": {
        "pagination": {},
        "timestamp": "2025-01-01T12:00:00Z"
    }
}
```

### Error Handling
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": {}
    },
    "meta": {
        "timestamp": "2025-01-01T12:00:00Z"
    }
}
```

## Security Architecture

### Multi-Layer Security
1. **Authentication**: Verify user identity
2. **Authorization**: Check capabilities
3. **Validation**: Sanitize and validate inputs
4. **CSRF Protection**: Nonce verification
5. **SQL Injection Prevention**: Prepared statements
6. **XSS Prevention**: Output escaping
7. **Rate Limiting**: Prevent abuse
8. **Audit Logging**: Track all operations

### Capability System
```php
'bassmah_submit_reports'      => Staff can submit reports
'bassmah_view_own_reports'    => Staff can view own reports
'bassmah_view_all_reports'    => Manager can view all reports
'bassmah_comment_reports'      => Manager can comment on reports
'bassmah_export_reports'       => Manager can export reports
'bassmah_view_own_salary'      => Staff can view own salary
'bassmah_view_all_salary'      => Manager can view all salary
'bassmah_manage_salary_settings' => Manager can manage salary
'bassmah_manage_working_days'  => Manager can manage working days
'bassmah_manage_staff'         => Manager can manage staff
```

## Frontend Architecture

### Admin Interface (React/Gutenberg)
- Component-based architecture
- State management with WordPress data stores
- Real-time updates with WebSocket
- Progressive Web App features

### Public Interface
- Modern JavaScript (ES6+)
- Service workers for offline support
- Responsive design with CSS Grid/Flexbox
- Accessibility (WCAG 2.1 AA)

## Performance Optimization

### Database Optimization
- Proper indexing strategy
- Query optimization
- Database connection pooling
- Read replicas for scaling

### Caching Strategy
- Object cache for frequently accessed data
- Page caching for dashboards
- CDN for static assets
- Browser caching headers

### Code Optimization
- Lazy loading for large datasets
- Code splitting for JavaScript
- Image optimization
- Minification and compression

## Scalability Considerations

### Multi-Tenant Support
- Company_id column for data isolation
- Configurable features per company
- Separate database schemas option
- Horizontal sharding support

### Performance Scaling
- Load balancing ready
- Microservices architecture path
- API gateway integration
- Event-driven architecture

### Future Expansion
- Plugin ecosystem support
- Third-party integrations
- Mobile app API
- Analytics and reporting

## Development Workflow

### Environment Setup
- Local development with Docker
- Staging environment for testing
- CI/CD pipeline
- Automated testing

### Code Quality
- PHPStan for static analysis
- ESLint for JavaScript
- PHP CodeSniffer for standards
- Automated code reviews

### Testing Strategy
- Unit tests (PHPUnit)
- Integration tests
- End-to-end tests
- Performance tests

## Deployment Strategy

### Production Deployment
- Blue-green deployment
- Database migrations
- Feature flags
- Monitoring and alerting

### Monitoring
- Application performance monitoring
- Error tracking
- User analytics
- System health checks

This architecture ensures the plugin is production-ready, scalable, secure, and maintainable for long-term enterprise use.
