<?php 

//Blyz Code 
/**
 * to exclude field from notification add 'exclude[ID]' option to {all_fields} tag
 * 'include[ID]' option includes HTML field / Section Break field description / Signature image in notification
 * see http://www.gravityhelp.com/documentation/page/Merge_Tags for a list of standard options
 * example: {all_fields:exclude[2,3]}
 * example: {all_fields:include[6]}
 * example: {all_fields:include[6],exclude[2,3]}
 */
add_filter('gform_merge_tag_filter', 'all_fields_extra_options', 11, 5);
function all_fields_extra_options($value, $merge_tag, $options, $field, $raw_value)
{
    if ($merge_tag != 'all_fields') {
        return $value;
    }

    // usage: {all_fields:include[ID],exclude[ID,ID]}
    $include = preg_match("/include\[(.*?)\]/", $options, $include_match);
    $include_array = explode(',', rgar($include_match, 1));

    $exclude = preg_match("/exclude\[(.*?)\]/", $options, $exclude_match);
    $exclude_array = explode(',', rgar($exclude_match, 1));

    $log = "all_fields_extra_options(): {$field->label}({$field->id} - {$field->type}) - ";

    if ($include && in_array($field->id, $include_array)) {
        switch ($field->type) {
            case 'html':
                $value = $field->content;
                break;
            case 'section':
                $value .= sprintf('<tr bgcolor="#FFFFFF">
                                                        <td width="20">&nbsp;</td>
                                                        <td>
                                                            <font style="font-family: sans-serif; font-size:12px;">%s</font>
                                                        </td>
                                                   </tr>
                                                   ', $field->description);
                break;
            case 'signature':
                $url = GFSignature::get_signature_url($raw_value);
                $value = "<img alt='signature' src='{$url}'/>";
                break;
        }
        GFCommon::log_debug($log . 'included.');
    }
    if ($exclude && in_array($field->id, $exclude_array)) {
        GFCommon::log_debug($log . 'excluded.');
        return false;
    }
    return $value;
}

add_filter('gform_entry_field_value', 'bly_hide_field', 10, 4);
function bly_hide_field($value, $field, $lead, $form)
{
    if ($form['id'] == 19) {

        $Field_id_hide = [127,386,387,15,365,55,282,79,92,284,286];

        foreach ($Field_id_hide as $key => $id) {
            if (($field->id) == $id) {
                $value = '';
            }
        }

    }

    return $value;
}

function bly_scripts() {
    wp_enqueue_script( 'script-bly', get_stylesheet_directory_uri() . '/inc/bly-js.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'bly_scripts' );

add_action( 'gform_pre_submission_19', 'pre_submission_bly' );
function pre_submission_bly( $form ) {
    if(empty($_POST['input_305']) && empty($_POST['input_179']) ) {
    $_POST['input_305'] = rgpost('input_156'); //Username
    $_POST['input_179'] = rgpost('input_153'); //Email register
    }
}


add_action( 'gform_after_submission_19', 'send_entry_order_optimoroute', 10, 2 );
function send_entry_order_optimoroute( $entry, $form ) {
 
    $endpoint_url = 'https://api.optimoroute.com/v1/create_order?key=5b5b4ce639460dea46852cd5e876c5c6fFuw6Q3Qx0';
    $date = new DateTime(rgar( $entry, '131' ));
    $new_date_format = $date->format('Y-m-d');

    $time = rgpost('input_135' );
    $time_windows = (explode(" ",$time));
    // "orderNo" => "P".$entry['id'],

    $body = array(
        "operation" => "CREATE", 
        "orderNo" => "P".rgar( $entry, '172' ),
        "type" => "P",
        "date" => $new_date_format,

        "location" => array(
            "address" => rgar( $entry, '334.1' )." ".rgar( $entry, '334.2' )." ".rgar( $entry, '334.5' )." ".rgar( $entry, '334.3' ),
            "locationName" =>  rgar( $entry, '352.3' )." ".rgar( $entry, '352.6' ),
            "acceptPartialMatch" => true
        ),
        
        "twFrom" => $time_windows[0],
        "twTo" => $time_windows[2],
        "duration" => 20,
        "notes" => " Notes for the driver - collection :".rgar($entry,'345')." ".rgar($entry,'347')." ".rgar($entry,'348')." ".rgar($entry,'349'), 
        "email" => rgar($entry, '179'),
        "phone" => rgar($entry, '353'),
        "notificationPreference" => "sms"
        );
        

    $response = wp_remote_post( $endpoint_url, array( 'body' => json_encode($body) ) );

    
// if ( is_wp_error( $response ) ) {
//     $error_message = $response->get_error_message();
//     echo "Something went wrong: $error_message";
//     echo $body;
// } else {
//     echo 'Response:<pre>';
//     print_r( $response );
//     echo '</pre>';
//     echo 'Body:<pre>';
//     print_r( $body );
//     echo '</pre>';
// }
}


add_action( 'gform_after_submission_19', 'send_entry_delivery', 10, 2 );
function send_entry_delivery( $entry, $form ) {
 
    $endpoint_url = 'https://api.optimoroute.com/v1/create_order?key=5b5b4ce639460dea46852cd5e876c5c6fFuw6Q3Qx0';
    $date = new DateTime(rgar( $entry, '132' ));
    $new_date_format = $date->format('Y-m-d');

    $time = rgpost('input_136' );
    $time_windows = (explode(" ",$time));

    $body = array(
        "operation" => "CREATE", 
        "orderNo" => "D".rgar( $entry, '172' ),
        "type" => "D",
        "date" => $new_date_format,

        "location" => array(
            "address" => rgar( $entry, '334.1' )." ". rgar( $entry, '334.2' )." ". rgar( $entry, '334.5' )." ".rgar( $entry, '334.3' ),
            "locationName" =>  rgar( $entry, '352.3' )." ".rgar( $entry, '352.6' ),
            "acceptPartialMatch" => true
        ),
        "twFrom" => $time_windows[0],
        "twTo" => $time_windows[2],
        "duration" => 20,
        "notes" => " Notes for the driver - collection :".rgar($entry,'345')." ".rgar($entry,'347')." ".rgar($entry,'348')." ".rgar($entry,'349'), 
        "email" => rgar($entry, '179'),
        "phone" => rgar($entry, '353'),
        "notificationPreference" => "sms"
        );

        if($_POST['input_336'] == "Different address" ) {
            echo "Đăng ký";
            $body = array(
                "operation" => "CREATE", 
                "orderNo" => "D".rgar( $entry, '172' ),
                "type" => "D",
                "date" => $new_date_format,
        
                "location" => array(
                    "address" => rgar( $entry, '335.1' )." ". rgar( $entry, '335.2' )." ". rgar( $entry, '335.5' )." ".rgar( $entry, '335.3' ),
                    "locationName" =>  rgar( $entry, '352.3' )." ".rgar( $entry, '352.6' ),
                    "acceptPartialMatch" => true
                ),
                "twFrom" => $time_windows[0],
                "twTo" => $time_windows[2],
                "duration" => 20,
                "notes" => "Notes for the driver - delivery: ".rgar($entry,'325')." ".rgar($entry,'326')." ".rgar($entry,'327')." ".rgar($entry,'329'),
                "email" => rgar($entry, '179'),
                "phone" => rgar($entry, '353'),
                "notificationPreference" => "sms"
                );
        }

    $response = wp_remote_post( $endpoint_url, array( 'body' => json_encode($body) ) );
}

add_filter( 'gplc_remove_choices_19_135', '__return_false' );
add_filter( 'gplc_remove_choices_19_136', '__return_false' );

add_action('wp_head', 'pinterest_pixel_base_code');
function pinterest_pixel_base_code() { ?>

   <!-- Pinterest Tag -->
<script>
!function(e){if(!window.pintrk){window.pintrk = function () {
window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var
  n=window.pintrk;n.queue=[],n.version="3.0";var
  t=document.createElement("script");t.async=!0,t.src=e;var
  r=document.getElementsByTagName("script")[0];
  r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
pintrk('load', '2613498796658', {em: '<user_email_address>'});
pintrk('page');
</script>
<noscript>
<img height="1" width="1" style="display:none;" alt=""
  src="https://ct.pinterest.com/v3/?event=init&tid=2613498796658&pd[em]=<hashed_email_address>&noscript=1" />
</noscript>
<!-- end Pinterest Tag -->
<script>
pintrk('track', 'pagevisit');
</script>

<?php }

?>