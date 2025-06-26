<?php

namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Util;

class User extends \EasyCMS_WP\Template\Component
{
    private $number_of_tries = 0;

    public function hooks()
    {

        //$this->set_sync_status( false );

        add_action('user_register', array($this, 'register_user'));
        add_action('rest_api_init', array($this, 'register_api'));
        add_action('profile_update', array($this, 'update_profile'), 10, 2);

        add_filter('easycms_wp_prepare_order_request', array($this, 'add_order_customer_data'), 10, 2);
        add_filter('woocommerce_after_checkout_validation', array($this, 'updateCustomerFromCms'), 10, 2);
        add_filter('easycms_wp_set_order_item_data', array($this, 'set_order_product_fields'), 10, 4);

        add_filter('easycms_wp_prepare_order_params', array($this, 'set_customer_data_in_order'), 10, 3);
    }

    public function register_api()
    {
        register_rest_route(self::API_BASE, $this->get_module_name(), array(
            'methods' => 'POST',
            'permission_callback' => array($this, 'rest_check_auth'),
            'callback' => array($this, 'rest_add_user'),
            'args' => array(
                'customer_id' => array(
                    'validate_callback' => array($this, 'rest_validate_id'),
                    'sanitize_callback' => 'absint',
                    'required' => true,
                ),
                'email' => array(
                    'sanitize_callback' => 'sanitize_email',
                    'required' => true,
                ),
            )
        ));

        register_rest_route(self::API_BASE, $this->get_module_name() . '/delete', array(
            'methods' => 'POST',
            'permission_callback' => array($this, 'rest_check_auth'),
            'callback' => array($this, 'rest_delete_user'),
            'args' => array(
                'customer_id' => array(
                    'validate_callback' => array($this, 'rest_validate_id'),
                    'sanitize_callback' => 'absint',
                    'required' => true,
                ),
            )
        ));
    }

    public function fail_safe()
    {

    }

    public function update_profile(int $user_id, \WP_User $old_user_data)
    {
        if ($this->get_cms_id($user_id)) {
            $this->log(
                sprintf(
                    __('Updating user (%d) profile data', 'easycms-wp'),
                    $user_id
                ),
                'info'
            );

            $data = (array)$this->get_cms_data($user_id);
            $data['customer_password'] = $data['password'];
            unset($data['password']);
            $data = $this->set_cms_params($user_id, false, $data);

            $this->log(
                sprintf(
                    __('###### CASE 1::update_profile $user->data %s', 'easycms-wp'),
                    json_encode($data)
                ),
                'info'
            );
            // $this->log(
            // 	sprintf(
            // 		__( '###### CASE 1::update_profile $user->data->user_pass %s', 'easycms-wp' ),
            // 		$data['customer_password']
            // 	),
            // 	'info'
            // );

            if ($data) {
                $request = $this->make_request('/set_customer_update_profile_wp', 'POST', $data);
                if (is_wp_error($request)) {
                    $this->log(
                        sprintf(
                            __('Error updating user data to CMS: %s', 'easycms-wp'),
                            $request->get_error_message()
                        ),
                        'error'
                    );
                } else {
                    $payload = json_decode($request);
                    if (!empty($payload->OUTPUT)) {
                        if ('error' == $payload->OUTPUT->response_type) {
                            $this->log(
                                sprintf(
                                    __('Error updating customer profile: %s', 'easycms-wp'),
                                    $payload->OUTPUT->message
                                ),
                                'error'
                            );
                        } else if ('success' == $payload->OUTPUT->response_type) {
                            $this->log(__('Profile updated successfully', 'easycms-wp'), 'info');
                            $data['password'] = $data['customer_password'];
                            unset($data['customer_password']);

                            remove_action('profile_update', array($this, 'update_profile'), 10, 2);
                            $this->save_user_meta($user_id, (object)$data);
                            add_action('profile_update', array($this, 'update_profile'), 10, 2);
                        } else {
                            $this->log(
                                __('Unable to update profile. Unknown response format', 'easycms-wp'),
                                'error'
                            );
                        }
                    } else {
                        $this->log(
                            __('Error updating profile. Unknown response format', 'easycms-wp'),
                            'error'
                        );
                    }
                }
            }
        }
    }

