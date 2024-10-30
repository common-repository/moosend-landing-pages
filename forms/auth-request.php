<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
add_action('admin_post_auth-form', 'moosend_landings_auth_get');
function moosend_landings_auth_get()
{
    if(isset($_POST['logout'])) {
        $logout = sanitize_text_field( $_POST['logout']);
        if($logout == 'false'){
            update_option( 'moosend_landing_api_key', false);
            wp_redirect( $_SERVER['HTTP_REFERER'] );
            delete_option('moosend_landing_user_site');
            exit();
        }
    }
	$email = sanitize_text_field( $_POST['moosend_landing_email']);
    $email_encoded = urlencode($email);
    $pass = sanitize_text_field( $_POST['moosend_landing_password']);
    $pass_encoded = urlencode($pass);
    $moosend_auth_gateway = 'https://authentication.m-operations.com/api/user/authenticate.json?usernameOrEmail=' . $email_encoded . '&password=' . $pass_encoded;
    // Send the request
    $response = wp_remote_get($moosend_auth_gateway);
    $responseBody = json_decode($response['body'], true);
    $responseApiKey = $responseBody['ApiKey'];
    $responseUserId = $responseBody['UserId'];
    $responseTeamMemberId = $responseBody['TeamMemberId'];
    $jsonResponseCode = $response['response']['code'];
    $landing_api_key = get_option('moosend_landing_api_key');
    $createAuthErrorOption = add_option( 'moosend_landing_auth_error', 'No', '', 'no' );

    if($jsonResponseCode == 200 ){
        update_option( 'moosend_landing_auth_error', 'No');
        if(!isset($landing_api_key)){
            add_option( 'moosend_landing_api_key', $responseApiKey, '', 'no' );
            add_option( 'moosend_landing_user_id', $responseUserId, '', 'no' );
            add_option( 'moosend_landing_email', $email, '', 'no' );
            add_option( 'moosend_landing_team_member_id', $responseTeamMemberId, '', 'no' );
        }else{
            update_option( 'moosend_landing_api_key', $responseApiKey);
	        update_option( 'moosend_landing_user_id', $responseUserId);
            update_option( 'moosend_landing_email', $email);
            update_option( 'moosend_landing_team_member_id', $responseTeamMemberId);
        }
        wp_redirect( admin_url('edit.php?post_type=moosend_landing&page=moosend-landing-importer') );
        exit();
    }else{
        update_option( 'moosend_landing_auth_error', 'Yes');
        if(!isset($landing_api_key)){
            add_option( 'moosend_landing_api_key', false, '', 'no' );
            add_option( 'moosend_landing_user_id', false, '', 'no' );
            add_option( 'moosend_landing_team_member_id', false, '', 'no' );
        }else{
            update_option( 'moosend_landing_api_key', false);
            update_option( 'moosend_landing_user_id', false);
            update_option( 'moosend_landing_team_member_id', false);
        }
        wp_redirect( admin_url('edit.php?post_type=moosend_landing&page=moosend-authentication') );
        exit();
    }
}
