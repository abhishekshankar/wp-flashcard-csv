<?php
/**
 * CSV Processor Class
 * 
 * Handles CSV file parsing and flashcard import
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCSV_CSV_Processor {

    /**
     * Batch size for processing
     */
    const BATCH_SIZE = 500;

    /**
     * Required CSV columns
     */
    const REQUIRED_COLUMNS = array('question', 'answer');

    /**
     * Supported delimiters
     */
    const DELIMITERS = array(',', ';', "\t");

    /**
     * Flashcard set ID
     */
    private $flashcard_set_id = 0;

    /**
     * Import log entries
     */
    private $log = array();

    /**
     * Add log entry
     */
    private function log($message, $type = 'info') {
        $this->log[] = array(
            'time' => current_time('H:i:s'),
            'type' => $type,
            'message' => $message
        );
    }

    /**
     * Get log entries
     */
    public function get_log() {
        return $this->log;
    }

    /**
     * Validate CSV file
     */
    public function validate($file_path) {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'headers' => array(),
            'row_count' => 0
        );

        // Check file exists
        if (!file_exists($file_path)) {
            $result['valid'] = false;
            $result['errors'][] = __('File not found.', 'flashcard-csv-importer');
            return $result;
        }

        // Check file extension
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $result['valid'] = false;
            $result['errors'][] = __('Invalid file type. Only CSV files are allowed.', 'flashcard-csv-importer');
            return $result;
        }

        // Open file
        $handle = @fopen($file_path, 'r');
        if (!$handle) {
            $result['valid'] = false;
            $result['errors'][] = __('Unable to read file.', 'flashcard-csv-importer');
            return $result;
        }

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detect delimiter and read headers
        $delimiter = $this->detect_delimiter($handle);
        $headers = fgetcsv($handle, 0, $delimiter);

        if (empty($headers)) {
            $result['valid'] = false;
            $result['errors'][] = __('CSV file is empty.', 'flashcard-csv-importer');
            fclose($handle);
            return $result;
        }

        // Normalize headers
        $headers = $this->normalize_headers($headers);
        $result['headers'] = $headers;

        // Check required columns
        $missing = array();
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!in_array($col, $headers)) {
                $missing[] = $col;
            }
        }

        if (!empty($missing)) {
            $result['valid'] = false;
            $result['errors'][] = sprintf(
                __('Missing required columns: %s. Found columns: %s', 'flashcard-csv-importer'),
                implode(', ', $missing),
                implode(', ', $headers)
            );
            fclose($handle);
            return $result;
        }

        // Count rows
        $count = 0;
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $count++;
            if ($count >= 10000) break; // Limit for performance
        }
        $result['row_count'] = $count;

        fclose($handle);
        return $result;
    }

    /**
     * Process CSV file and import flashcards
     */
    public function process($file_path, $flashcard_set_id) {
        @set_time_limit(600);
        @ini_set('memory_limit', '256M');

        $this->flashcard_set_id = $flashcard_set_id;
        $this->log('Starting import process', 'info');
        $this->log('File: ' . basename($file_path), 'info');

        $results = array(
            'total_rows' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => array()
        );

        // Validate flashcard set
        $set = get_post($flashcard_set_id);
        if (!$set || $set->post_type !== 'flashcard_set') {
            $this->log('Invalid flashcard set ID', 'error');
            $results['errors']++;
            $results['error_messages'][] = __('Invalid flashcard set.', 'flashcard-csv-importer');
            return $results;
        }

        $this->log('Importing to: ' . $set->post_title, 'info');

        // Open file
        $handle = @fopen($file_path, 'r');
        if (!$handle) {
            $this->log('Cannot open file', 'error');
            $results['errors']++;
            $results['error_messages'][] = __('Unable to read file.', 'flashcard-csv-importer');
            return $results;
        }

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Get delimiter and headers
        $delimiter = $this->detect_delimiter($handle);
        $headers = $this->normalize_headers(fgetcsv($handle, 0, $delimiter));
        
        $this->log('Columns: ' . implode(', ', $headers), 'info');

        // Map column indices
        $question_idx = array_search('question', $headers);
        $answer_idx = array_search('answer', $headers);

        // Get existing flashcards
        $existing = get_post_meta($flashcard_set_id, 'flashcard_slides', true);
        if (!is_array($existing)) {
            $existing = array();
        }

        $this->log('Existing flashcards: ' . count($existing), 'info');

        // Process rows
        $batch = array();
        $row_num = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row_num++;
            $results['total_rows']++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $question = isset($row[$question_idx]) ? trim($row[$question_idx]) : '';
            $answer = isset($row[$answer_idx]) ? trim($row[$answer_idx]) : '';

            // Validate
            if (empty($question)) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(__('Row %d: Missing question.', 'flashcard-csv-importer'), $row_num);
                continue;
            }

            if (empty($answer)) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(__('Row %d: Missing answer.', 'flashcard-csv-importer'), $row_num);
                continue;
            }

            // Sanitize
            $question = sanitize_text_field(wp_strip_all_tags($question));
            $answer = sanitize_text_field(wp_strip_all_tags($answer));

            // Check for duplicates
            if (isset($existing[$question]) || isset($batch[$question])) {
                $results['skipped']++;
                continue;
            }

            $batch[$question] = array(
                'foreground' => $question,
                'background' => $answer
            );
            $results['created']++;

            // Process batch
            if (count($batch) >= self::BATCH_SIZE) {
                $existing = array_merge($existing, $batch);
                update_post_meta($flashcard_set_id, 'flashcard_slides', $existing);
                $this->log('Saved batch of ' . count($batch) . ' flashcards', 'success');
                $batch = array();
            }
        }

        // Save remaining
        if (!empty($batch)) {
            $existing = array_merge($existing, $batch);
            update_post_meta($flashcard_set_id, 'flashcard_slides', $existing);
            $this->log('Saved final batch of ' . count($batch) . ' flashcards', 'success');
        }

        fclose($handle);

        $this->log('Import complete!', 'success');
        $this->log('Created: ' . $results['created'] . ', Skipped: ' . $results['skipped'] . ', Errors: ' . $results['errors'], 'info');

        return $results;
    }

    /**
     * Detect CSV delimiter
     */
    private function detect_delimiter($handle) {
        $pos = ftell($handle);
        $line = fgets($handle);
        fseek($handle, $pos);

        if (!$line) return ',';

        $counts = array();
        foreach (self::DELIMITERS as $d) {
            $counts[$d] = substr_count($line, $d);
        }

        $max = max($counts);
        if ($max > 0) {
            return array_search($max, $counts);
        }

        return ',';
    }

    /**
     * Normalize headers
     */
    private function normalize_headers($headers) {
        return array_map(function($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = trim($h, " \t\n\r\0\x0B\"'");
            return strtolower($h);
        }, $headers);
    }
}

