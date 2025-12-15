<?php
/**
 * Settings Class
 * 
 * Handles the plugin settings page for styling defaults
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCARDM_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'flashcard-manager',
            __('Settings', 'flashcard-manager'),
            __('Settings', 'flashcard-manager'),
            'manage_options',
            'flashcard-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('fcardm_settings_group', 'fcardm_settings', array($this, 'sanitize_settings'));
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Size settings
        $sanitized['card_height'] = isset($input['card_height']) ? intval($input['card_height']) : 300;
        $sanitized['font_size'] = isset($input['font_size']) ? intval($input['font_size']) : 18;
        $sanitized['border_radius'] = isset($input['border_radius']) ? intval($input['border_radius']) : 16;
        $sanitized['card_padding'] = isset($input['card_padding']) ? intval($input['card_padding']) : 30;
        $sanitized['container_padding'] = isset($input['container_padding']) ? intval($input['container_padding']) : 0;
        $sanitized['container_margin'] = isset($input['container_margin']) ? intval($input['container_margin']) : 20;
        $sanitized['max_width'] = isset($input['max_width']) ? intval($input['max_width']) : 800;
        
        // Text alignment
        $sanitized['text_align'] = isset($input['text_align']) && in_array($input['text_align'], array('left', 'center', 'right', 'justify')) 
            ? $input['text_align'] : 'center';
        
        // Counter position
        $sanitized['counter_position'] = isset($input['counter_position']) && in_array($input['counter_position'], array('top', 'bottom')) 
            ? $input['counter_position'] : 'bottom';
        
        // Colors
        $sanitized['front_color'] = isset($input['front_color']) ? sanitize_hex_color($input['front_color']) : '#667eea';
        $sanitized['front_color_end'] = isset($input['front_color_end']) ? sanitize_hex_color($input['front_color_end']) : '#764ba2';
        $sanitized['back_color'] = isset($input['back_color']) ? sanitize_hex_color($input['back_color']) : '#11998e';
        $sanitized['back_color_end'] = isset($input['back_color_end']) ? sanitize_hex_color($input['back_color_end']) : '#38ef7d';
        $sanitized['text_color'] = isset($input['text_color']) ? sanitize_hex_color($input['text_color']) : '#ffffff';
        $sanitized['button_color'] = isset($input['button_color']) ? sanitize_hex_color($input['button_color']) : '#4361ee';
        
        // Display options
        $sanitized['show_title'] = isset($input['show_title']) ? 'true' : 'false';
        $sanitized['show_counter'] = isset($input['show_counter']) ? 'true' : 'false';
        $sanitized['show_progress'] = isset($input['show_progress']) ? 'true' : 'false';
        $sanitized['show_shuffle'] = isset($input['show_shuffle']) ? 'true' : 'false';
        
        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = FCARDM_Shortcode::get_defaults();
        ?>
        <div class="wrap fcardm-settings-wrap">
            <h1><?php esc_html_e('Flashcard Settings', 'flashcard-manager'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('fcardm_settings_group'); ?>
                
                <div class="fcardm-settings-grid">
                    <!-- Size Settings -->
                    <div class="fcardm-settings-card">
                        <h2><?php esc_html_e('Card Size & Layout', 'flashcard-manager'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="card_height"><?php esc_html_e('Card Height (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="card_height" name="fcardm_settings[card_height]" 
                                           value="<?php echo esc_attr($settings['card_height']); ?>" 
                                           min="150" max="800" step="10" class="small-text">
                                    <p class="description"><?php esc_html_e('Height of the flashcard. Default: 300px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="max_width"><?php esc_html_e('Max Width (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="max_width" name="fcardm_settings[max_width]" 
                                           value="<?php echo esc_attr($settings['max_width']); ?>" 
                                           min="0" max="2000" step="10" class="small-text">
                                    <p class="description"><?php esc_html_e('Maximum container width. Set to 0 for no limit. Default: 800px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="card_padding"><?php esc_html_e('Card Padding (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="card_padding" name="fcardm_settings[card_padding]" 
                                           value="<?php echo esc_attr($settings['card_padding']); ?>" 
                                           min="10" max="100" step="5" class="small-text">
                                    <p class="description"><?php esc_html_e('Internal padding inside the card. Default: 30px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="border_radius"><?php esc_html_e('Border Radius (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="border_radius" name="fcardm_settings[border_radius]" 
                                           value="<?php echo esc_attr($settings['border_radius']); ?>" 
                                           min="0" max="50" step="1" class="small-text">
                                    <p class="description"><?php esc_html_e('Corner roundness. 0 for square. Default: 16px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Container Spacing -->
                    <div class="fcardm-settings-card">
                        <h2><?php esc_html_e('Container Spacing', 'flashcard-manager'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="container_margin"><?php esc_html_e('Container Margin (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="container_margin" name="fcardm_settings[container_margin]" 
                                           value="<?php echo esc_attr($settings['container_margin']); ?>" 
                                           min="0" max="100" step="5" class="small-text">
                                    <p class="description"><?php esc_html_e('Outer spacing around the flashcard module. Default: 20px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="container_padding"><?php esc_html_e('Container Padding (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="container_padding" name="fcardm_settings[container_padding]" 
                                           value="<?php echo esc_attr($settings['container_padding']); ?>" 
                                           min="0" max="100" step="5" class="small-text">
                                    <p class="description"><?php esc_html_e('Inner spacing of the container. Default: 0px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Text Settings -->
                    <div class="fcardm-settings-card">
                        <h2><?php esc_html_e('Text Settings', 'flashcard-manager'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="font_size"><?php esc_html_e('Font Size (px)', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="number" id="font_size" name="fcardm_settings[font_size]" 
                                           value="<?php echo esc_attr($settings['font_size']); ?>" 
                                           min="12" max="48" step="1" class="small-text">
                                    <p class="description"><?php esc_html_e('Text size on cards. Default: 18px', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="text_align"><?php esc_html_e('Text Alignment', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <select id="text_align" name="fcardm_settings[text_align]">
                                        <option value="left" <?php selected($settings['text_align'], 'left'); ?>><?php esc_html_e('Left', 'flashcard-manager'); ?></option>
                                        <option value="center" <?php selected($settings['text_align'], 'center'); ?>><?php esc_html_e('Center', 'flashcard-manager'); ?></option>
                                        <option value="right" <?php selected($settings['text_align'], 'right'); ?>><?php esc_html_e('Right', 'flashcard-manager'); ?></option>
                                        <option value="justify" <?php selected($settings['text_align'], 'justify'); ?>><?php esc_html_e('Justify', 'flashcard-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="counter_position"><?php esc_html_e('Counter Position', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <select id="counter_position" name="fcardm_settings[counter_position]">
                                        <option value="top" <?php selected($settings['counter_position'], 'top'); ?>><?php esc_html_e('Above Cards', 'flashcard-manager'); ?></option>
                                        <option value="bottom" <?php selected($settings['counter_position'], 'bottom'); ?>><?php esc_html_e('Below Cards', 'flashcard-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Color Settings -->
                    <div class="fcardm-settings-card">
                        <h2><?php esc_html_e('Colors', 'flashcard-manager'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Front Card Gradient', 'flashcard-manager'); ?></th>
                                <td>
                                    <div class="fcardm-color-group">
                                        <input type="color" id="front_color" name="fcardm_settings[front_color]" 
                                               value="<?php echo esc_attr($settings['front_color']); ?>">
                                        <span>→</span>
                                        <input type="color" id="front_color_end" name="fcardm_settings[front_color_end]" 
                                               value="<?php echo esc_attr($settings['front_color_end']); ?>">
                                    </div>
                                    <p class="description"><?php esc_html_e('Question side gradient colors', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Back Card Gradient', 'flashcard-manager'); ?></th>
                                <td>
                                    <div class="fcardm-color-group">
                                        <input type="color" id="back_color" name="fcardm_settings[back_color]" 
                                               value="<?php echo esc_attr($settings['back_color']); ?>">
                                        <span>→</span>
                                        <input type="color" id="back_color_end" name="fcardm_settings[back_color_end]" 
                                               value="<?php echo esc_attr($settings['back_color_end']); ?>">
                                    </div>
                                    <p class="description"><?php esc_html_e('Answer side gradient colors', 'flashcard-manager'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="text_color"><?php esc_html_e('Text Color', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="color" id="text_color" name="fcardm_settings[text_color]" 
                                           value="<?php echo esc_attr($settings['text_color']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="button_color"><?php esc_html_e('Button Color', 'flashcard-manager'); ?></label></th>
                                <td>
                                    <input type="color" id="button_color" name="fcardm_settings[button_color]" 
                                           value="<?php echo esc_attr($settings['button_color']); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Display Options -->
                    <div class="fcardm-settings-card">
                        <h2><?php esc_html_e('Display Options', 'flashcard-manager'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Show Title', 'flashcard-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fcardm_settings[show_title]" value="1" 
                                               <?php checked($settings['show_title'], 'true'); ?>>
                                        <?php esc_html_e('Display flashcard set title', 'flashcard-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Show Counter', 'flashcard-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fcardm_settings[show_counter]" value="1" 
                                               <?php checked($settings['show_counter'], 'true'); ?>>
                                        <?php esc_html_e('Display card counter (1 / 10)', 'flashcard-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Show Progress Bar', 'flashcard-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fcardm_settings[show_progress]" value="1" 
                                               <?php checked($settings['show_progress'], 'true'); ?>>
                                        <?php esc_html_e('Display progress bar', 'flashcard-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Show Shuffle Button', 'flashcard-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="fcardm_settings[show_shuffle]" value="1" 
                                               <?php checked($settings['show_shuffle'], 'true'); ?>>
                                        <?php esc_html_e('Display shuffle button', 'flashcard-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Shortcode Reference -->
                    <div class="fcardm-settings-card fcardm-shortcode-ref">
                        <h2><?php esc_html_e('Shortcode Reference', 'flashcard-manager'); ?></h2>
                        
                        <p><?php esc_html_e('Basic usage:', 'flashcard-manager'); ?></p>
                        <code>[flashcards id="123"]</code>
                        
                        <p style="margin-top: 15px;"><?php esc_html_e('With custom options (override settings):', 'flashcard-manager'); ?></p>
                        <code>[flashcards id="123" height="350" card_padding="40" text_align="left" counter_position="bottom"]</code>
                        
                        <h3 style="margin-top: 20px;"><?php esc_html_e('All Available Options', 'flashcard-manager'); ?></h3>
                        <div class="fcardm-ref-columns">
                            <div class="fcardm-ref-col">
                                <h4><?php esc_html_e('Size & Layout', 'flashcard-manager'); ?></h4>
                                <table class="fcardm-ref-table">
                                    <tr><td><code>height</code></td><td><?php esc_html_e('Card height in px', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>max_width</code></td><td><?php esc_html_e('Max container width (px or "none")', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>width</code></td><td><?php esc_html_e('Container width (e.g., "100%")', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>card_padding</code></td><td><?php esc_html_e('Inner card padding in px', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>container_padding</code></td><td><?php esc_html_e('Container padding in px', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>container_margin</code></td><td><?php esc_html_e('Container margin in px', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>border_radius</code></td><td><?php esc_html_e('Corner radius in px', 'flashcard-manager'); ?></td></tr>
                                </table>
                            </div>
                            <div class="fcardm-ref-col">
                                <h4><?php esc_html_e('Text & Display', 'flashcard-manager'); ?></h4>
                                <table class="fcardm-ref-table">
                                    <tr><td><code>font_size</code></td><td><?php esc_html_e('Text size in px', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>text_align</code></td><td><?php esc_html_e('left, center, right, justify', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>counter_position</code></td><td><?php esc_html_e('top or bottom', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>show_title</code></td><td><?php esc_html_e('true or false', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>show_counter</code></td><td><?php esc_html_e('true or false', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>show_progress</code></td><td><?php esc_html_e('true or false', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>show_shuffle</code></td><td><?php esc_html_e('true or false', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>shuffle</code></td><td><?php esc_html_e('true to shuffle on load', 'flashcard-manager'); ?></td></tr>
                                </table>
                            </div>
                            <div class="fcardm-ref-col">
                                <h4><?php esc_html_e('Colors (hex)', 'flashcard-manager'); ?></h4>
                                <table class="fcardm-ref-table">
                                    <tr><td><code>front_color</code></td><td><?php esc_html_e('Front gradient start', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>front_color_end</code></td><td><?php esc_html_e('Front gradient end', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>back_color</code></td><td><?php esc_html_e('Back gradient start', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>back_color_end</code></td><td><?php esc_html_e('Back gradient end', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>text_color</code></td><td><?php esc_html_e('Card text color', 'flashcard-manager'); ?></td></tr>
                                    <tr><td><code>button_color</code></td><td><?php esc_html_e('Button color', 'flashcard-manager'); ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(__('Save Settings', 'flashcard-manager')); ?>
            </form>
        </div>
        
        <style>
            .fcardm-settings-wrap { max-width: 1400px; }
            .fcardm-settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
            .fcardm-settings-card { background: #fff; padding: 20px 25px; border: 1px solid #c3c4c7; border-radius: 8px; }
            .fcardm-settings-card h2 { margin: 0 0 15px; padding: 0; border: none; font-size: 1.2em; }
            .fcardm-settings-card .form-table { margin: 0; }
            .fcardm-settings-card .form-table th { padding: 12px 10px 12px 0; width: 45%; font-weight: 500; }
            .fcardm-settings-card .form-table td { padding: 12px 0; }
            .fcardm-color-group { display: flex; align-items: center; gap: 10px; }
            .fcardm-color-group input[type="color"] { width: 60px; height: 36px; padding: 2px; cursor: pointer; }
            .fcardm-shortcode-ref { grid-column: span 2; }
            .fcardm-shortcode-ref code { display: inline-block; background: #f0f0f1; padding: 8px 12px; border-radius: 4px; font-size: 13px; }
            .fcardm-ref-columns { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px; }
            .fcardm-ref-col h4 { margin: 0 0 10px; font-size: 13px; color: #1d2327; }
            .fcardm-ref-table { width: 100%; border-collapse: collapse; }
            .fcardm-ref-table td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; font-size: 12px; }
            .fcardm-ref-table td:first-child { width: 140px; }
            .fcardm-ref-table code { background: #f6f7f7; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
            @media (max-width: 1200px) {
                .fcardm-settings-grid { grid-template-columns: 1fr; }
                .fcardm-shortcode-ref { grid-column: span 1; }
                .fcardm-ref-columns { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }
}
