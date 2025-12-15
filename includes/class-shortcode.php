<?php
/**
 * Shortcode Class
 * 
 * Handles the [flashcards] shortcode for frontend display
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCARDM_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('flashcards', array($this, 'render'));
    }

    /**
     * Get default settings
     */
    public static function get_defaults() {
        $saved = get_option('fcardm_settings', array());
        
        return wp_parse_args($saved, array(
            'card_height' => 300,
            'font_size' => 18,
            'front_color' => '#667eea',
            'front_color_end' => '#764ba2',
            'back_color' => '#11998e',
            'back_color_end' => '#38ef7d',
            'text_color' => '#ffffff',
            'button_color' => '#4361ee',
            'show_title' => 'false',
            'show_counter' => 'true',
            'show_progress' => 'true',
            'show_shuffle' => 'true',
            'border_radius' => 16,
            'card_padding' => 30,
            'container_padding' => 0,
            'container_margin' => 20,
            'text_align' => 'center',
            'max_width' => 800,
            'counter_position' => 'bottom',
        ));
    }

    /**
     * Render the flashcards shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render($atts) {
        $defaults = self::get_defaults();
        
        $atts = shortcode_atts(array(
            'id' => 0,
            'shuffle' => 'false',
            'show_title' => $defaults['show_title'],
            'show_counter' => $defaults['show_counter'],
            'show_progress' => $defaults['show_progress'],
            'show_shuffle' => $defaults['show_shuffle'],
            'height' => $defaults['card_height'],
            'font_size' => $defaults['font_size'],
            'front_color' => $defaults['front_color'],
            'front_color_end' => $defaults['front_color_end'],
            'back_color' => $defaults['back_color'],
            'back_color_end' => $defaults['back_color_end'],
            'text_color' => $defaults['text_color'],
            'button_color' => $defaults['button_color'],
            'border_radius' => $defaults['border_radius'],
            'width' => '100%',
            'max_width' => $defaults['max_width'],
            'card_padding' => $defaults['card_padding'],
            'container_padding' => $defaults['container_padding'],
            'container_margin' => $defaults['container_margin'],
            'text_align' => $defaults['text_align'],
            'counter_position' => $defaults['counter_position'],
        ), $atts, 'flashcards');

        $set_id = intval($atts['id']);
        
        if (!$set_id) {
            return '<p class="fcardm-error">' . esc_html__('Please specify a flashcard set ID.', 'flashcard-manager') . '</p>';
        }

        // Get the flashcard set
        $set = get_post($set_id);
        if (!$set || $set->post_type !== 'flashcard_set') {
            return '<p class="fcardm-error">' . esc_html__('Flashcard set not found.', 'flashcard-manager') . '</p>';
        }

        // Get cards
        $cards = get_post_meta($set_id, 'flashcard_slides', true);
        if (!is_array($cards) || empty($cards)) {
            return '<p class="fcardm-error">' . esc_html__('This flashcard set has no cards.', 'flashcard-manager') . '</p>';
        }

        // Convert to indexed array
        $cards_list = array();
        foreach ($cards as $question => $data) {
            $cards_list[] = array(
                'question' => is_array($data) ? (isset($data['foreground']) ? $data['foreground'] : $question) : $question,
                'answer' => is_array($data) ? (isset($data['background']) ? $data['background'] : $data) : $data
            );
        }

        // Shuffle if requested
        if ($atts['shuffle'] === 'true') {
            shuffle($cards_list);
        }

        $total_cards = count($cards_list);
        $unique_id = 'fcardm-' . $set_id . '-' . wp_rand(1000, 9999);
        
        // Parse display options
        $show_title = $atts['show_title'] === 'true';
        $show_counter = $atts['show_counter'] === 'true';
        $show_progress = $atts['show_progress'] === 'true';
        $show_shuffle = $atts['show_shuffle'] === 'true';
        $counter_bottom = $atts['counter_position'] === 'bottom';
        
        // Sanitize text align
        $text_align = in_array($atts['text_align'], array('left', 'center', 'right', 'justify')) ? $atts['text_align'] : 'center';
        
        // Parse max_width - can be number or "none"
        $max_width_val = $atts['max_width'];
        if ($max_width_val === 'none' || $max_width_val === '0' || $max_width_val === 0) {
            $max_width_css = 'none';
        } else {
            $max_width_css = intval($max_width_val) . 'px';
        }
        
        // Build inline styles with all CSS variables
        $container_style = sprintf(
            '--fcardm-height: %dpx; --fcardm-font-size: %dpx; --fcardm-front-start: %s; --fcardm-front-end: %s; --fcardm-back-start: %s; --fcardm-back-end: %s; --fcardm-text-color: %s; --fcardm-button-color: %s; --fcardm-radius: %dpx; --fcardm-width: %s; --fcardm-max-width: %s; --fcardm-card-padding: %dpx; --fcardm-container-padding: %dpx; --fcardm-container-margin: %dpx; --fcardm-text-align: %s;',
            intval($atts['height']),
            intval($atts['font_size']),
            esc_attr($atts['front_color']),
            esc_attr($atts['front_color_end']),
            esc_attr($atts['back_color']),
            esc_attr($atts['back_color_end']),
            esc_attr($atts['text_color']),
            esc_attr($atts['button_color']),
            intval($atts['border_radius']),
            esc_attr($atts['width']),
            esc_attr($max_width_css),
            intval($atts['card_padding']),
            intval($atts['container_padding']),
            intval($atts['container_margin']),
            esc_attr($text_align)
        );

        ob_start();
        ?>
        <div class="fcardm-flashcards" id="<?php echo esc_attr($unique_id); ?>" data-total="<?php echo esc_attr($total_cards); ?>" style="<?php echo esc_attr($container_style); ?>">
            
            <?php if ($show_title) : ?>
            <h3 class="fcardm-title"><?php echo esc_html($set->post_title); ?></h3>
            <?php endif; ?>
            
            <?php if ($show_counter && !$counter_bottom) : ?>
            <div class="fcardm-counter">
                <span class="fcardm-current">1</span> / <span class="fcardm-total"><?php echo esc_html($total_cards); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="fcardm-cards-wrapper">
                <?php foreach ($cards_list as $index => $card) : ?>
                <div class="fcardm-card <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr($index); ?>">
                    <div class="fcardm-card-inner">
                        <div class="fcardm-card-front">
                            <div class="fcardm-card-content">
                                <?php echo esc_html($card['question']); ?>
                            </div>
                            <div class="fcardm-card-hint">
                                <?php esc_html_e('Click to flip', 'flashcard-manager'); ?>
                            </div>
                        </div>
                        <div class="fcardm-card-back">
                            <div class="fcardm-card-content">
                                <?php echo esc_html($card['answer']); ?>
                            </div>
                            <div class="fcardm-card-hint">
                                <?php esc_html_e('Click to flip back', 'flashcard-manager'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($show_counter && $counter_bottom) : ?>
            <div class="fcardm-counter">
                <span class="fcardm-current">1</span> / <span class="fcardm-total"><?php echo esc_html($total_cards); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="fcardm-controls">
                <button type="button" class="fcardm-btn fcardm-prev" aria-label="<?php esc_attr_e('Previous card', 'flashcard-manager'); ?>">
                    <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                    <span><?php esc_html_e('Previous', 'flashcard-manager'); ?></span>
                </button>
                <?php if ($show_shuffle) : ?>
                <button type="button" class="fcardm-btn fcardm-shuffle" aria-label="<?php esc_attr_e('Shuffle cards', 'flashcard-manager'); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></svg>
                </button>
                <?php endif; ?>
                <button type="button" class="fcardm-btn fcardm-next" aria-label="<?php esc_attr_e('Next card', 'flashcard-manager'); ?>">
                    <span><?php esc_html_e('Next', 'flashcard-manager'); ?></span>
                    <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                </button>
            </div>
            
            <?php if ($show_progress) : ?>
            <div class="fcardm-progress">
                <div class="fcardm-progress-bar" style="width: <?php echo esc_attr((1 / $total_cards) * 100); ?>%"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
