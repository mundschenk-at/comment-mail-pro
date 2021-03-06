<?php
/*[pro exclude-file-from="lite"]*/
/**
 * SSO Utilities.
 *
 * @since     141111 First documented version.
 *
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license   GNU General Public License, version 3
 */
namespace WebSharks\CommentMail\Pro;

/**
 * SSO Utilities.
 *
 * @since 141111 First documented version.
 */
class UtilsSso extends AbsBase
{
    /**
     * Class constructor.
     *
     * @since 141111 First documented version.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Attempt to log a user in automatically.
     *
     * @since 141111 First documented version.
     *
     * @param string $service The service name we are dealing with.
     * @param string $sso_id  The SSO service's ID for this user.
     * @param array  $args    Add additional specs and/or behavioral args.
     *
     * @return bool `TRUE` on a successful/automatic login.
     */
    public function autoLogin($service, $sso_id, array $args = [])
    {
        if (!($service = trim(strtolower((string) $service)))) {
            return false; // Not possible.
        }
        if (!($sso_id = trim((string) $sso_id))) {
            return false; // Not possible.
        }
        $default_args = [
            'no_cache' => false,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $user_exists = $user_id = $this->userExists($service, $sso_id, $args);

        if (!$user_exists || !$user_id) {
            return false; // Not possible.
        }
        wp_set_auth_cookie($user_id); // Set cookie.

        return true; // User is now logged into their account.
    }

    /**
     * Attempt to register and log a user in automatically.
     *
     * @since 141111 First documented version.
     *
     * @param string $service The service name we are dealing with.
     * @param string $sso_id  The SSO service's ID for this user.
     * @param array  $args    Add additional specs and/or behavioral args.
     *
     * @return bool `TRUE` on a successful/automatic registration & login.
     */
    public function autoRegisterLogin($service, $sso_id, array $args = [])
    {
        if (!($service = trim(strtolower((string) $service)))) {
            return false; // Not possible.
        }
        if (!($sso_id = trim((string) $sso_id))) {
            return false; // Not possible.
        }
        $default_args = [
            'fname' => '',
            'lname' => '',
            'email' => '',

            'no_cache' => false,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $args_no_cache_false = array_merge($args, ['no_cache' => false]);
        $args_no_cache_true  = array_merge($args, ['no_cache' => true]);

        $user_exists = $user_id = $this->userExists($service, $sso_id, $args);

        if ($user_exists) { // If so, just log them in now.
            return $this->autoLogin($service, $sso_id, $args_no_cache_false);
        }
        //if(!$this->plugin->utils_user->canRegister())
        //	return FALSE; // Not possible.

        $fname = trim((string) $args['fname']);
        $lname = trim((string) $args['lname']);
        $email = trim((string) $args['email']);

        $fname = $this->plugin->utils_string->firstName($fname, $email);

        $no_cache = (boolean) $args['no_cache']; // Fresh check(s)?

        if (!$fname || !$email || !is_email($email)
            || $this->plugin->utils_user->emailExistsOnBlog($email, $no_cache)
        ) {
            return false; // Invalid; or email exists on this blog already.
        }
        # Handle the insertion of this user now.

        $first_name   = $fname; // Data from above.
        $last_name    = $lname; // Data from above.
        $display_name = $fname; // Data from above.

        $user_email = $email; // Data from above.
        $user_login = strtolower('sso'.$this->plugin->utils_enc->uunnciKey20Max());
        $user_pass  = wp_generate_password();

        if (is_multisite()) { // On networks, there are other considerations.
            $user_data = compact('first_name', 'last_name', 'display_name', 'user_login', 'user_pass');
            if (is_wp_error($user_id = wp_insert_user($user_data)) || !$user_id) {
                return false; // Insertion failure.
            }
            // So WP will allow duplicate email addresses across child blogs.
            $user_data_update = array_merge(['ID' => $user_id], compact('user_email'));
            if (is_wp_error(wp_update_user($user_data_update))) { // Update email address.
                return false; // Update failure on email address.
            }
            if (!add_user_to_blog(get_current_blog_id(), $user_id, get_option('default_role'))) {
                return false; // Failed to add the user to this blog.
            }
        } else { // Just a single DB query will do; i.e. we can set the email address on insertion.
            $user_data = compact('first_name', 'last_name', 'display_name', 'user_email', 'user_login', 'user_pass');
            if (is_wp_error($user_id = wp_insert_user($user_data)) || !$user_id) {
                return false; // Insertion failure.
            }
        }
        $user_sso_services = get_user_option(GLOBAL_NS.'_sso_services');
        $user_sso_services = is_array($user_sso_services) ? $user_sso_services : [];
        $user_sso_services = array_unique(array_merge($user_sso_services, [$service]));
        update_user_option($user_id, GLOBAL_NS.'_sso_services', $user_sso_services);
        update_user_option($user_id, GLOBAL_NS.'_'.$service.'_sso_id', $sso_id);

        return $this->autoLogin($service, $sso_id, $args_no_cache_true);
    }

    /**
     * Check if an account exists already.
     *
     * @since 141111 First documented version.
     *
     * @param string $service The service name we are dealing with.
     * @param string $sso_id  The SSO service's ID for this user.
     * @param array  $args    Add additional specs and/or behavioral args.
     *
     * @return int A matching WP user ID, else `0` on failure.
     */
    public function userExists($service, $sso_id, array $args = [])
    {
        if (!($service = trim(strtolower((string) $service)))) {
            return 0; // Not possible.
        }
        if (!($sso_id = trim((string) $sso_id))) {
            return 0; // Not possible.
        }
        $default_args = [
            'no_cache' => false,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $no_cache = (boolean) $args['no_cache'];

        $cache_keys = compact('service', 'sso_id');
        if (!is_null($user_id = &$this->cacheKey(__FUNCTION__, $cache_keys)) && !$no_cache) {
            return $user_id; // Already cached this.
        }
        $meta_key = $this->plugin->utils_db->wp->prefix.GLOBAL_NS.'_'.$service.'_sso_id';

        $matching_user_ids_sql = // Find a matching SSO ID in the `wp_users` table; for this blog.

            'SELECT `user_id` FROM ('.// See: <http://jas.xyz/1I52mVE>

            '	SELECT `user_id` FROM `'.esc_sql($this->plugin->utils_db->wp->usermeta).'`'.
            "	 WHERE `meta_key` = '".esc_sql($meta_key)."'".
            "	 AND `meta_value` = '".esc_sql($sso_id)."'".

            ') AS `user_id`'; // Alias requirement.

        $sql = // Find a user ID matching the SSO ID; if possible.

            'SELECT `ID` FROM `'.esc_sql($this->plugin->utils_db->wp->users).'`'.
            ' WHERE `ID` IN('.$matching_user_ids_sql.') LIMIT 1';

        return $user_id = (integer) $this->plugin->utils_db->wp->get_var($sql);
    }

    /**
     * Request registration completion.
     *
     * @since 141111 First documented version.
     *
     * @param array $request_args Data in the current SSO request action.
     * @param array $args         Any data we already have; or behavior args.
     */
    public function requestCompletion(array $request_args = [], array $args = [])
    {
        $default_request_args = [
            'service'     => null,
            'action'      => null,
            'redirect_to' => null,

            'sso_id'   => null,
            '_wpnonce' => null,

            'fname' => null,
            'lname' => null,
            'email' => null,
        ];
        $request_args = array_merge($default_request_args, $request_args);
        $request_args = array_intersect_key($request_args, $default_request_args);

        $default_args = [
            'service'     => '',
            'action'      => '',
            'redirect_to' => '',

            'sso_id'   => '',
            '_wpnonce' => '',

            'fname' => '',
            'lname' => '',
            'email' => '',
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        if (!($service = trim((string) $request_args['service']))) {
            $service = trim((string) $args['service']);
        }
        if (!($action = trim((string) $request_args['action']))) {
            $action = trim((string) $args['action']);
        }
        if (!($redirect_to = trim((string) $request_args['redirect_to']))) {
            $redirect_to = trim((string) $args['redirect_to']);
        }
        if (!($sso_id = trim((string) $request_args['sso_id']))) {
            $sso_id = trim((string) $args['sso_id']);
        }
        if (!($_wpnonce = trim((string) $request_args['_wpnonce']))) {
            $_wpnonce = trim((string) $args['_wpnonce']);
        }
        if (!($fname = trim((string) $request_args['fname']))) {
            $fname = trim((string) $args['fname']);
        }
        if (!($lname = trim((string) $request_args['lname']))) {
            $lname = trim((string) $args['lname']);
        }
        if (!($email = trim((string) $request_args['email']))) {
            $email = trim((string) $args['email']);
        }
        $form_fields = new FormFields(
            [
                'ns_name_suffix' => '[sso]',
                'ns_id_suffix'   => '-sso-complete-form',
                'class_prefix'   => 'sso-complete-form-',
            ]
        );
        $_this         = $this; // Needed by this closure.
        $hidden_inputs = function () use ($_this, $form_fields, $service, $redirect_to, $sso_id) {
            return $_this->hiddenInputsForCompletion(get_defined_vars());
        };
        $error_codes = []; // Initialize error codes array.

        if ($action === 'complete') { // Processing completion?
            //if(!$this->plugin->utils_user->canRegister())
            //	$error_codes[] = 'users_cannot_register';

            if (!$service) { // Service is missing?
                $error_codes[] = 'missing_service';
            } elseif (!$sso_id) { // SSO ID is missing?
                $error_codes[] = 'missing_sso_id';
            } elseif (!wp_verify_nonce($_wpnonce, GLOBAL_NS.'_sso_complete')) {
                $error_codes[] = 'invalid_wpnonce';
            }
            if (!$fname) { // First name is missing?
                $error_codes[] = 'missing_fname';
            }
            if (!$email) { // Email address is missing?
                $error_codes[] = 'missing_email';
            } elseif (!is_email($email)) { // Invalid email?
                $error_codes[] = 'invalid_email';
            } elseif ($this->plugin->utils_user->emailExistsOnBlog($email)) {
                $error_codes[] = 'email_exists'; // Exists on this blog already.
            }
        } elseif ($action === 'callback') { // Handle duplicate email on callback.
            // Note: only occurs if an account exists w/ a different underlying SSO ID.
            // Otherwise, for existing accounts w/ a matching SSO ID, we automatically log them in.

            //if(!$this->plugin->utils_user->canRegister())
            //	$error_codes[] = 'users_cannot_register';

            if ($email && $this->plugin->utils_user->emailExistsOnBlog($email)) {
                $error_codes[] = 'email_exists'; // Exists on this blog already.
            }
        }
        $template_vars = get_defined_vars(); // Everything above.
        $template      = new Template('site/sso-actions/complete.php');

        status_header(200); // Status header.
        nocache_headers(); // Disallow caching.
        header('Content-Type: text/html; charset=UTF-8');

        exit($template->parse($template_vars));
    }

    /**
     * Hidden inputs for a completion request form.
     *
     * @since 141111 First documented version.
     *
     * @param array $args Specs and/or behavioral args.
     *
     * @return string Hidden inputs for a completion request form.
     */
    public function hiddenInputsForCompletion(array $args = [])
    {
        $default_args = [
            'form_fields' => null,

            'service'     => '',
            'redirect_to' => '',

            'sso_id' => '',
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        /** @type $form_fields form_fields Reference for IDEs. */
        if (!(($form_fields = $args['form_fields']) instanceof form_fields)) {
            return ''; // Not possible.
        }
        $service     = trim(strtolower((string) $args['service']));
        $redirect_to = trim((string) $args['redirect_to']);
        $sso_id      = trim((string) $args['sso_id']);

        $hidden_inputs = ''; // Initialize.

        $hidden_inputs .= $form_fields->hiddenInput(
            [
                'name'          => 'service',
                'current_value' => $service,
            ]
        )."\n";
        $hidden_inputs .= $form_fields->hiddenInput(
            [
                'name'          => 'action',
                'current_value' => 'complete',
            ]
        )."\n";
        $hidden_inputs .= $form_fields->hiddenInput(
            [
                'name'          => 'redirect_to',
                'current_value' => $redirect_to,
            ]
        )."\n";
        $hidden_inputs .= $form_fields->hiddenInput(
            [
                'name'          => 'sso_id', // Encrypted for security.
                'current_value' => $this->plugin->utils_enc->encrypt($sso_id),
            ]
        )."\n";
        $hidden_inputs .= $form_fields->hiddenInput(
            [
                'name'          => '_wpnonce',
                'current_value' => wp_create_nonce(GLOBAL_NS.'_sso_complete'),
            ]
        )."\n";
        $sso_get_vars = !empty($_GET[GLOBAL_NS]['sso']) ? (array) $_GET[GLOBAL_NS]['sso'] : [];
        $sso_get_vars = $this->plugin->utils_string->trimStripDeep($sso_get_vars);

        foreach ($sso_get_vars as $_sso_var_key => $_sso_var_value) {
            if (!in_array($_sso_var_key, ['action', 'service', 'redirect_to', 'sso_id', '_wpnonce'], true)) {
                $hidden_inputs .= $form_fields->hiddenInput(
                    [
                        'name'          => $_sso_var_key,
                        'current_value' => (string) $_sso_var_value,
                    ]
                )."\n";
            }
        }
        unset($_sso_var_key, $_sso_var_value); // Housekeeping.

        return $hidden_inputs;
    }
}
