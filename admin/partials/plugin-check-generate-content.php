<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://arturkowalczyk.com
 * @since      1.0.0
 *
 * @package    Plugin_Check
 * @subpackage Plugin_Check/includes
 */

/**
 * Class to generate content for send using wp_remote_post().
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Plugin_Check
 * @subpackage Plugin_Check/admin/partials
 * @author     Artur Kowalczyk <arturkowalczyc@wp.pl>
 */
class Plugin_Check_Generate_Content
{

    const EVENT_HOOK = 'cron_hourly_event';
    public $content_array;
    public $url;


    public function __construct($url)
    {

        $wp_array['wordpress'] = $this->checkCoreUpdate();
        $wp_plugins_array['plugins'] = $this->checkPluginUpdate();
        $this->content_array = array_merge($wp_array, $wp_plugins_array);
        $this->url = $url;
        add_action(self::EVENT_HOOK, array($this, 'hourly_event'));
    }

    public function init()
    {
        $wp_array['wordpress'] = $this->checkCoreUpdate();
        $wp_plugins_array['plugins'] = $this->checkPluginUpdate();
        $this->content_array = json_encode(array_merge($wp_array, $wp_plugins_array));
        return $this->content_array;
    }

    private function checkPluginUpdate()
    {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
        // Get all plugins
        $plugins = get_plugins();
        $plugins_data_send = array();

        // Get the list of active plugins
        // Delete the transient so wp_update_plugins can get fresh data
        if (function_exists('get_site_transient')) {
            delete_site_transient('update_plugins');
        } else {
            delete_transient('update_plugins');
        }
        // Force a plugin update check
        wp_update_plugins();
        // Different versions of wp store the updates in different places
        // TODO can we depreciate
        if (function_exists('get_site_transient') && ($transient = get_site_transient('update_plugins'))) {
            $current = $transient;
        } elseif ($transient = get_transient('update_plugins')) {
            $current = $transient;
        } else {
            $current = get_option('update_plugins');
        }
        foreach ((array)$plugins as $plugin_file => $plugin) {

            $plugin_name = dirname($plugin_file, 1);

            $plugins_data_send[$plugin_name]['current'] = $plugin['Version'];

            $new_version = isset($current->response[$plugin_file]) ? $current->response[$plugin_file]->new_version : null;
            if (is_plugin_active($plugin_name)) {
                $plugins[$plugin_name]['active'] = true;
            } else {
                $plugins[$plugin_name]['active'] = false;
            }
            if ($new_version) {
                $plugins_data_send[$plugin_name]['latest'] = $new_version;
                $plugin_update_require = true;
            } else {
                $plugins_data_send[$plugin_name]['latest'] = $plugin['Version'];
                $plugin_update_require = false;
            }

            $plugins_data_send[$plugin_name]['requires_update'] = $plugin_update_require;
        }
        return $plugins_data_send;
    }

    private function checkCoreUpdate()
    {
        $th = array();

        require_once ABSPATH . 'wp-includes/version.php';

        global $wp_version;

        if (!function_exists('wp_version_check')) {
            require_once ABSPATH . WPINC . '/update.php';
        }
        if (!function_exists('get_preferred_from_update_core')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        wp_version_check();

        $update = get_preferred_from_update_core();

        $th['current'] = $wp_version;

        if (isset($update->response) && $update->response == 'upgrade') {
            $th['latest'] = $update->current;
            $wp_update_require = true;
        } else {
            $th['latest'] = $wp_version;
            $wp_update_require = false;
        }
        $th['requires_update'] = $wp_update_require;
        return $th;
    }

    // register activation hook
    public function ActivateCronJob()
    {

        register_activation_hook(__FILE__, $this->activate_schedule());
    }

    // register deactivation hook
    public function DeactivateCronJob()
    {

        register_deactivation_hook(__FILE__, $this->deactivate_schedule());
    }

    // clear scheduled hook
    private function deactivate_schedule()
    {
        wp_clear_scheduled_hook('cron_hourly_event');
    }

    // check if scheduled hook exists
    private function activate_schedule()
    {

        if (!wp_next_scheduled('cron_hourly_event')) {
            wp_schedule_event(time(), 'hourly', 'cron_hourly_event');
        }
    }

    private function hourly_event()

    {
        $send_array = $this->init();

        wp_remote_post($this->url, array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => $send_array
            )
        );

        /**
         * or you can use
         *
        $response = wp_remote_post($this->url, array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                'body' => $send_array
                )
            );
         *
         * and write the code below using $response;
         */
    }


}
