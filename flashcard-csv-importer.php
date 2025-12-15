<?php
/**
 * Plugin Name: Flashcard Manager
 * Description: Create, import, and display flashcard sets with CSV import support
 * Version: 2.0.0
 * Author: Cognitive Care Alliance
 * Author URI: https://cogcare.org/
 * Text Domain: flashcard-manager
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('FCARDM_VERSION', '2.0.0');
define('FCARDM_URL', plugin_dir_url(__FILE__));
define('FCARDM_PATH', plugin_dir_path(__FILE__));
define('FCARDM_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class Flashcard_Manager {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once FCARDM_PATH . 'includes/class-csv-processor.php';
        require_once FCARDM_PATH . 'includes/class-ajax-handler.php';
        require_once FCARDM_PATH . 'includes/class-admin-page.php';
        require_once FCARDM_PATH . 'includes/class-post-type.php';
        require_once FCARDM_PATH . 'includes/class-shortcode.php';
        require_once FCARDM_PATH . 'includes/class-settings.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register post type
        add_action('init', array('FCARDM_Post_Type', 'register'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Metabox
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_flashcard_set', array($this, 'save_metabox'), 10, 2);
        
        // Initialize AJAX handler
        new FCSV_Ajax_Handler();
        
        // Initialize shortcode
        new FCARDM_Shortcode();
        
        // Initialize settings
        new FCARDM_Settings();
    }

    /**
     * Add admin menu - Top level menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Flashcards', 'flashcard-manager'),
            __('Flashcards', 'flashcard-manager'),
            'manage_options',
            'flashcard-manager',
            array($this, 'render_dashboard'),
            'dashicons-index-card',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'flashcard-manager',
            __('Dashboard', 'flashcard-manager'),
            __('Dashboard', 'flashcard-manager'),
            'manage_options',
            'flashcard-manager',
            array($this, 'render_dashboard')
        );
        
        // All Flashcard Sets
        add_submenu_page(
            'flashcard-manager',
            __('All Sets', 'flashcard-manager'),
            __('All Sets', 'flashcard-manager'),
            'manage_options',
            'edit.php?post_type=flashcard_set'
        );
        
        // Add New
        add_submenu_page(
            'flashcard-manager',
            __('Add New Set', 'flashcard-manager'),
            __('Add New', 'flashcard-manager'),
            'manage_options',
            'post-new.php?post_type=flashcard_set'
        );
        
        // CSV Import
        add_submenu_page(
            'flashcard-manager',
            __('CSV Import', 'flashcard-manager'),
            __('CSV Import', 'flashcard-manager'),
            'manage_options',
            'flashcard-csv-import',
            array('FCSV_Admin_Page', 'render')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        $sets_count = wp_count_posts('flashcard_set');
        $published = isset($sets_count->publish) ? $sets_count->publish : 0;
        
        // Count total flashcards
        $total_cards = 0;
        $sets = get_posts(array('post_type' => 'flashcard_set', 'posts_per_page' => -1, 'post_status' => 'publish'));
        foreach ($sets as $set) {
            $cards = get_post_meta($set->ID, 'flashcard_slides', true);
            if (is_array($cards)) {
                $total_cards += count($cards);
            }
        }
        ?>
        <div class="wrap fcardm-dashboard">
            <h1><?php esc_html_e('Flashcard Manager', 'flashcard-manager'); ?></h1>
            
            <div class="fcardm-stats">
                <div class="fcardm-stat-box">
                    <span class="fcardm-stat-number"><?php echo esc_html($published); ?></span>
                    <span class="fcardm-stat-label"><?php esc_html_e('Flashcard Sets', 'flashcard-manager'); ?></span>
                </div>
                <div class="fcardm-stat-box">
                    <span class="fcardm-stat-number"><?php echo esc_html($total_cards); ?></span>
                    <span class="fcardm-stat-label"><?php esc_html_e('Total Cards', 'flashcard-manager'); ?></span>
                </div>
            </div>
            
            <div class="fcardm-quick-actions">
                <h2><?php esc_html_e('Quick Actions', 'flashcard-manager'); ?></h2>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=flashcard_set')); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Create New Set', 'flashcard-manager'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flashcard-csv-import')); ?>" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Import from CSV', 'flashcard-manager'); ?>
                </a>
            </div>
            
            <div class="fcardm-help">
                <h2><?php esc_html_e('How to Use', 'flashcard-manager'); ?></h2>
                <div class="fcardm-help-grid">
                    <div class="fcardm-help-item">
                        <h3><span class="dashicons dashicons-edit"></span> <?php esc_html_e('Create Sets', 'flashcard-manager'); ?></h3>
                        <p><?php esc_html_e('Create a new flashcard set and add cards manually with questions and answers.', 'flashcard-manager'); ?></p>
                    </div>
                    <div class="fcardm-help-item">
                        <h3><span class="dashicons dashicons-upload"></span> <?php esc_html_e('Import CSV', 'flashcard-manager'); ?></h3>
                        <p><?php esc_html_e('Bulk import flashcards from a CSV file with "question" and "answer" columns.', 'flashcard-manager'); ?></p>
                    </div>
                    <div class="fcardm-help-item">
                        <h3><span class="dashicons dashicons-shortcode"></span> <?php esc_html_e('Display Cards', 'flashcard-manager'); ?></h3>
                        <p><?php esc_html_e('Use the shortcode to display flashcards on any page or post:', 'flashcard-manager'); ?></p>
                        <code>[flashcards id="123"]</code>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .fcardm-dashboard { max-width: 1000px; }
            .fcardm-stats { display: flex; gap: 20px; margin: 30px 0; }
            .fcardm-stat-box { 
                background: #fff; 
                padding: 30px 40px; 
                border-radius: 8px; 
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
            }
            .fcardm-stat-number { 
                display: block; 
                font-size: 48px; 
                font-weight: 700; 
                color: #2271b1; 
                line-height: 1;
            }
            .fcardm-stat-label { 
                display: block; 
                margin-top: 8px; 
                color: #50575e; 
                font-size: 14px;
            }
            .fcardm-quick-actions { margin: 40px 0; }
            .fcardm-quick-actions h2 { margin-bottom: 15px; }
            .fcardm-quick-actions .button-hero { 
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                margin-right: 10px;
            }
            .fcardm-quick-actions .dashicons { font-size: 18px; width: 18px; height: 18px; }
            .fcardm-help { 
                background: #fff; 
                padding: 25px 30px; 
                border-radius: 8px; 
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fcardm-help h2 { margin-top: 0; }
            .fcardm-help-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
            .fcardm-help-item h3 { 
                display: flex; 
                align-items: center; 
                gap: 8px; 
                margin: 0 0 10px; 
                font-size: 15px;
            }
            .fcardm-help-item p { margin: 0; color: #50575e; }
            .fcardm-help-item code { 
                display: inline-block; 
                margin-top: 10px; 
                background: #f0f0f1; 
                padding: 8px 12px;
                border-radius: 4px;
            }
            @media (max-width: 782px) {
                .fcardm-stats { flex-direction: column; }
                .fcardm-help-grid { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'fcardm_flashcards',
            __('Flashcards', 'flashcard-manager'),
            array($this, 'render_flashcards_metabox'),
            'flashcard_set',
            'normal',
            'high'
        );
        
        add_meta_box(
            'fcardm_shortcode',
            __('Shortcode', 'flashcard-manager'),
            array($this, 'render_shortcode_metabox'),
            'flashcard_set',
            'side',
            'high'
        );
    }

    /**
     * Render flashcards metabox
     */
    public function render_flashcards_metabox($post) {
        wp_nonce_field('fcardm_save_flashcards', 'fcardm_nonce');
        
        $cards = get_post_meta($post->ID, 'flashcard_slides', true);
        if (!is_array($cards)) {
            $cards = array();
        }
        
        // Convert associative array to indexed array for easier handling
        $cards_list = array();
        foreach ($cards as $question => $data) {
            $cards_list[] = array(
                'question' => is_array($data) ? (isset($data['foreground']) ? $data['foreground'] : $question) : $question,
                'answer' => is_array($data) ? (isset($data['background']) ? $data['background'] : $data) : $data
            );
        }
        ?>
        <div id="fcardm-cards-container">
            <div id="fcardm-cards-list">
                <?php if (empty($cards_list)) : ?>
                    <p class="fcardm-no-cards"><?php esc_html_e('No flashcards yet. Add your first card below.', 'flashcard-manager'); ?></p>
                <?php else : ?>
                    <?php foreach ($cards_list as $index => $card) : ?>
                        <div class="fcardm-card-item" data-index="<?php echo esc_attr($index); ?>">
                            <div class="fcardm-card-header">
                                <span class="fcardm-card-number"><?php echo esc_html($index + 1); ?></span>
                                <button type="button" class="fcardm-remove-card button-link-delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <div class="fcardm-card-fields">
                                <div class="fcardm-field">
                                    <label><?php esc_html_e('Question (Front)', 'flashcard-manager'); ?></label>
                                    <textarea name="fcardm_cards[<?php echo esc_attr($index); ?>][question]" rows="2"><?php echo esc_textarea($card['question']); ?></textarea>
                                </div>
                                <div class="fcardm-field">
                                    <label><?php esc_html_e('Answer (Back)', 'flashcard-manager'); ?></label>
                                    <textarea name="fcardm_cards[<?php echo esc_attr($index); ?>][answer]" rows="2"><?php echo esc_textarea($card['answer']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="fcardm-add-card-section">
                <button type="button" id="fcardm-add-card" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Add Card', 'flashcard-manager'); ?>
                </button>
            </div>
        </div>
        
        <template id="fcardm-card-template">
            <div class="fcardm-card-item" data-index="{{INDEX}}">
                <div class="fcardm-card-header">
                    <span class="fcardm-card-number">{{NUMBER}}</span>
                    <button type="button" class="fcardm-remove-card button-link-delete">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                <div class="fcardm-card-fields">
                    <div class="fcardm-field">
                        <label><?php esc_html_e('Question (Front)', 'flashcard-manager'); ?></label>
                        <textarea name="fcardm_cards[{{INDEX}}][question]" rows="2"></textarea>
                    </div>
                    <div class="fcardm-field">
                        <label><?php esc_html_e('Answer (Back)', 'flashcard-manager'); ?></label>
                        <textarea name="fcardm_cards[{{INDEX}}][answer]" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </template>
        
        <style>
            #fcardm-cards-container { padding: 10px 0; }
            .fcardm-no-cards { color: #787c82; font-style: italic; }
            .fcardm-card-item { 
                background: #f6f7f7; 
                border: 1px solid #c3c4c7; 
                border-radius: 4px; 
                margin-bottom: 15px; 
                padding: 15px;
            }
            .fcardm-card-header { 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                margin-bottom: 12px;
                padding-bottom: 10px;
                border-bottom: 1px solid #dcdcde;
            }
            .fcardm-card-number { 
                background: #2271b1; 
                color: #fff; 
                width: 28px; 
                height: 28px; 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                font-weight: 600;
                font-size: 13px;
            }
            .fcardm-remove-card { color: #b32d2e !important; }
            .fcardm-remove-card .dashicons { font-size: 18px; width: 18px; height: 18px; }
            .fcardm-card-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
            .fcardm-field label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px; color: #1d2327; }
            .fcardm-field textarea { width: 100%; resize: vertical; }
            .fcardm-add-card-section { margin-top: 15px; }
            #fcardm-add-card { display: flex; align-items: center; gap: 5px; }
            #fcardm-add-card .dashicons { font-size: 16px; width: 16px; height: 16px; }
            @media (max-width: 782px) {
                .fcardm-card-fields { grid-template-columns: 1fr; }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var cardIndex = <?php echo count($cards_list); ?>;
            
            // Add card
            $('#fcardm-add-card').on('click', function() {
                var template = $('#fcardm-card-template').html();
                template = template.replace(/{{INDEX}}/g, cardIndex);
                template = template.replace(/{{NUMBER}}/g, cardIndex + 1);
                
                $('.fcardm-no-cards').remove();
                $('#fcardm-cards-list').append(template);
                cardIndex++;
                updateCardNumbers();
            });
            
            // Remove card
            $(document).on('click', '.fcardm-remove-card', function() {
                $(this).closest('.fcardm-card-item').remove();
                updateCardNumbers();
                
                if ($('.fcardm-card-item').length === 0) {
                    $('#fcardm-cards-list').html('<p class="fcardm-no-cards"><?php esc_html_e('No flashcards yet. Add your first card below.', 'flashcard-manager'); ?></p>');
                }
            });
            
            function updateCardNumbers() {
                $('.fcardm-card-item').each(function(index) {
                    $(this).find('.fcardm-card-number').text(index + 1);
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render shortcode metabox
     */
    public function render_shortcode_metabox($post) {
        ?>
        <p><?php esc_html_e('Use this shortcode to display flashcards:', 'flashcard-manager'); ?></p>
        <code style="display: block; padding: 10px; background: #f0f0f1; border-radius: 4px; word-break: break-all;">
            [flashcards id="<?php echo esc_attr($post->ID); ?>"]
        </code>
        <p style="margin-top: 12px; color: #787c82; font-size: 12px;">
            <?php esc_html_e('Copy and paste this shortcode into any page or post.', 'flashcard-manager'); ?>
        </p>
        <?php
    }

    /**
     * Save metabox data
     */
    public function save_metabox($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['fcardm_nonce']) || !wp_verify_nonce($_POST['fcardm_nonce'], 'fcardm_save_flashcards')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save cards
        $cards = array();
        if (isset($_POST['fcardm_cards']) && is_array($_POST['fcardm_cards'])) {
            foreach ($_POST['fcardm_cards'] as $card) {
                $question = isset($card['question']) ? sanitize_textarea_field($card['question']) : '';
                $answer = isset($card['answer']) ? sanitize_textarea_field($card['answer']) : '';
                
                if (!empty($question)) {
                    $cards[$question] = array(
                        'foreground' => $question,
                        'background' => $answer
                    );
                }
            }
        }
        
        update_post_meta($post_id, 'flashcard_slides', $cards);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Load on CSV import page
        if ($hook === 'flashcards_page_flashcard-csv-import') {
            wp_enqueue_style(
                'fcsv-admin-style',
                FCARDM_URL . 'assets/css/admin.css',
                array(),
                filemtime(FCARDM_PATH . 'assets/css/admin.css')
            );

            wp_enqueue_script(
                'fcsv-admin-script',
                FCARDM_URL . 'assets/js/admin.js',
                array('jquery'),
                filemtime(FCARDM_PATH . 'assets/js/admin.js'),
                true
            );

            wp_localize_script('fcsv-admin-script', 'fcsvImporter', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fcsv_import'),
                'strings' => array(
                    'uploading' => __('Uploading...', 'flashcard-manager'),
                    'processing' => __('Processing...', 'flashcard-manager'),
                    'success' => __('Import completed successfully!', 'flashcard-manager'),
                    'error' => __('An error occurred.', 'flashcard-manager'),
                    'select_set' => __('Please select a flashcard set first.', 'flashcard-manager'),
                    'invalid_file' => __('Please select a valid CSV file.', 'flashcard-manager'),
                )
            ));
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'fcardm-frontend-style',
            FCARDM_URL . 'assets/css/frontend.css',
            array(),
            FCARDM_VERSION
        );
        
        wp_enqueue_script(
            'fcardm-frontend-script',
            FCARDM_URL . 'assets/js/frontend.js',
            array('jquery'),
            FCARDM_VERSION,
            true
        );
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Include post type class first (not loaded yet during activation)
        require_once FCARDM_PATH . 'includes/class-post-type.php';
        
        // Register post type first
        FCARDM_Post_Type::register();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $import_dir = $upload_dir['basedir'] . '/flashcard-csv-imports';
        
        if (!file_exists($import_dir)) {
            wp_mkdir_p($import_dir);
            file_put_contents($import_dir . '/.htaccess', 'deny from all');
            file_put_contents($import_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
        
        // Clean up transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fcsv_import_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fcsv_import_%'");
    }
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, array('Flashcard_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('Flashcard_Manager', 'deactivate'));

// Initialize plugin
add_action('plugins_loaded', array('Flashcard_Manager', 'get_instance'));
