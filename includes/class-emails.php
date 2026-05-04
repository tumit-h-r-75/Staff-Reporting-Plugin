<?php
class Basmah_Staff_Reports_Emails {
    public static function notify_manager_on_report_submission($user_id, $report_date) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $managers = get_users(array(
            'role' => 'basmah_manager',
            'fields' => array('user_email', 'display_name')
        ));

        if (empty($managers)) {
            return false;
        }

        $subject = sprintf('New Daily Report Submitted by %s', $user->display_name);
        $message = sprintf("A new daily report has been submitted by %s (%s) for %s.", $user->display_name, $user->user_email, $report_date);
        $message .= "\n\nYou can review the report in the WordPress admin panel under Staff Reports > All Reports.";

        foreach ($managers as $manager) {
            wp_mail($manager->user_email, $subject, $message);
        }

        return true;
    }
}
