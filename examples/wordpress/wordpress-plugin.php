<?php
/**
 * Plugin Name: Secure AI Integration
 * Description: Secure AI API Integration for WordPress
 * Version: 1.0.0
 * Author: Volkan Sah
 * Text Domain: secure-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load API key from environment or wp-config.php
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

/**
 * Handle AJAX request for AI generation
 */
add_action('wp_ajax_ai_request', 'handle_ai_request');

function handle_ai_request() {
    // Verify nonce for CSRF protection
    check_ajax_referer('ai_nonce', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Input sanitization
    $user_input = sanitize_textarea_field($_POST['prompt']);
    
    // Validate input length
    if (strlen($user_input) > 4000) {
        wp_send_json_error(['message' => 'Prompt too long']);
        return;
    }
    
    // Rate limiting via transients (1 request per minute per user)
    $user_id = get_current_user_id();
    $rate_key = "ai_rate_$user_id";
    
    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'Rate limit exceeded. Please wait.']);
        return;
    }
    
    set_transient($rate_key, true, 60); // 60 seconds
    
    // Make API call
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $user_input]
            ],
            'max_tokens' => 1000
        ])
    ]);
    
    // Error handling
    if (is_wp_error($response)) {
        error_log('AI API Error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'API request failed']);
        return;
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        error_log('AI API HTTP Error: ' . $http_code);
        wp_send_json_error(['message' => 'API returned error']);
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Output sanitization (allow safe HTML tags)
    $ai_response = wp_kses_post($data['choices'][0]['message']['content']);
    
    wp_send_json_success(['response' => $ai_response]);
}

/**
 * Enqueue scripts with nonce
 */
add_action('wp_enqueue_scripts', 'enqueue_ai_scripts');

function enqueue_ai_scripts() {
    wp_enqueue_script('ai-handler', plugins_url('js/ai-handler.js', __FILE__), ['jquery'], '1.0.0', true);
    
    wp_localize_script('ai-handler', 'aiConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_nonce')
    ]);
}
