<?php
// Simple test to check if class can be loaded
require_once 'api/class-rest-report-approvals.php';

if (class_exists('Bassmah_Staff_Reports_REST_Report_Approvals')) {
    echo "Class exists - OK\n";
} else {
    echo "Class NOT FOUND - ERROR\n";
}

echo "File loaded successfully\n";
?>
