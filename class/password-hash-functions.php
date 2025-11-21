<?php

/**
 * Create a hash (encrypt) of a plain text password.
 *
 * For integration with other applications, this function can be overwritten to
 * instead use the other package password checking algorithm.
 *
 * @param string $password Plain text user password to hash
 * @return string The hash string of the password
 */
if (!function_exists('wp_hash_password')) {
    function wp_hash_password(string $password): string
    {
        $bCrypt = new Bcrypt();
        return $bCrypt->buildHash($password, 'easyCMS');
    }
}

/**
 * Checks the plaintext password against the encrypted Password.
 *
 * Maintains compatibility between old version and the new cookie authentication
 * protocol using PHPass library. The $hash parameter is the encrypted password
 * and the function compares the plain text password when encrypted similarly
 * against the already encrypted password to see if they match.
 *
 * For integration with other applications, this function can be overwritten to
 * instead use the other package password checking algorithm.
 *
 * @param string $password Plaintext user's password
 * @param string $hash Hash of the user's password to check against.
 * @param string|int $user_id Optional. User ID.
 * @return bool False, if the $password does not match the hashed password
 *
 * @since 2.5.0
 *
 */
if (!function_exists('wp_check_password')) {
    function wp_check_password(string $password, string $hash, $user_id = ''): bool
    {
        $bCrypt = new Bcrypt();
        $check = $bCrypt->bcryptVerify($password, $hash);

        /** This filter is documented in wp-includes/pluggable.php */
        return apply_filters('check_password', $check, $password, $hash, $user_id);
    }
}

// Disable password change email like the original plugin
add_filter('send_password_change_email', '__return_false');

?>