<?php
if ( !defined( 'ABSPATH' ) ) exit;
include(get_stylesheet_directory().'/inc/functions_bly_efe.php');

function my_login_redirect($redirect_to, $request, $user)
{
    //is there a user to check?
    if (isset($user->roles) && is_array($user->roles)) {
        //check for admins
        if (in_array('administrator', $user->roles)) {
            // redirect them to the default place
            return $redirect_to;
        } else {
            return home_url();
        }
    } else {
        return $redirect_to;
    }
}

add_filter('login_redirect', 'my_login_redirect', 10, 3);

add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
function theme_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/assets/css/custom.css',
        array('parent-style'),
        time(),
        'all'
    );
}

// Postcode Validation in booking form

add_filter('gform_field_validation_19_142', 'custom_postcode_validation', 10, 4);
function custom_postcode_validation($result, $value, $form, $field)
{
    if ($result['is_valid']) {
        $acceptable_postcodes = array(
            'NW3',
            'NW7',
            'N12',
            'N2',
            'N20',
            'NW11',

        );

        $is_valid = false;

        foreach ($acceptable_postcodes as $postcode) {
            if (strpos(strtolower($value), strtolower($postcode)) === 0) {
                $is_valid = true;
                break;
            }
        }

        if (!$is_valid) {
            $result['is_valid'] = false;
            $result['message'] = 'Sorry, we are not serving this area yet.';
        }
    }

    return $result;
}


add_filter('gform_field_validation_19_334', 'custom_zip_validation', 10, 4);
add_filter('gform_field_validation_19_335', 'custom_zip_validation', 10, 4);
function custom_zip_validation($result, $value, $form, $field)
{
    if ($result['is_valid']) {
        $acceptable_zips = array(
            'NW3',
            'NW7',
            'N12',
            'N2',
            'N20',
            'NW11',

        );

        $is_valid = false;
        $zip_value = rgar($value, $field->id . '.5');
        foreach ($acceptable_zips as $postcode) {
            if (strpos(strtolower($zip_value), strtolower($postcode)) === 0) {
                $is_valid = true;
                break;
            }
        }

        if (!$is_valid) {
            $result['is_valid'] = false;
            $result['message'] = 'Sorry, we are not serving this area yet.';
        }
    }

    return $result;
}

add_filter('gform_validation_19', function ($validation_result) {
    $form = $validation_result['form'];

    //supposing we don't want input 1 to be a value of 86
    if (rgpost('gform_source_page_number_19') == 4 && !is_user_logged_in()) {
        $is_conditional_user = rgpost('is_conditional_user');

        $usernameObj = null;
        $usernameKey = null;
        $passwordObj = null;
        $passwordKey = null;

        $conditionUserId = null;
        foreach ($form['fields'] as $key => &$field) {

            if (strpos($field->cssClass, 'login_username') !== false) {
                $usernameObj = $field;
                $usernameKey = $key;
            }
            if (strpos($field->cssClass, 'login_password') !== false) {
                $passwordObj = $field;
                $passwordKey = $key;
            }
            if (strpos($field->cssClass, 'is_conditional_user') !== false) {
                $conditionUserId = $field->id;
            }
            if ($usernameObj != null && $passwordObj != null && $conditionUserId != null) {
                break;
            }
        }

        $logic = rgpost('input_' . $conditionUserId);

        if ($logic == 'Login') {
            $validation_result['is_valid'] = false;
            if (!empty(rgpost('input_' . $usernameObj->id)) && !empty(rgpost('input_' . $passwordObj->id))) {
                $username = rgpost('input_' . $usernameObj->id);
                $password = rgpost('input_' . $passwordObj->id);
                $wp_authenticate = wp_authenticate($username, $password);
                if (!is_wp_error($wp_authenticate)) {
                    $user_id = $wp_authenticate->ID;
                    clean_user_cache($user_id);
                    wp_clear_auth_cookie();
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id, true, false);
                    update_user_caches($wp_authenticate);
                    $validation_result['is_valid'] = true;
                } else {
                    $form['fields'][$usernameKey]->failed_validation = true;
                    $form['fields'][$usernameKey]->validation_message = 'Your username/email is incorrect.';

                    $form['fields'][$passwordKey]->failed_validation = true;
                    $form['fields'][$passwordKey]->validation_message = 'Your password is incorrect.';
                }
            } else {
                $form['fields'][$usernameKey]->failed_validation = true;
                $form['fields'][$usernameKey]->validation_message = 'Please enter your username or email.';

                $form['fields'][$passwordKey]->failed_validation = true;
                $form['fields'][$passwordKey]->validation_message = 'Please enter your password.';
            }
        }
    }

    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;
});