    public function set_customer_data_in_order($wc_order, $params, $is_updating)
    {
        if (null === $wc_order) {
            return $wc_order;
        }

        if (!empty($params['customer_id'])) {
            $this->log(
                sprintf(
                    __('Setting customer data for order #%d', 'easycms-wp'),
                    $wc_order->get_id()
                ),
                'info'
            );

            $user = $this->get_user($params['customer_id']);
            if ($user) {
                $wc_order->set_customer_id($user->ID);
                $wc_order->set_billing_address_1(get_user_meta($user->ID, 'billing_address_1', true));
                $wc_order->set_billing_address_2(get_user_meta($user->ID, 'billing_address_2', true));
                $wc_order->set_billing_city(get_user_meta($user->ID, 'billing_city', true));
                $wc_order->set_billing_state(get_user_meta($user->ID, 'billing_state', true));
                $wc_order->set_billing_country(get_user_meta($user->ID, 'billing_country', true));
                $wc_order->set_billing_postcode(get_user_meta($user->ID, 'billing_postcode', true));

                $wc_order->set_shipping_address_1(get_user_meta($user->ID, 'shipping_address_1', true));
                $wc_order->set_shipping_address_2(get_user_meta($user->ID, 'shipping_address_2', true));
                $wc_order->set_shipping_city(get_user_meta($user->ID, 'shipping_city', true));
                $wc_order->set_shipping_state(get_user_meta($user->ID, 'shipping_state', true));
                $wc_order->set_shipping_country(get_user_meta($user->ID, 'shipping_country', true));
                $wc_order->set_shipping_postcode(get_user_meta($user->ID, 'shipping_postcode', true));
            } else {
                $this->log(
                    sprintf(
                        __('Customer not synced: Unable to find user on WP for the customer_id %d. Aborting...', 'easycms-wp'),
                        $params['customer_id']
                    ),
                    'error'
                );

                if (!$is_updating)
                    $wc_order->delete();
                $wc_order = null;

                return $wc_order;
            }
        }

        if (!empty($params['country_id'])) {
            $country = $this->get_country_by_id($params['country_id']);
            if (!$country) {
                $this->log(
                    sprintf(
                        __('Billing country ID %d not found', 'easycms-wp'),
                        $params['country_id']
                    ),
                    'error'
                );

                if (!$is_updating)
                    $wc_order->delete();
                $wc_order = null;

                return $wc_order;
            } else {
                $wc_order->set_billing_country($country);
            }
        }

        if (!empty($params['city_id'])) {
            $city = $this->get_city_by_id($params['city_id']);
            if (!$city) {
                $this->log(
                    sprintf(
                        __('Billing city ID %d not found', 'easycms-wp'),
                        $params['city_id']
                    ),
                    'error'
                );

                if (!$is_updating)
                    $wc_order->delete();
                $wc_order = null;

                return $wc_order;
            } else {
                $wc_order->set_billing_city($country);
            }
        }

        if (!empty($params['address'])) {
            $wc_order->set_billing_address_1($params['address']);
        }

        if (!empty($params['address_2'])) {
            $wc_order->set_billing_address_2($params['address_2']);
        }

        if (!empty($params['postal'])) {
            $wc_order->set_billing_postcode($params['postal']);
        }

        $wc_order->set_billing_email($params['email']);
        $wc_order->set_billing_phone($params['phone_full']);

        if (!empty($params['shipping_country_id'])) {
            $country = ($params['shipping_country_id'] != $params['country_id']
                ?
                $this->get_country_by_id($params['shipping_country_id'])
                :
                $country);
            if (!$country) {
                $this->log(
                    sprintf(
                        __('Shipping country ID %d not found', 'easycms-wp'),
                        $params['shipping_country_id']
                    ),
                    'error'
                );

                if (!$is_updating)
                    $wc_order->delete();
                $wc_order = null;

                return $wc_order;
            } else {
                $wc_order->set_shipping_country($country);
            }
        }

        if (!empty($params['shipping_city_id'])) {
            $city = ($params['city_id'] != $params['shipping_city_id'] ? $this->get_city_by_id($params['shipping_city_id']) : $city);
            if (!$city) {
                $this->log(
                    sprintf(
                        __('Shipping city ID %d not found', 'easycms-wp'),
                        $params['shipping_city_id']
                    ),
                    'error'
                );

                if (!$is_updating)
                    $wc_order->delete();
                $wc_order = null;

                return $wc_order;
            } else {
                $wc_order->set_shipping_city($country);
            }
        }

        if (!empty($params['shipping_address'])) {
            $wc_order->set_shipping_address_1($params['shipping_address']);
        }

        if (!empty($params['shipping_address_2'])) {
            $wc_order->set_shipping_address_2($params['shipping_address_2']);
        }

        if (!empty($params['shipping_postal'])) {
            $wc_order->set_shipping_postcode($params['shipping_postal']);
        }

        return $wc_order;
    }

    public function set_order_product_fields($product_data, \WC_Product $product, $wc_order_item, $wc_order)
    {
        if (null == $product_data) {
            return $product_data;
        }

        $cms_id = $this->get_cms_id($wc_order->get_customer_id());

        if ($cms_id) {
            $product_data['customer_id'] = $cms_id;
            $product_data['ignore_customer_pricing'] = 0;
        } else {
            $this->log(
                sprintf(
                    __('User CMS data was not found. CMS ID: %d', 'easycms-wp'),
                    $cms_id
                ),
                'error'
            );
            $product_data = null;
        }

        return $product_data;
    }

    public function isUserInCms($email)
    {
        $limit = 50;

        $req = $this->make_request('/get_customer_exists', 'POST', array('email' => $email));

        $payload = json_decode($req, true);
        if ($payload) {
            if (!empty($payload['OUTPUT']) && $payload['OUTPUT']['TOTAL_COUNT'] != 0) {
                return true;
            }
        }
        return false;
    }

    public function updateCustomerFromCms($data, $errors)
    {

        global $woocommerce;

        $email = $woocommerce->customer->get_billing_email();

        $cms_data = $this->isUserInCms($email);

        if ($cms_data && !email_exists($email)) {
            $req = $this->make_request('/get_customers', 'POST', array(
                'start' => 0,
                'limit' => 1,
                'email' => $email,
            ));

            if (is_wp_error($req)) {
                $this->log(
                    sprintf(
                        __('WP error getting unsyced users from CMS: %s', 'easycms-wp'),
                        $req->get_error_message()
                    ),
                    'error'
                );
                return false;
            }

            $payload = json_decode($req, true);
            if ($payload) {
                if (!empty($payload['OUTPUT'])) {
                    $payload['OUTPUT'] = array_filter($payload['OUTPUT'], 'is_numeric', ARRAY_FILTER_USE_KEY);
                    $this->register_wp_user($payload['OUTPUT'][0]);
                }
            }
            $password_reset = wp_lostpassword_url(wc_get_checkout_url());
            $login_url = get_permalink(wc_get_page_id('myaccount'));
            $errors->add('validation', __("An account is already registered with your email address.  <a href='" . $login_url . "'>Please log in.</a> or reset your password  <a href='" . $password_reset . "'>here </a> please reset your password here</a>", 'easycms-wp'));
        }
    }


