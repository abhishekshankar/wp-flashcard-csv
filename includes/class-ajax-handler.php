<?php
/**
 * AJAX Handler Class
 * 
 * Handles all AJAX requests for CSV import
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCSV_Ajax_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_fcsv_upload', array($this, 'handle_upload'));
        add_action('wp_ajax_fcsv_process', array($this, 'handle_process'));
    }

    /**
     * Handle file upload
     */
    public function handle_upload() {
        // Verify nonce
        if (!check_ajax_referer('fcsv_import', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flashcard-csv-importer')));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'flashcard-csv-importer')));
        }

        // Check file
        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'flashcard-csv-importer')));
        }

        $file = $_FILES['file'];

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            wp_send_json_error(array('message' => __('Only CSV files are allowed.', 'flashcard-csv-importer')));
        }

        // Validate size
        $max_size = min(wp_max_upload_size(), 10 * 1024 * 1024);
        if ($file['size'] > $max_size) {
            wp_send_json_error(array(
                'message' => sprintf(__('File too large. Maximum size: %s', 'flashcard-csv-importer'), size_format($max_size))
            ));
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $import_dir = $upload_dir['basedir'] . '/flashcard-csv-imports';
        
        if (!file_exists($import_dir)) {
            wp_mkdir_p($import_dir);
            file_put_contents($import_dir . '/.htaccess', 'deny from all');
        }

        // Save file
        $filename = 'import-' . time() . '-' . wp_generate_password(8, false) . '.csv';
        $file_path = $import_dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => __('Failed to save file.', 'flashcard-csv-importer')));
        }

        // Validate CSV
        $processor = new FCSV_CSV_Processor();
        $validation = $processor->validate($file_path);

        if (!$validation['valid']) {
            @unlink($file_path);
            wp_send_json_error(array(
                'message' => implode(' ', $validation['errors']),
                'errors' => $validation['errors']
            ));
        }

        // Store file path in transient
        $transient_key = 'fcsv_import_' . get_current_user_id();
        set_transient($transient_key, $file_path, HOUR_IN_SECONDS);

        wp_send_json_success(array(
            'message' => __('File validated successfully.', 'flashcard-csv-importer'),
            'headers' => $validation['headers'],
            'row_count' => $validation['row_count']
        ));
    }

    /**
     * Handle import processing
     */
    public function handle_process() {
        @set_time_limit(600);

        // Verify nonce
        if (!check_ajax_referer('fcsv_import', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flashcard-csv-importer')));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'flashcard-csv-importer')));
        }

        // Get file path
        $transient_key = 'fcsv_import_' . get_current_user_id();
        $file_path = get_transient($transient_key);

        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(array('message' => __('File not found. Please upload again.', 'flashcard-csv-importer')));
        }

        // Get flashcard set ID
        $flashcard_set_id = isset($_POST['flashcard_set_id']) ? intval($_POST['flashcard_set_id']) : 0;

        if (!$flashcard_set_id) {
            wp_send_json_error(array('message' => __('Please select a flashcard set.', 'flashcard-csv-importer')));
        }

        // Validate flashcard set
        $set = get_post($flashcard_set_id);
        if (!$set || $set->post_type !== 'flashcard_set') {
            wp_send_json_error(array('message' => __('Invalid flashcard set.', 'flashcard-csv-importer')));
        }

        // Process import
        $processor = new FCSV_CSV_Processor();
        $results = $processor->process($file_path, $flashcard_set_id);
        $results['log'] = $processor->get_log();

        // Cleanup
        @unlink($file_path);
        delete_transient($transient_key);

        wp_send_json_success($results);
    }
}