add_filter('gform_review_page_19', 'add_review_page', 10, 3);
function add_review_page($review_page, $form, $entry)
{

    // Enable the review page
    $review_page['is_enabled'] = true;

    if ($entry) {
        // Populate the review page.

        $review_page['content'] = GFCommon::replace_variables('{all_fields:exclude[127,386,387,15,365,55,282]}', $form, $entry);
    }

    return $review_page;
}

add_filter("gform_pre_render_19", "gform_skip_page");
add_filter("gform_pre_render_21", "gform_skip_page");
function gform_skip_page($form)
{
    $current_page = GFFormDisplay::get_current_page($form['id']);

    if ($current_page == '1' && is_user_logged_in())
        GFFormDisplay::$submission[$form['id']]["page_number"] = '2';
    return $form;
}


add_filter('gpecf_order_sumary_markup_19', 'get_custom_order_summary_markup', 10, 6);
function get_custom_order_summary_markup($markup, $order, $form, $entry, $order_summary, $labels)
{
    ob_start();

    //    var_dump($order['products']['80']);
?>

    <table class="gpecf-order-summary" cellspacing="0" width="100%" style="<?php gp_ecommerce_fields()->style('.order-summary'); ?>">
        <thead>
            <tr>
                <th scope="col" style="<?php gp_ecommerce_fields()->style('.order-summary/thead/th.column-1'); ?>"><?php echo $labels['product']; ?></th>
                <th scope="col" style="<?php gp_ecommerce_fields()->style('.order-summary/thead/th.column-2'); ?>"><?php echo $labels['quantity']; ?></th>
                <th scope="col" style="<?php gp_ecommerce_fields()->style('.order-summary/thead/th.column-3'); ?>"><?php echo $labels['unit_price']; ?></th>
                <th scope="col" style="<?php gp_ecommerce_fields()->style('.order-summary/thead/th.column-4'); ?>"><?php echo $labels['price']; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['products'] as $key => $product) :
                if (empty($product['name']) || gp_ecommerce_fields()->is_ecommerce_product($product)) {
                    continue;
                }

                if ($key == 199 || $key == 200 || $key == 291 || $key == 202 || $key == 213) {
                } else {
            ?>

                    <tr style="<?php gp_ecommerce_fields()->style('.order-summary/tbody/tr'); ?>">
                        <td style="<?php gp_ecommerce_fields()->style('.order-summary/tbody/tr/td.column-1'); ?>">
                            <div style="<?php gp_ecommerce_fields()->style('.order-summary/.product-name'); ?>">
                                <?php echo esc_html($product['name']); ?>
                            </div>
                            <ul style="<?php gp_ecommerce_fields()->style('.order-summary/.product-options'); ?>">
                                <?php
                                $price = GFCommon::to_number($product['price']);
                                if (is_array(rgar($product, 'options'))) :
                                    foreach ($product['options'] as $index => $option) :
                                        $class = $index == count($product['options']) - 1 ? '.last-child' : '';
                                        if ($option['id'] == 311 || $option['id'] == 187 || $option['id'] == 186 || $option['id'] == 185 || $option['id'] == 184 || $option['id'] == 290) {
                                        } else {
                                ?>
                                            <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li{$class}"); ?>"><?php echo $option['option_label'] ?></li>
                                    <?php }
                                    endforeach;
                                endif;
                                $field_total = floatval($product['quantity']) * $price;
                                $product_total = floatval($product['quantity']) * GFCommon::to_number($product['price']);
                                if ($key == 80 && !empty($order['products'][199])) :
                                    ?>
                                    <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li.last-child"); ?>"><?php echo esc_html($order['products'][199]['name']); ?></li>

                                <?php endif;
                                if ($key == 82 && !empty($order['products'][200])) :
                                ?>
                                    <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li.last-child"); ?>"><?php echo esc_html($order['products'][200]['name']); ?></li>

                                <?php endif;
                                if ($key == 93 && !empty($order['products'][291])) :
                                ?>
                                    <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li.last-child"); ?>"><?php echo esc_html($order['products'][291]['name']); ?></li>

                                <?php endif;
                                if ($key == 97 && !empty($order['products'][202])) :
                                ?>
                                    <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li.last-child"); ?>"><?php echo esc_html($order['products'][202]['name']); ?></li>

                                <?php endif;
                                if ($key == 210 && !empty($order['products'][213])) :
                                ?>
                                    <li style="<?php gp_ecommerce_fields()->style(".order-summary/.product-options/li.last-child"); ?>"><?php echo esc_html($order['products'][213]['name']); ?></li>

                                <?php endif; ?>
                            </ul>
                        </td>
                        <td style="<?php gp_ecommerce_fields()->style('.order-summary/tbody/tr/td.column-2'); ?>"><?php echo esc_html($product['quantity']); ?>
                            <ul style="<?php gp_ecommerce_fields()->style('.order-summary/.product-options'); ?>; margin-left: 0 !important;">
                                <?php
                                if (is_array(rgar($product, 'options'))) :
                                    foreach ($product['options'] as $index => $option) :
                                        $class = $index == count($product['options']) - 1 ? '.last-child' : '';
                                        if ($option['id'] == 311 || $option['id'] == 187 || $option['id'] == 186 || $option['id'] == 185 || $option['id'] == 184 || $option['id'] == 290) {
                                        } else {
                                ?>
                                            <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;">&nbsp;
                                            </li>
                                    <?php }
                                    endforeach;
                                endif;
                                if ($key == 80 && !empty($order['products'][199])) :
                                    ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 0;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;"><?php echo esc_html($order['products'][199]['quantity']); ?></li>

                                <?php endif;
                                if ($key == 82 && !empty($order['products'][200])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 0;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;"><?php echo esc_html($order['products'][200]['quantity']); ?></li>

                                <?php endif;
                                if ($key == 93 && !empty($order['products'][291])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 0;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;"><?php echo esc_html($order['products'][291]['quantity']); ?></li>

                                <?php endif;
                                if ($key == 97 && !empty($order['products'][202])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 0;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;"><?php echo esc_html($order['products'][202]['quantity']); ?></li>

                                <?php endif;
                                if ($key == 210 && !empty($order['products'][213])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 0;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;"><?php echo esc_html($order['products'][213]['quantity']); ?></li>

                                <?php endif; ?>
                            </ul>
                        </td>
                        <td style="<?php gp_ecommerce_fields()->style('.order-summary/tbody/tr/td.column-3'); ?>"><?php
                                                                                                                    if ($key == 126 || $key == 119) {
                                                                                                                        echo '';
                                                                                                                    } else {
                                                                                                                        echo GFCommon::to_money($product['price'], $entry['currency']);
                                                                                                                    } ?>
                            <ul style="<?php gp_ecommerce_fields()->style('.order-summary/.product-options'); ?>" class="gray-ul-mobile">
                                <?php
                                if (is_array(rgar($product, 'options'))) :
                                    foreach ($product['options'] as $index => $option) :
                                        $class = $index == count($product['options']) - 1 ? '.last-child' : '';
                                        if ($option['id'] == 311 || $option['id'] == 187 || $option['id'] == 186 || $option['id'] == 185 || $option['id'] == 184 || $option['id'] == 290) {
                                        } elseif ($option['price'] == 0) {
                                ?>
                                            <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile">&nbsp;
                                            </li>
                                        <?php
                                        } else {
                                        ?>
                                            <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($option['price'], $entry['currency']); ?></li>
                                    <?php }
                                    endforeach;
                                endif;
                                if ($key == 80 && !empty($order['products'][199])) :
                                    ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo esc_html($order['products'][199]['price']); ?></li>

                                <?php endif;
                                if ($key == 82 && !empty($order['products'][200])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo esc_html($order['products'][200]['price']); ?></li>

                                <?php endif;
                                if ($key == 93 && !empty($order['products'][291])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo esc_html($order['products'][291]['price']); ?></li>

                                <?php endif;
                                if ($key == 97 && !empty($order['products'][202])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo esc_html($order['products'][202]['price']); ?></li>

                                <?php endif;
                                if ($key == 210 && !empty($order['products'][213])) :
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo esc_html($order['products'][213]['price']); ?></li>

                                <?php endif; ?>
                            </ul>
                        </td>
                        <td style="<?php gp_ecommerce_fields()->style('.order-summary/tbody/tr/td.column-4'); ?>"><?php
                                                                                                                    if ($key == 126 || $key == 119) {
                                                                                                                        echo '';
                                                                                                                    } else {
                                                                                                                        echo GFCommon::to_money($product_total, $entry['currency']);
                                                                                                                    } ?>
                            <ul style="<?php gp_ecommerce_fields()->style('.order-summary/.product-options'); ?>" class="gray-ul-mobile">
                                <?php
                                if (is_array(rgar($product, 'options'))) :
                                    foreach ($product['options'] as $index => $option) :
                                        $class = $index == count($product['options']) - 1 ? '.last-child' : '';
                                        $option_total = floatval($product['quantity']) * $option['price'];
                                        if ($option['id'] == 311 || $option['id'] == 187 || $option['id'] == 186 || $option['id'] == 185 || $option['id'] == 184 || $option['id'] == 290) {
                                        } elseif ($option_total == 0) {
                                ?>
                                            <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile">&nbsp;
                                            </li>
                                        <?php
                                        } else {
                                        ?>
                                            <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 2px 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($option_total, $entry['currency']); ?></li>
                                    <?php }
                                    endforeach;
                                endif;
                                if ($key == 80 && !empty($order['products'][199])) :
                                    $pro_total = floatval($order['products'][199]['quantity']) * GFCommon::to_number($order['products'][199]['price']);
                                    ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($pro_total, $entry['currency']); ?></li>

                                <?php endif;
                                if ($key == 82 && !empty($order['products'][200])) :
                                    $pro_total = floatval($order['products'][200]['quantity']) * GFCommon::to_number($order['products'][200]['price']);
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($pro_total, $entry['currency']); ?></li>

                                <?php endif;
                                if ($key == 93 && !empty($order['products'][291])) :
                                    $pro_total = floatval($order['products'][291]['quantity']) * GFCommon::to_number($order['products'][291]['price']);
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($pro_total, $entry['currency']); ?></li>

                                <?php endif;
                                if ($key == 97 && !empty($order['products'][202])) :
                                    $pro_total = floatval($order['products'][202]['quantity']) * GFCommon::to_number($order['products'][202]['price']);
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($pro_total, $entry['currency']); ?></li>

                                <?php endif;
                                if ($key == 210 && !empty($order['products'][213])) :
                                    $pro_total = floatval($order['products'][213]['quantity']) * GFCommon::to_number($order['products'][213]['price']);
                                ?>
                                    <li style="overflow: hidden;
    margin: 0 0 0 2px !important;
    padding: 0 0 6px 16px;
    color: #555;
    line-height: 1.5;
    list-style-type: none !important;" class="gray-li-mobile"><?php echo GFCommon::to_money($pro_total, $entry['currency']); ?></li>

                                <?php endif; ?>
                            </ul>
                        </td>
                    </tr>
            <?php }
            endforeach;
            ?>
        </tbody>
        <tfoot style="<?php gp_ecommerce_fields()->style('.order-summary/tfoot'); ?>">
            <?php foreach (gp_ecommerce_fields()->get_order_summary($order, $form, $entry) as $index => $group) : ?>
                <?php foreach ($group as $item) :
                    $class = rgar($item, 'class') ? '.' . rgar($item, 'class') : '';
                ?>
                    <tr style="<?php gp_ecommerce_fields()->style('.order-summary/tfoot/tr' . $class); ?>">
                        <?php if ($index === 0) : ?>
                            <td style="<?php gp_ecommerce_fields()->style('.order-summary/tfoot/tr/td.empty'); ?>" colspan="2" rowspan="<?php echo gp_ecommerce_fields()->get_order_summary_item_count($order_summary); ?>"></td>
                        <?php endif; ?>
                        <td style="<?php gp_ecommerce_fields()->style(".order-summary/tfoot/{$class}/td.column-3"); ?>"><?php echo $item['name']; ?></td>
                        <td style="<?php gp_ecommerce_fields()->style(".order-summary/tfoot/{$class}/td.column-4"); ?>"><?php echo GFCommon::to_money($item['price'], $entry['currency']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tfoot>
    </table>

<?php
    return ob_get_clean();
}

add_filter('gform_merge_tag_filter', 'gray_add_filter', 10, 5);

function gray_add_filter($field_value, $merge_tag, $options, $field, $raw_field_value)
{
    if (
        $field->id == 284 ||
        $field->id == 79 ||
        $field->id == 92 ||
        $field['id'] == 286 ||
        $field['id'] == 303 ||
        $field['id'] == 176 ||
        $field['id'] == 177 ||
        $field['id'] == 178 ||
        $field['id'] == 178 ||
        //        $field['id'] == 152 ||
        //        $field['id'] == 153 ||
        //        $field['id'] == 154 ||
        $field['id'] == 155 ||
        $field['id'] == 156 ||
        $field['id'] == 157 ||
        $field['id'] == 310 ||
        $field['id'] == 145 ||
        $field['id'] == 146 ||
        $field['id'] == 142 ||
        $field['id'] == 179 ||
        $field['id'] == 305
    ) {
        return '';
    } else {
        return $field_value;
    }
}

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar()
{
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}

add_filter('gform_review_page_19', 'change_review_page_button', 10, 3);
add_filter('gform_review_page_21', 'change_review_page_button', 10, 3);
function change_review_page_button($review_page, $form, $entry)
{

    $review_page['nextButton']['text'] = 'Review your order';

    return $review_page;
}
echo xxx;
echo xxx;
echo xxx;
echo xxx;