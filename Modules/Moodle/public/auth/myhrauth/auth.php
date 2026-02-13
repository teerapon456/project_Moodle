<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

class auth_plugin_myhrauth extends auth_plugin_base
{
    public function __construct()
    {
        $this->authtype = 'myhrauth';
    }

    /**
     * Returns true if this authentication plugin is enabled.
     *
     * @return bool
     */
    function is_internal()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's password.
     *
     * @return bool
     */
    function can_change_password()
    {
        return false;
    }

    /**
     * Returns the user's password change URL.
     *
     * @return string|null
     */
    function change_password_url()
    {
        return null;
    }

    /**
     * Authenticates the user against the MYHR Portal database using SSO token.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return bool True if authentication is successful, false otherwise.
     */
    function user_login($username, $password)
    {
        return false; // This plugin is for SSO only, not manual login
    }
}