    public function add_order_customer_data($post_data, \WC_Order $wc_order)
    {
        if (null === $post_data) {
            return $post_data;
        }

        $cms_id = $this->get_cms_id($wc_order->get_customer_id());

        if ($cms_id) {
            $user = get_user_by('id', $wc_order->get_customer_id());
            $data = $this->get_cms_data($wc_order->get_customer_id());

            if ($data) {
                $post_data['customer_id'] = $data->customer_id;
                $post_data['email'] = $data->email;
                $post_data['customer_password'] = $data->password;
                $post_data['phone'] = $data->phone;
                $post_data['phone_full'] = $data->phone_full;
                $post_data['phone_prefix'] = '+358';
                $post_data['city_id'] = $data->city_id;
                $post_data['country_id'] = $data->country_id;
                $post_data['customer_language_id'] = $data->language_id;
                $post_data['customer_language'] = $data->language;
                $post_data['customer_discount'] = $data->discount;
            } else {
                $this->log(
                    sprintf(
                        __('User CMS data was not found. CMS ID: %d', 'easycms-wp'),
                        $cms_id
                    ),
                    'error'
                );

                $post_data = null;
            }
        } else {
            $this->log(
                sprintf(
                    __('Unable to find CMS user ID. User not synced', 'easycms-wp')
                ),
                'error'
            );
            if ($this->$number_of_tries == 0) {
                $this->$number_of_tries++;
                $this->log(
                    sprintf(
                        __('Trying to call the sync_to_cms() function now...' . ($this->$number_of_tries), 'easycms-wp')
                    ),
                    'info'
                );
                $this->sync_to_cms($wc_order->get_customer_id());
                $this->add_order_customer_data($post_data, $wc_order);
            }

            $post_data = null;
        }

        return $post_data;
    }

    public function sync()
    {

        add_filter('send_password_change_email', '__return_false');
        add_filter('wpmu_signup_user_notification', '__return_false');

        if ($this->is_syncing()) {
            $this->log(__('Sync already running. Cannot start another', 'easycms-wp'), 'error');
            return;
        }

        ignore_user_abort(true);
        set_time_limit(0);

        $not_in = $this->get_synced_users();
        $not_in = array_map(array($this, 'get_cms_id'), $not_in);
        $not_in = array_filter($not_in);

        $customers = $this->get_cms_unsynced_users($not_in);

        $this->log(__('===SYNC STARTED===', 'easycms-wp'), 'info');
        $this->set_sync_status(true);
        $page = 0;
        $customers_that_could_not_be_synced_from_CMS = array();
        while ($customers) {
            $page++;

            $this->log(
                sprintf(
                    __('Page: %d, total fetched customers: %d', 'easycms-wp'),
                    $page,
                    count($customers)
                ),
                'info'
            );

            foreach ($customers as $customer) {

                $user_id = $this->register_wp_user($customer);
                if (empty($user_id)) {
                    $customers_that_could_not_be_synced_from_CMS[] = $customer['email'];
                    $this->log(
                        sprintf(
                            __('This customer could not be synced to WP from CMS: %s: ', 'easycms-wp'),
                            json_encode($customer['email'])
                        ),
                        'error'
                    );
                }
            }

            $this->log(
                sprintf(
                    __('Fetching other customers to be synced using this method: get_cms_unsynced_users', 'easycms-wp')
                ),
                'info'
            );
            $customers = $this->get_cms_unsynced_users($not_in);
        }

        if (!empty($customers_that_could_not_be_synced_from_CMS)) {
            $this->log(
                sprintf(
                    __('Customers that could not be synced to WP from CMS are: %s', 'easycms-wp'),
                    json_encode($customers_that_could_not_be_synced_from_CMS)
                ),
                'error'
            );
        }
        $this->log(__('===SYNC ENDED===', 'easycms-wp'), 'info');
        $this->set_sync_status(false);

        ignore_user_abort(false);
    }

    public function rest_add_user(\WP_REST_Request $request)
    {
        $params = $request->get_params();

        $user_id = $this->register_wp_user($params);

        if ($user_id) {
            return $this->rest_response(array('user_id' => $user_id));
        }

        return $this->rest_response(array(), 'FAIL');
    }

    public function rest_delete_user(\WP_REST_Request $request)
    {
        $customer_id = $request['customer_id'];

        $user = $this->get_user($customer_id);
        if (!$user) {
            $this->log(
                sprintf(
                    __('Error deleting user. Customer account does not exist', 'easycms-wp')
                ),
                'error'
            );
        } else {
            require_once ABSPATH . '/wp-admin/includes/user.php';
            wp_delete_user($user->ID);
            $this->log(
                sprintf(
                    __('Customer account %d deleted successfully', 'easycms-wp'),
                    $customer_id
                ),
                'error'
            );
        }

        return $this->rest_response('');
    }

    public function sync_to_cms($argument_WP_user_id = false)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $users = $this->get_unsync_users();
        if ($users) {
            $this->log(__('===SYNC STARTED===', 'easycms-wp'), 'info');
            $active_lang = \EasyCMS_WP\Util::get_default_language();
            if (!$active_lang) {
                $this->log(__('Unable to retrieve site default language. Aborting sync...', 'easycms-wp'), 'error');
                return;
            }

            $this->log(
                sprintf(
                    __('Retrieving default language ID', 'easycms-wp')
                ),
                'info'
            );

            $language_id = $this->get_language_id($active_lang);
            if (!$language_id) {
                $this->log(
                    sprintf(
                        __('Unable to retrieve language ID for %s. Aborting sync', 'easycms-wp'),
                        $active_lang
                    ),
                    'error'
                );

                return;
            }

            foreach ($users as $user_id) {
                if (!empty($argument_WP_user_id)) {### if a specific user id was passed to the argument only add that user to CMS
                    if ($argument_WP_user_id == $user_id) {
                        $this->register_user($user_id, array('language_id' => $language_id, 'language' => $act));
                    }
                } else {
                    $this->register_user($user_id, array('language_id' => $language_id, 'language' => $act));
                }
            }

            $this->log(__('===SYNC ENDED===', 'easycms-wp'), 'info');
        }

