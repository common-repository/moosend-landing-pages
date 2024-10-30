<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
add_action('admin_post_submit-form', 'moosend_landing_create_landing');
function moosend_landing_create_landing($landingName){
    $landingId = sanitize_key( $_POST['websiteId']);
    $landingName = sanitize_text_field( $_POST['landingName']);
    $apiKey = sanitize_key( $_POST['landingApiKey']);
    $postarr = array(
        'page_template' => 'template file',
        'post_content' => 'placeholder for the landing content',
        'post_status' => 'draft',
        'post_title' => $landingName,
        'meta_input' => array( 'MoosendWebsiteId' => $landingId, 'landingApiKey' => $apiKey ),
        'post_type' => 'moosend_landing');

    //Get tags of the entity
    $moosend_tags_gateway = 'https://gateway.services.moosend.com/websites/entities/' . $landingId . '/with-extras?format=json';
    //Send the request
    $response = wp_remote_get( $moosend_tags_gateway, array(
        'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey) ));
    $jsonResponse = json_decode($response['body'], true);
    $tags = $jsonResponse['Tags'];
    $landing_tags= [];
    foreach ($tags as $tag) {
        $landing_tags[] = [$tag['Tag']];
    }
    foreach ($landing_tags as $landing_tag) {
        $landings_tag_str .= json_encode($landing_tag);
        $landing_tag .= $landing_tag;
    }
    //we add the wp imported tag to the tags of the entity
    $landing_tag_wp_import = $landings_tag_str .= '"wp imported"';
    $landings_tag_st_left = str_replace('[', '', $landing_tag_wp_import);
    //list of current tags
    $landings_tag_clean= str_replace(']', ',', $landings_tag_st_left);

    //Creating the wp imported tag
    $moosend_gateway_imported = 'https://gateway.services.moosend.com/websites/entities/' . $landingId .'/tags';
    // Send the request
    $response_post = wp_remote_post( $moosend_gateway_imported, array(
        'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey),
        'body' => '{Tags: [ ' . $landings_tag_clean .']}',
    ));
    //Create post and redirect
    $post_id = wp_insert_post($postarr);
    //wp_redirect( get_permalink($post_id) );
    wp_redirect( admin_url('post.php?post=' . $post_id . '&action=edit') );
};
