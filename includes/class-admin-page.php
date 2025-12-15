<?php
/**
 * Admin Page Class
 * 
 * Renders the CSV import admin interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCSV_Admin_Page {

    /**
     * Render the admin page
     */
    public static function render() {
        // Get all flashcard sets
        $flashcard_sets = get_posts(array(
            'post_type' => 'flashcard_set',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <div class="wrap fcsv-wrap">
            <h1><?php esc_html_e('Flashcard CSV Import', 'flashcard-csv-importer'); ?></h1>
            
            <div class="fcsv-container">
                <!-- Step 1: Upload Section -->
                <div id="fcsv-upload-section" class="fcsv-section">
                    <div class="fcsv-card">
                        <h2><?php esc_html_e('Import Flashcards from CSV', 'flashcard-csv-importer'); ?></h2>
                        
                        <p class="fcsv-description">
                            <?php esc_html_e('Upload a CSV file with "question" and "answer" columns to import flashcards.', 'flashcard-csv-importer'); ?>
                        </p>
                        
                        <!-- Flashcard Set Selector -->
                        <div class="fcsv-field">
                            <label for="fcsv-flashcard-set">
                                <?php esc_html_e('Select Flashcard Set:', 'flashcard-csv-importer'); ?>
                            </label>
                            <select id="fcsv-flashcard-set" name="flashcard_set_id" required>
                                <option value=""><?php esc_html_e('-- Select a Flashcard Set --', 'flashcard-csv-importer'); ?></option>
                                <?php foreach ($flashcard_sets as $set) : ?>
                                    <option value="<?php echo esc_attr($set->ID); ?>">
                                        <?php echo esc_html($set->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($flashcard_sets)) : ?>
                                <p class="fcsv-warning">
                                    <?php 
                                    printf(
                                        esc_html__('No flashcard sets found. %sCreate one first%s.', 'flashcard-csv-importer'),
                                        '<a href="' . esc_url(admin_url('post-new.php?post_type=flashcard_set')) . '">',
                                        '</a>'
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Drop Zone -->
                        <div id="fcsv-drop-zone" class="fcsv-drop-zone">
                            <div class="fcsv-drop-zone-content">
                                <span class="dashicons dashicons-cloud-upload"></span>
                                <p class="fcsv-drop-text">
                                    <strong><?php esc_html_e('Drag and drop your CSV file here', 'flashcard-csv-importer'); ?></strong>
                                </p>
                                <p class="fcsv-or"><?php esc_html_e('or', 'flashcard-csv-importer'); ?></p>
                                <button type="button" class="button button-secondary" id="fcsv-browse-btn">
                                    <?php esc_html_e('Browse Files', 'flashcard-csv-importer'); ?>
                                </button>
                                <p class="fcsv-file-info">
                                    <?php 
                                    printf(
                                        esc_html__('Maximum file size: %s', 'flashcard-csv-importer'),
                                        size_format(wp_max_upload_size())
                                    );
                                    ?>
                                </p>
                            </div>
                            <input type="file" id="fcsv-file-input" accept=".csv" style="display: none;">
                        </div>
                        
                        <div id="fcsv-upload-status"></div>
                    </div>
                    
                    <!-- CSV Format Help -->
                    <div class="fcsv-card fcsv-help-card">
                        <h3><?php esc_html_e('CSV Format', 'flashcard-csv-importer'); ?></h3>
                        <p><?php esc_html_e('Your CSV file should have the following columns:', 'flashcard-csv-importer'); ?></p>
                        <table class="fcsv-format-table">
                            <thead>
                                <tr>
                                    <th>question</th>
                                    <th>answer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>What is the capital of France?</td>
                                    <td>Paris</td>
                                </tr>
                                <tr>
                                    <td>What is 2 + 2?</td>
                                    <td>4</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="fcsv-note">
                            <strong><?php esc_html_e('Note:', 'flashcard-csv-importer'); ?></strong>
                            <?php esc_html_e('Column headers must be lowercase "question" and "answer".', 'flashcard-csv-importer'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Step 2: Confirmation Section -->
                <div id="fcsv-confirmation-section" class="fcsv-section" style="display: none;">
                    <div class="fcsv-card">
                        <h2><?php esc_html_e('Confirm Import', 'flashcard-csv-importer'); ?></h2>
                        <div id="fcsv-preview-info"></div>
                        <div class="fcsv-actions">
                            <button type="button" id="fcsv-confirm-btn" class="button button-primary button-large">
                                <?php esc_html_e('Start Import', 'flashcard-csv-importer'); ?>
                            </button>
                            <button type="button" id="fcsv-cancel-btn" class="button button-secondary">
                                <?php esc_html_e('Cancel', 'flashcard-csv-importer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Processing Section -->
                <div id="fcsv-processing-section" class="fcsv-section" style="display: none;">
                    <div class="fcsv-card">
                        <h2><?php esc_html_e('Importing...', 'flashcard-csv-importer'); ?></h2>
                        <div class="fcsv-progress-container">
                            <div class="fcsv-spinner"></div>
                            <p id="fcsv-progress-message"><?php esc_html_e('Processing your flashcards...', 'flashcard-csv-importer'); ?></p>
                        </div>
                        
                        <!-- Import Log -->
                        <div id="fcsv-log-container" class="fcsv-log">
                            <div class="fcsv-log-header">
                                <span><?php esc_html_e('Import Log', 'flashcard-csv-importer'); ?></span>
                                <button type="button" id="fcsv-toggle-log" class="button button-small">
                                    <?php esc_html_e('Hide', 'flashcard-csv-importer'); ?>
                                </button>
                            </div>
                            <div id="fcsv-log-content"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Results Section -->
                <div id="fcsv-results-section" class="fcsv-section" style="display: none;">
                    <div class="fcsv-card">
                        <h2><?php esc_html_e('Import Complete', 'flashcard-csv-importer'); ?></h2>
                        <div id="fcsv-results-content"></div>
                        <div class="fcsv-actions">
                            <button type="button" id="fcsv-new-import-btn" class="button button-primary">
                                <?php esc_html_e('Import Another File', 'flashcard-csv-importer'); ?>
                            </button>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=flashcard_set')); ?>" class="button button-secondary">
                                <?php esc_html_e('View Flashcard Sets', 'flashcard-csv-importer'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

