<?php
class Basmah_Staff_Reports_Deactivator {
    public static function deactivate() {
        Basmah_Staff_Reports_Roles::remove_roles();
        flush_rewrite_rules();
    }
}
