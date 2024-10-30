<?php
/**
 * The form to be loaded on the plugin's admin page
 */
if( current_user_can( 'edit_users' ) ) {
    $apiKey = get_option('moosend_landing_api_key');
    $userId = get_option('moosend_landing_user_id');
    //how many landing pages do we have
    $moosend_body_type = [
        'WhereEntityType' => '2',
        'WhereEntityStatus' => '1'
    ];
    $moosend_gateway_count = 'https://gateway.services.moosend.com/websites/entities/count';
    $endpoint_count = $moosend_gateway_count;
    // Send the request
    $response_count = wp_remote_post( $endpoint_count, array(
        'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey),
        'body' => json_encode($moosend_body_type),
    ));
    $jsonResponse_count = json_decode($response_count['body'], true);

    //Look for all landing pages
    $moosend_body = [
        'WhereEntityType' => '2',
        'WhereEntityStatus' => '1',
        'PageSize' => $jsonResponse_count,
        'OrderBy' => 3
    ];
    $moosend_gateway = 'https://gateway.services.moosend.com/websites/entities/filter';
    $endpoint = $moosend_gateway;
    // Send the request
    $response = wp_remote_post( $endpoint, array(
        'headers' => array('Content-Type' => 'application/json', 'x-apikey' => $apiKey),
        'body' => json_encode($moosend_body),
    ));
    $jsonResponse = json_decode($response['body'], true);
    $totalLandings = $jsonResponse['TotalItems'];
    $landing = $jsonResponse["Items"];

    ?>
    <style>
        /* Table CSS */
        #landing_name_col, td:first-child{
            padding-left: 20px;
        }
        #moosend-importer-table{
            margin-top: 50px;
        }
        tr{
            vertical-align: middle;
        }

        /* ENDTable CSS */

        /* TITLE CSS */
        p, h1{
            text-align: center;
        }
        #moosend-logo{
            display: block;
            margin: 10px auto;
            width: 250px;
            height: auto;
            padding-top: 50px;
        }
        input{
            display: inline-block;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-size: .875rem;
            font-family: Helvetica,sans-serif;
            padding: 5px 20px;
            cursor: pointer;
            outline: 0;
            background-color: #5ccdde;
            border: 1px solid transparent;
            color: #fff;
            margin: 0px;
        }
        /* END TITLE CSS */

        /* NO LANDINGS CSS */
        #no-landings-img{
            display: block;
            margin: 20px auto;
        }
        #createNewLanding{
            display: block;
            margin: 0px auto;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-size: .875rem;
            font-family: Helvetica,sans-serif;
            padding: 10px 45px;
            cursor: pointer;
            outline: 0;
            background-color: #5ccdde;
            border: 1px solid transparent;
            color: #fff;
        }
        #h1NoLandings{
            margin-bottom: 19px;
            font-size: 1.75rem;
        }
    </style>
    <div class="wrap">
        <img id="moosend-logo" src="<?php echo(plugins_url('assets/img/moosend_logo_full.svg', __DIR__));?>">
    <?php if($totalLandings == null): ?>
    <?php
        $moosend_auth_gateway_subdomain = 'https://gateway.services.moosend.com/site/user-id/'. $userId;
        $response_subdomain = wp_remote_get( $moosend_auth_gateway_subdomain, array(
            'headers' => array('Accept' => 'application/json', 'x-apikey' => $apiKey) ));
        $jsonResponse_subdomain = json_decode($response_subdomain['body'], true);
        $subdomain = $jsonResponse_subdomain['Name'];
    ?>
        <img id="no-landings-img" src="<?php echo(plugins_url('assets/img/no-landings.png', __DIR__));?>"/>
        <h1 id="h1NoLandings">No Landing Pages</h1>
        <button id="createNewLanding"><a rel="nofollow noreferrer noopener" style="color: white" target="_blank" href="http://<?php echo($subdomain)?>.moosend.com/#/lead-generation/landing-pages/settings/page-settings">Create New</a></button>
    <?php else: ?>
        <h1>Your Moosend Landing Pages</h1>
        <p>You have <?php echo(esc_html($totalLandings)); ?> landing pages, to import them just click in the <strong>Import</strong> button</p>
        <table id="moosend-importer-table" class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th scope="col" id="landing_name_col" class="manage-column column-title column-primary sortable desc">Landing Name</th>
                <th scope="col" id="status_col" class="manage-column">Status</th>
                <th scope="col" id="pageviews_col" class="manage-column">Total Page Views</th>
                <th scope="col" id="conversions_col" class="manage-column">Total Conversions</th>
                <th scope="col" id="conversion_rate_col" class="manage-column">Conversion Rate</th>
                <th scope="col" id="action_col" class="manage-column">Action</th>
            </tr>
            </thead>
            <tbody id="the-list">
            <?php for($i = 0; $i < $totalLandings; $i++): ?>
                <tr>
                    <td><?php echo(esc_html($landing[$i]['Entity']['Name'])); ?></td>
                    <td><?php if($landing[$i]['Entity']['Status'] == 1){echo('Published');}else{echo('Unpublished');}; ?> and <?php if(strpos(json_encode($landing[$i]['Tags']), 'wp imported') == false){echo('has not been imported');}else{ echo('you already imported it');}; ?> </td>
                    <td><?php echo(number_format($landing[$i]['Entity']['TotalPageViews'])); ?></td>
                    <td><?php echo(number_format($landing[$i]['Entity']['TotalConversions']));?></td>
                    <td><strong><?php echo(round($landing[$i]['Entity']['ConversionPercentage']*100)) . '%';  ?></strong></td>
                    <td><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                            <input name='action' type="hidden" value='submit-form'>
                            <input type="hidden" id="websiteId" name="websiteId" value="<?php echo(esc_attr($landing[$i]['Entity']['WebsiteId'])); ?>">
                            <input type="hidden" id="landingName" name="landingName" value="<?php echo(esc_attr($landing[$i]['Entity']['Name'])); ?>">
                            <input type="hidden" id="userId" name="userId" value="<?php echo(esc_attr($landing[$i]['Entity']['UserId'])); ?>">
                            <input type="hidden" id="landingApiKey" name="landingApiKey" value="<?php echo(esc_attr($apiKey)); ?>">
                            <?php if($landing[$i]['Entity']['Status'] == 0): ?>
                                <input style="pointer-events: none" type="submit" value="Unpublished <?php if(strpos(json_encode($landing[$i]['Tags']), 'wp imported') != false){echo(' and Imported');}; ?>" name="btn">
                            <?php else: ?>
                                <input <?php if(strpos(json_encode($landing[$i]['Tags']), 'wp imported') != false){echo('style="pointer-events: none"');}?> type="submit" value="<?php if(strpos(json_encode($landing[$i]['Tags']), 'wp imported') == false){echo('Import');}else{ echo('Imported');}; ?>" name="btn">
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
    <?php
}
else {
    ?>
    <p> <?php __("You are not authorized to perform this operation.", $this->plugin_name) ?> </p>
    <?php
}
