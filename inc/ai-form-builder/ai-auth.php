<?php
/**
 * SureForms - AI Auth.
 *
 * @package sureforms
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class AI_Auth {
	use Get_Instance;

    public $key = "";

    public function initiate_auth() {

        $this->key = wp_generate_password(16, false);

        $token_data = array(
            "redirect-back" => site_url() . "/wp-admin/admin.php?page=add-new-form&method=ai",
            "key" => $this->key,
            // "key" => "Lg(pRHTc3PoQ0STZ",
            "site-url" => site_url(),
            "nonce" => wp_create_nonce('ai_auth_nonce')
        );
    
        // Convert to JSON and Base64 encode
        $encoded_token_data = base64_encode(json_encode($token_data));
    
        // Generate the auth URL
        $auth_url = "https://billing.sureforms.com/auth/?token=" . $encoded_token_data;
    
        wp_send_json_success($auth_url);
    }

    public function handle_access_key($request) {
        // get body data
        $body = json_decode($request->get_body(), true);

        // get access key
        $access_key = $body['accessKey'];
        $access_key = $body['accessKey'];

        if (!empty($access_key)) {
            $this->decrypt_access_key($access_key, $this->key,
                'AES-256-CBC', 0 );
        } else {
            wp_send_json_error("No access key provided.");
        }
    }
    
        /**
         * Decrypts a string using OpenSSL decryption.
         *
         * @param string $data The data to decrypt.
         * @param string $key The encryption key.
         * @param string $method The encryption method (e.g., AES-256-CBC).
         * @param int $options Encryption options (default is 0).
         * @return string|false The decrypted string or false on failure.
         */
        public function decrypt_access_key($data, $key, $method = 'AES-256-CBC', $options = 0)
        {
            // Decode the data and split IV and encrypted data
            $decodedData = base64_decode($data);
            if ($decodedData === false) {
                return false;
            }

            list($key, $encrypted) = explode('::', $decodedData, 2);

            // Decrypt the data
            $decrypted = openssl_decrypt($encrypted, $method, $key, $options, $key);

            if ($decrypted === false) {
                wp_send_json_error("Failed to decrypt the access key.");
            }

            // json decode the decrypted data
            $decrypted_email_array = json_decode($decrypted, true);

            if (empty($decrypted_email_array)) {
                wp_send_json_error("Failed to json decode the decrypted data.");
            }


            if (empty($decrypted_email_array['user_email'])) {
                wp_send_json_error("No user email found in the decrypted data.");
            }

            // verify the nonce that comes in $encrypted_email_array
            if (!empty($decrypted_email_array['nonce']) && !wp_verify_nonce($decrypted_email_array['nonce'], 'ai_auth_nonce')) {
                wp_send_json_error("Nonce verification failed.");
            }

            // verify the nonce
            $is_option_updated = update_option('srfm_ai_auth_user_email', $decrypted_email_array );

            wp_send_json_success($is_option_updated);
        }

    

    

}

