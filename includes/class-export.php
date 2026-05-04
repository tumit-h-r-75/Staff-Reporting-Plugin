<?php
class Basmah_Staff_Reports_Export {
    public static function export_csv($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys((array) $data[0]));
            
            foreach ($data as $row) {
                fputcsv($output, (array) $row);
            }
        }
        
        fclose($output);
        exit;
    }
}
