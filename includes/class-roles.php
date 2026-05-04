<?php
class Basmah_Staff_Reports_Roles {
    public static function add_roles() {
        add_role('bsr_staff', 'Staff', array(
            'read' => true,
        ));
        
        add_role('bsr_manager', 'Manager', array(
            'read' => true,
            'edit_posts' => true,
        ));
    }
    
    public static function remove_roles() {
        remove_role('bsr_staff');
        remove_role('bsr_manager');
    }
}