        ignore_user_abort(false);
    }

    public function set_params(array $params)
    {

        // $this->log(
        // 	sprintf(
        // 		__( '###### CASE 3::set_params  $params %s', 'easycms-wp' ),
        // 		json_encode($params)
        // 	),
        // 	'info'
        // );

        $args = array(
            'meta' => array(),
            'role' => 'customer',
        );

        if (isset($params['customer_id'])) {
            $user = $this->get_user($params['customer_id']);

            if ($user) {
                $args['ID'] = $user->ID;
            }
        }

        if (isset($params['email']) && filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            $args['user_email'] = $params['email'];
            $args['user_login'] = $params['email'];
        }

        if (isset($params['firstname'])) {
            $args['first_name'] = $params['firstname'];
            $args['meta']['billing_first_name'] = $params['firstname'];
        }

        if (isset($params['lastname'])) {
            $args['last_name'] = $params['lastname'];
            $args['meta']['billing_last_name'] = $params['lastname'];
        }

        if (isset($params['language'])) {
            $args['locale'] = $params['language'];
        }

        if (isset($params['password'])) {
            if (!empty($params['password'])) {
                $args['user_pass'] = $params['password'];
            } else {
                $args['user_pass'] = wp_generate_password(12, false, false);
                $args['is_password_generated'] = true;
            }
        }

        if (isset($params['company'])) {
            $args['meta']['billing_company'] = $params['company'];
            $args['meta']['shipping_company'] = $params['company'];
        }

        if (isset($params['address'])) {
            $args['meta']['billing_address_1'] = $params['address'];
        }

        if (isset($params['address_2'])) {
            $args['meta']['billing_address_2'] = $params['address_2'];
        }

        if (isset($params['city_id'])) {
            $args['meta']['billing_city'] = $params['city_id'];
        }

        if (isset($params['postal'])) {
            $args['meta']['billing_postcode'] = $params['postal'];
        }

        if (isset($params['state'])) {
            $args['meta']['billing_state'] = $params['state'];
        }

        if (!empty($params['city_id'])) {
            $args['meta']['billing_city'] = $this->get_city_by_id($params['city_id']);
        }

        if (!empty($params['country_id'])) {
            $args['meta']['billing_country'] = $this->get_country_by_id($params['country_id']);
        }

        if (isset($params['billing_email'])) {
            $args['meta']['billing_email'] = $params['billing_email'];
        }

        if (isset($params['billing_phone'])) {
            $args['meta']['billing_phone'] = $params['billing_phone'];
        }

        if (isset($params['shipping_address'])) {
            $args['meta']['shipping_address'] = $params['shipping_address'];
        }

        if (isset($params['shipping_postal'])) {
            $args['meta']['shipping_postcode'] = $params['shipping_postal'];
        }

        if (isset($params['shipping_state'])) {
            $args['meta']['shipping_state'] = $params['shipping_state'];
        }

        if (!empty($params['shipping_country_id'])) {
            if (!empty($params['country_id']) && $params['shipping_country_id'] == $params['country_id']) {
                $args['meta']['shipping_country'] = $args['meta']['billing_country'];
            } else {
                $args['meta']['shipping_country'] = $this->get_country_by_id($params['shipping_country_id']);
            }
        }

        if (!empty($params['shipping_city_id'])) {
            if (!empty($params['city_id']) && $params['city_id'] == $params['shipping_city_id']) {
                $args['meta']['shipping_city'] = $args['meta']['billing_city'];
            } else {
                $args['meta']['shipping_city'] = $this->get_city_by_id($params['shipping_city_id']);
            }
        }

        return $args;
    }

    public function get_country_by_id(int $id)
    {
        $req = $this->make_request('/get_countries', 'POST', array('country_id' => $id));

        if (is_wp_error($req)) {
            $this->log(
                sprintf(
                    __('Error getting country data by id: %s', 'easycms-wp'),
                    $req->get_error_message()
                ),
                'error'
            );

            return false;
        }

        $data = json_decode($req);
        if ($data) {
            if (!empty($data->OUTPUT)) {
                return $data->OUTPUT[0]->country_iso_code;
            }

            $this->log(
                sprintf(
                    __('No country was found with the country id %d', 'easycms-wp'),
                    $id
                ),
                'error'
            );

            return false;
        }

        $this->log(
            sprintf(
                __('Unable to decode JSON data while getting country', 'easycms-wp')
            ),
            'error'
        );
    }

    public function get_city_by_id(int $id)
    {


        $req = $this->make_request('/get_cities', 'POST', array('city_id' => $id));

        if (is_wp_error($req)) {
            $this->log(
                sprintf(
                    __('Error getting city data by id: %s', 'easycms-wp'),
                    $req->get_error_message()
                ),
                'error'
            );

            return false;
        }

        $data = json_decode($req);
        if ($data) {
            if (!empty($data->OUTPUT)) {
                $city_data = $data->OUTPUT[0];
                $city_name = (array)$city_data->city_name;
                $city_name = Util::strip_locale($city_name);
                $default_lang = Util::get_default_language();

                if (!empty($city_name[$default_lang])) {
                    return $city_name[$default_lang];
                }

                $this->log(
                    sprintf(
                        __('No city name for the site translation', 'easycms-wp')
                    ),
                    'error'
                );

                return false;
            }

            $this->log(
                sprintf(
                    __('No city was found with the city id %d', 'easycms-wp'),
                    $id
                ),
                'error'
            );

            return false;
        }

        $this->log(
            sprintf(
                __('Unable to decode JSON data while getting city', 'easycms-wp')
            ),
            'error'
        );
    }

    private function set_cms_password($user_id, $args)
    {
        // var_dump($args);
        // $this->log(
        // 	sprintf(
        // 		__( '###### in set_cms_password args are %s', 'easycms-wp' ),
        // 		json_encode($args)
        // 	),
        // 	'error'
        // );
        if (!empty($args['is_password_generated'])) {
            $user = get_user_by('id', $user_id);
            // $this->log(
            // 	sprintf(
            // 		__( '###### CASE 2::set_cms_password  $user->data->user_pass %s', 'easycms-wp' ),
            // 		$user->data->user_pass
            // 	),
            // 	'info'
            // );


            if ($user) {
                $req = $this->make_request('/set_customer_update_profile_wp', 'POST', array(
                    'email' => $user->data->user_email,
                    'customer_password' => $user->data->user_pass
                ));

                if (is_wp_error($req)) {
                    $this->log(
                        sprintf(
                            __('Error updating customer password on CMS: %s', 'easycms-wp'),
                            $req->get_error_message()
                        ),
                        'error'
                    );
                } else {
                    $data = json_decode($req);
                    if (!$data) {
                        $this->log(
                            sprintf(
                                __('Error decoding JSON payload: %s', 'easycms-wp'),
                                $req
                            )
                            , 'error');
                    } else if (empty($data->OUTPUT)) {
                        $this->log(__('Unknown response from API', 'error'));
                    } else if ($data->OUTPUT->response_type == 'error') {
                        $this->log(
                            sprintf(
                                __('Unable to update user password on CMS: %s', 'easycms-wp'),
                                $data->OUTPUT->message
                            ),
                            'error'
                        );
                    } else {
                        $this->log(
                            __('User password updated successfully', 'easycms-wp'),
                            'info'
                        );

                        $message = "Hi,\n\nYour new generated login password is {$args['user_pass']}\n\nThanks";
                        // wp_mail(
                        // 	$user->data->user_email,
                        // 	__( 'Notice of New Password', 'easycms-wp' ),
                        // 	$message,
                        // 	array(
                        // 		'Content-Type: text/plain',
                        // 		'From: ' . get_bloginfo( 'admin_email' )
                        // 	)
                        // );
                    }
                }
            }
        }
    }

    public function register_wp_user(array $data)
    {

        $this->log(
            sprintf(
                __('Creating user with customer_id %d on WP', 'easycms-wp'),
                $data['customer_id']
            ),
            'info'
        );

        $args = $this->set_params($data);

        if (!empty($args['ID'])) {
            $this->log(__('Customer already exists. Updating...', 'easycms-wp'), 'info');
        }

        remove_action('user_register', array($this, 'register_user'));
        remove_action('profile_update', array($this, 'update_profile'), 10, 2);
        $user_id = wp_insert_user($args);
        add_action('user_register', array($this, 'register_user'));
        add_action('profile_update', array($this, 'update_profile'), 10, 2);

        if (is_wp_error($user_id)) {
            $this->log(
                sprintf(
                    __('Error creating/updating user on WP: %s', 'easycms-wp'),
                    $user_id->get_error_message()
                ),
                'error'
            );

            return false;
        }

        if (!empty($args['meta'])) {
            foreach ($args['meta'] as $key => $value) {
                if (!add_user_meta($user_id, $key, $value, true)) {
                    update_user_meta($user_id, $key, $value);
                }
            }
        }

        $this->set_cms_password($user_id, $args);
        $this->save_user_meta($user_id, (object)$data);

        $this->log(
            sprintf(
                __('User successfully created/updated on WP with ID: %d', 'easycms-wp'),
                $user_id
            ),
            'info'
        );

        return $user_id;
    }

    public function get_unsync_users(int $limit = 50)
    {
        $args = array(
            'meta_key' => 'easycms_wp_id',
            'meta_value' => '',
            'meta_compare' => 'NOT EXISTS',
            'fields' => 'ID',
        );

        $query = new \WP_User_Query($args);

        return $query->get_results();
    }

    public function get_synced_users()
    {
        $args = array(
            'meta_key' => 'easycms_wp_id',
            'meta_value' => '',
            'meta_compare' => 'EXISTS',
            'fields' => 'ID',
        );

        $query = new \WP_User_Query($args);

        return $query->get_results();
    }

    public function get_cms_unsynced_users(array $not_in)
    {
        static $page = 1;
        $limit = 50;

        $req = $this->make_request('/get_customers', 'POST', array(
            'NOT_IN' => $not_in,
            'start' => $limit * ($page - 1),
            'limit' => $limit,
            'no_profile_image' => 1,
        ));

        $page++;

        if (is_wp_error($req)) {
            $this->log(
                sprintf(
                    __('Error getting unsyced users from CMS: %s', 'easycms-wp'),
                    $req->get_error_message()
                ),
                'error'
            );
            return false;
        }

        $payload = json_decode($req, true);
        if ($payload) {
            if (!empty($payload['OUTPUT'])) {
                $payload['OUTPUT'] = array_filter($payload['OUTPUT'], 'is_numeric', ARRAY_FILTER_USE_KEY);
                return $payload['OUTPUT'];
            }

            return false;
        }

        $this->log(
            sprintf(
                __('Error while decoding JSON payload: %s', 'easycms-wp'),
                $req
            ),
            'error'
        );
        return false;
    }


    public function get_country_id($iso)
    {
        $page = 1;
        $limit = 50;
        $offset = $limit * ($page - 1);
        $id = 0;

        $request = $this->make_request('/get_countries', 'POST', array('start' => $offset, 'limit' => $limit));

        while (!is_wp_error($request)) {
            $data = json_decode($request);
            if (!$data) {
                $this->log(
                    __('Error getting countries: Unable to parse request response payload', 'easycms-wp'),
                    'error'
                );
                break;
            }

            if (!empty($data->OUTPUT)) {
                foreach ($data->OUTPUT as $country_data) {
                    if (strtolower($country_data->country_iso_code) == strtolower($iso)) {
                        $id = $country_data->country_id;
                        break 2;
                    }
                }
            } else {
                break;
            }

            $page++;
            $offset = $limit * ($page - 1);
            $request = $this->make_request('/get_countries', 'POST', array('start' => $offset, 'limit' => $limit));
        }

        return $id;
    }

    private function get_city_id(int $country_id, string $name, string $lang = '')
    {
        $page = 1;
        $limit = 50;
        $offset = $limit * ($page - 1);
        $id = 0;

        if (!$lang) {
            $lang = Util::get_default_language();
        }

        $request = $this->make_request('/get_cities', 'POST', array('start' => $offset, 'limit' => $limit));

        while (!is_wp_error($request)) {
            $data = json_decode($request, true);

            if (!$data) {
                $this->log(
                    __('Error getting cities: Unable to parse request response payload', 'easycms-wp'),
                    'error'
                );
                break;
            }

            if (!empty($data['OUTPUT'])) {
                foreach ($data['OUTPUT'] as $city_data) {
                    $city_data['city_name'] = Util::strip_locale($city_data['city_name']);
                    if ($city_data['country_id'] == $country_id) {
                        if (
                            isset($city_data['city_name'][$lang]) &&
                            strtolower($city_data['city_name'][$lang]) == strtolower($name)
                        ) {
                            $id = $city_data['city_id'];
                            break 2;
                        }
                    }
                }
            } else {
                break;
            }

            $page++;
            $offset = $limit * ($page - 1);
            $request = $this->make_request('/get_cities', 'POST', array('start' => $offset, 'limit' => $limit));
        }

        return $id;
    }

    public function get_CMS_WP_customer_relation()
    {
        ### relations columns for CMS => in WP
        return array(
            'firstname' => array('first_name', 'last_name'),
            'lastname' => 'billing_company',
            'company' => 'billing_company',
            'country' => 'billing_country',
            'city' => 'billing_city',
            'email' => 'billing_email',
            'address' => 'billing_address_1',
            'state' => 'billing_state',
            'postal' => 'billing_postcode',
            'phone' => 'billing_phone',
            'phone_full' => 'billing_phone',
            'billing_email' => 'billing_email',
            'billing_phone' => 'billing_phone',
            'billing_phone_full' => 'billing_phone',

            'ordering_email' => 'billing_email',
            'ordering_phone' => 'billing_phone',
            'ordering_phone_full' => 'billing_phone',

            'different_shipping_address' => 'shipping_address_1',
            'shipping_country' => 'shipping_country',
            'shipping_city' => 'shipping_city',
            'shipping_address' => array('shipping_address_1', 'shipping_address_2'),
            // 'shipping_address_2' => isset($_POST['shipping_address_2']) ? $_POST['shipping_address_2'] :  get_user_meta( $user_id, 'shipping_address_2', true ),
            'shipping_postal' => 'shipping_postcode',
            'delivery_contact_person' => array('shipping_first_name', 'shipping_last_name', 'shipping_company'),
        );
    }

    public function set_cms_params(int $user_id, bool $new_user = false, array $data = array())
    {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        if ($new_user) {
            $this->log(
                sprintf(
                    __('Received new registered user request %d:%s. Proceeding to sync', 'easycms-wp'),
                    $user->ID,
                    $user->data->user_email
                ),
                'info'
            );

            $active_lang = \EasyCMS_WP\Util::get_default_language();

            if (!$active_lang) {
                $this->log(__('Unable to retrieve site default language. Aborting sync...', 'easycms-wp'), 'error');
                return;
            }

            $data['language_id'] = $this->get_language_id($active_lang);
            $data['language'] = $active_lang;

            if (!$data['language_id']) {
                $this->log(
                    sprintf(
                        __('in /set_cms_params/ Unable to retrieve language ID for %s. Aborting sync', 'easycms-wp'),
                        $active_lang
                    ),
                    'error'
                );

                return;
            }

            $_usr_profile_for_CMS = $this->get_CMS_WP_customer_relation();

            foreach ($_usr_profile_for_CMS as $usrk => $usrv) {
                switch ($usrk) {
                    case 'firstname':
                    case 'shipping_address':
                    case 'delivery_contact_person':
                        if (isset($_POST[$usrv[0]])) $data[$usrk] = rtrim(trim(($_POST[$usrv[0]] ?? '') . ' ' . ($_POST[$usrv[1]] ?? '') . ' ' . ($_POST[$usrv[2]] ?? '')));
                        break;
                    case 'different_shipping_address':
                        if (isset($_POST[$usrv])) $data[$usrk] = isset($_POST[$usrv]) && !empty($_POST[$usrv]) ? 1 : 0;
                        break;
                    default:
                        if (isset($_POST[$usrv])) $data[$usrk] = isset($_POST[$usrv]) ? $_POST[$usrv] : '';
                }

            }

            if (!empty($_POST['billing_country'])) {
                $country_id = $this->get_country_id($_POST['billing_country']);
                if (!$country_id) {
                    $this->log(
                        sprintf(
                            __('Error in add user > retrieving country ID for country: %s', 'easycms-wp'),
                            $_POST['billing_country']
                        ),
                        'error'
                    );
                    return;
                }
                $data['country_id'] = $country_id;
            }
            if (!empty($_POST['billing_city'])) {
                if (empty($data['country_id'])) {
                    $this->log(
                        sprintf(
                            __('Error in add user > Unable to set city_id for city %s. Country_ID is missing', 'easycms-wp'),
                            $_POST['billing_city']
                        ),
                        'error'
                    );
                    return;
                }
                $city_id = $this->get_city_id($data['country_id'], $_POST['billing_city']);
                if (!$city_id) {
                    $data['city_id'] = 'new_city: ' . $_POST['billing_city'];
                } else {
                    $data['city_id'] = $city_id;
                }
            }

            if (!empty($_POST['shipping_country'])) {
                $country_id = $this->get_country_id($_POST['shipping_country']);
                if (!$country_id) {
                    $this->log(
                        sprintf(
                            __('Error in add user >  retrieving country ID for country: %s', 'easycms-wp'),
                            $_POST['shipping_country']
                        ),
                        'error'
                    );
                    return;
                }
                $data['shipping_country_id'] = $country_id;
            }
            if (!empty($_POST['shipping_city'])) {
                if (empty($data['shipping_country_id'])) {
                    $this->log(
                        sprintf(
                            __('Error in add user > Unable to set city_id for city %s. Country_ID is missing', 'easycms-wp'),
                            $_POST['shipping_city']
                        ),
                        'error'
                    );
                    return;
                }
                $city_id = $this->get_city_id($data['country_id'], $_POST['shipping_city']);
                if (!$city_id) {
                    $data['shipping_city_id'] = 'new_city: ' . $_POST['shipping_city'];
                } else {
                    $data['shipping_city_id'] = $city_id;
                }
            }


            $data['email'] = $user->data->user_email;
            $data['customer_password'] = $user->data->user_pass;
        } else {

            $WP_udata = get_user_meta($user_id);
            // convert to single "key > value" instead of "key > [0:value]"
            if (!empty($WP_udata)) {
                foreach ($WP_udata as $uk => &$uv) {
                    if (isset($_POST[$uk])) {
                        $uv = $_POST[$uk];
                        continue;
                    }
                    $uv = $uv[key($uv)] ?? $uv;
                }

                /*$_usr_profile_for_CMS = array(
                    'firstname' => $WP_udata['first_name'].' '.$WP_udata['last_name'],
                    'lastname' => $WP_udata['billing_company'],
                    'company' => $WP_udata['billing_company'],
                    'country' => $WP_udata['billing_country'],
                    'city' => $WP_udata['billing_city'],
                    'email' => $WP_udata['billing_email'],
                    'address' => $WP_udata['billing_address_1'],
                    'state' => $WP_udata['billing_state'],
                    'postal' => $WP_udata['billing_postcode'],
                    'phone' => $WP_udata['billing_phone'],
                    'phone_full' => $WP_udata['billing_phone'],
                    'billing_email' => $WP_udata['billing_email'],
                    'billing_phone' => $WP_udata['billing_phone'],
                    'billing_phone_full' => $WP_udata['billing_phone'],

                    'ordering_email' => $WP_udata['billing_email'],
                    'ordering_phone' => $WP_udata['billing_phone'],
                    'ordering_phone_full' => $WP_udata['billing_phone'],

                    'different_shipping_address' => isset($WP_udata['shipping_address_1']) && !empty($WP_udata['shipping_address_1']) ? 1 : 0,
                    'shipping_country' => $WP_udata['shipping_country'],
                    'shipping_city' => $WP_udata['shipping_city'],
                    'shipping_address' => $WP_udata['shipping_address_1'].' '.$WP_udata['shipping_address_2'],
                    // 'shipping_address_2' => isset($_POST['shipping_address_2']) ? $_POST['shipping_address_2'] :  get_user_meta( $user_id, 'shipping_address_2', true ),
                    'shipping_postal' => $WP_udata['shipping_postcode'],
                    'delivery_contact_person' => rtrim($WP_udata['shipping_first_name'].' '.$WP_udata['shipping_last_name'].' '.$WP_udata['shipping_company']),
                );
                foreach ($_usr_profile_for_CMS as $bk => $bv) {
                    $data[$bk] = $bv;
                }*/

                $_usr_profile_for_CMS = $this->get_CMS_WP_customer_relation();
                foreach ($_usr_profile_for_CMS as $usrk => $usrv) {
                    switch ($usrk) {
                        case 'firstname':
                        case 'shipping_address':
                        case 'delivery_contact_person':
                            $data[$usrk] = rtrim(trim(($WP_udata[$usrv[0]] ?? '') . ' ' . ($WP_udata[$usrv[1]] ?? '') . ' ' . ($WP_udata[$usrv[2]] ?? '')));
                            break;
                        case 'different_shipping_address':
                            $data[$usrk] = isset($WP_udata[$usrv]) && !empty($WP_udata[$usrv]) ? 1 : 0;
                            break;
                        default:
                            $data[$usrk] = isset($WP_udata[$usrv]) ? $WP_udata[$usrv] : '';
                    }

                }

                ### _usr_profile_for_CMS city and country
                if (!empty($data['country'])) {
                    $country_id = $this->get_country_id($data['country']);
                    if (!$country_id) {
                        $this->log(
                            sprintf(
                                __('Error in update user >  retrieving country ID for country: %s', 'easycms-wp'),
                                $data['country']
                            ),
                            'error'
                        );

                        return;
                    }

                    $data['country_id'] = $country_id;
                }

                if (!empty($data['city'])) {
                    if (empty($data['city_id'])) {
                        $this->log(
                            sprintf(
                                __('Error in update user > Unable to set city_id for city %s. Country_ID is missing', 'easycms-wp'),
                                $data['city']
                            ),
                            'error'
                        );

                        return;
                    }

                    $city_id = $this->get_city_id($data['country_id'], $data['city']);

                    if (!$city_id) {
                        $data['city_id'] = 'new_city: ' . $data['city'];
                    } else {
                        $data['city_id'] = $city_id;
                    }
                }

                ### shipping city and country
                if (!empty($data['shipping_country'])) {
                    $country_id = $this->get_country_id($data['shipping_country']);
                    if (!$country_id) {
                        $this->log(
                            sprintf(
                                __('Error in update user > retrieving country ID for shipping_country: %s', 'easycms-wp'),
                                $data['shipping_country']
                            ),
                            'error'
                        );

                        return;
                    }

                    $data['shipping_country_id'] = $country_id;
                }

                if (!empty($data['shipping_city'])) {
                    if (empty($data['shipping_city_id'])) {
                        $this->log(
                            sprintf(
                                __('Error in update user > Unable to set shipping_city_id for shipping_city %s. Country_ID is missing', 'easycms-wp'),
                                $data['shipping_city']
                            ),
                            'error'
                        );

                        return;
                    }

                    $city_id = $this->get_city_id($data['shipping_country_id'], $data['shipping_city']);

                    if (!$city_id) {
                        $data['shipping_city_id'] = 'new_city: ' . $data['shipping_city'];
                    } else {
                        $data['shipping_city_id'] = $city_id;
                    }
                }

            } else {
                $this->log(
                    sprintf(
                        __('### set_cms_params::No existing user data in WP : get_user_meta(%s)', 'easycms-wp'),
                        $user_id
                    ),
                    'error'
                );
            }

            $data['email'] = $user->data->user_email;
            $data['ordering_email'] = $user->data->user_email;
            $data['customer_password'] = $user->data->user_pass;
        }
        $this->log(
            sprintf(
                __('### set_cms_params::data json are: %s. Proceeding to sync', 'easycms-wp'),
                json_encode($data)
            ),
            'info'
        );

        return $data;
    }

    public function register_user(int $user_id, array $data = array())
    {
        $data = $this->set_cms_params($user_id, true);

        if (!$data) {
            $this->log(
                __('Unable to set user data parameters', 'easycms-wp')
            );
        } else {
            $user = get_user_by('id', $user_id);
            if ($this->insert_user_cms($user, $data)) {
                $this->log(
                    sprintf(
                        __('User %s pushed to CMS successfully', 'easycms-wp'),
                        $data['email']
                    ),
                    'info'
                );

                // if ( ! add_user_meta( $user->ID, 'easycms_wp_account', 1, true ) ) {
                // 	update_user_meta( $user->ID, 'easycms_wp_account', 1 );
                // }
                return true;
            }
        }

        return;
    }

    public function get_cms_id(int $user_id)
    {
        $user = get_user_by('id', $user_id);
        if ($user && $user->easycms_wp_id) {
            return $user->easycms_wp_id;
        }

        return false;
    }

    public function get_user(int $cms_id)
    {
        $users = get_users(array(
            'meta_key' => 'easycms_wp_id',
            'meta_value' => $cms_id,
        ));

        return $users ? $users[0] : false;
    }

    public function get_cms_data(int $user_id)
    {
        return get_user_meta($user_id, 'easycms_wp_data', true);
        // update_user_meta( $user_id, 'easycms_wp_data', $data );
    }

    private function save_user_meta(int $user_id, $data)
    {
        $this->log(
            __('Setting customer\'s user data', 'easycms-wp'),
            'info'
        );

        if (!empty($data->customer_id)) {
            update_user_meta($user_id, 'easycms_wp_id', $data->customer_id);
            update_user_meta($user_id, 'easycms_wp_data', $data);
            $this->log(
                __('Customer\'s data set successfully', 'easycms-wp'),
                'info'
            );
        } else {
            $this->log(
                __('Error setting customer\'s user data. Customer_id not found', 'easycms-wp'),
                'error'
            );
        }
    }

    private function insert_user_cms(\WP_User $user, array $data)
    {
        $req = $this->make_request('/set_customer_register_wp', 'POST', $data);

        if (is_wp_error($req)) {
            $this->log(
                sprintf(
                    __('Unable to insert user to CMS: %s', 'easycms-wp'),
                    $req->get_error_message()
                ),
                'error'
            );

            return;
        }

        $data = json_decode($req);
        if ($data && !empty($data->OUTPUT)) {
            if ($data->OUTPUT->response_type == 'error') {
                $this->log(
                    sprintf(
                        __('Error inserting user to CMS: %s', 'easycms-wp'),
                        $data->OUTPUT->message
                    ),
                    'error'
                );

                return;
            }

            if ('success' == $data->OUTPUT->response_type) {
                $this->log(
                    sprintf(
                        __('User with the email %s pushed to CMS', 'easycms-wp'),
                        $user->data->user_email
                    ),
                    'info'
                );

                $this->save_user_meta($user->ID, $data->OUTPUT->customer_data->{0});

                return 1;
            }

            $this->log(
                __('Unknown data format received from API', 'easycms-wp'),
                'error'
            );
            return;
        }

        $this->log(
            __('Empty or unable to parse JSON response.', 'easycms-wp'),
            'error'
        );

        return;
    }

    public function get_language_id(string $language)
    {
        $req = $this->make_request('/get_languages');

        if (!is_wp_error($req)) {
            $data = json_decode($req);

            if ($data && !empty($data->OUTPUT)) {
                $active_lang = array_filter($data->OUTPUT, function ($lang) use ($language) {
                    if (strpos($lang->code, '_') !== false) {
                        return strstr($lang->code, '_', true) == $language;
                    } else {
                        return $lang->code == $language;
                    }
                });

                if (!$active_lang) {
                    $this->log(
                        sprintf(
                            __('Unable to find default language %s on CMS', 'easycms-wp'),
                            $language
                        ),
                        'error'
                    );

                    return 0;
                }

                return current($active_lang)->id;
            } else {
                $this->log(__('Empty request body from /get_languages', 'easycms-wp'), 'error');
            }
        }

        $this->log(
            sprintf(
                __('Error retrieving data from URL: %s', 'easycms-wp'),
                $req->get_error_message()
            ),
            'error'
        );

        return 0;
    }
}
?>