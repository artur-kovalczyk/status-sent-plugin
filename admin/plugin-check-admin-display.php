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
 * @subpackage Plugin_Check/admin
 * @author     Artur Kowalczyk <arturkowalczyc@wp.pl>
 */
class Plugin_Check_Admin_Display
{


    public function __construct()
    {
        /** Step 1 (event). */
        add_action('admin_menu', [$this, 'plugin_check_menu']);

       $this->CheckCron();
    }

    /** Step 2 (add item). */
    public function plugin_check_menu()
    {
        $page_title = 'Plugin Check Options';
        $menu_title = 'Plugin Check';
        $capability = 'manage_options'; // Only users that can manage options can access this menu item.
        $menu_slug = 'plugin-check'; // unique identifier.
        $callback = [$this, 'my_plugin_options'];
        $hookname = add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback);

        /** Setup fields. */
        // add_action( 'load-'.$hookname, [ $this, 'my_plugin_fields' ] );
        add_action('admin_init', [$this, 'my_plugin_fields']);

    }

    /** Step 3 (page html). */
    public function my_plugin_options()
    { ?>
        <div class="wrap">
            <h2>Plugin Check</h2>
            <?php settings_errors() ?>
            <form method="post" action="options.php">
                <?php settings_fields('plugin-check'); ?>
                <?php do_settings_sections('plugin-check'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function my_plugin_fields()
    {
        /** Check permissions. */
        if (!current_user_can('manage_options')) {
            return;
        }

        /* Define here your sections and fields. */
        $slug = 'plugin-check';
        $sections = [
            [
                'id' => 'my_section_1',
                'fields' => [
                    ['type' => 'checkbox', 'name' => 'hourly_send_schedule_active', 'value' => '', 'title' => 'Activate Hourly Send Wordpress Core and Plugins Status?'],
                    ['type' => 'url', 'name' => 'url_to_send_schedule', 'value' => '', 'title' => 'API url to send the data'],
                ],
            ],
        ];

        global $new_allowed_options;
        $option_names = $new_allowed_options[$slug];

        /** Call Settings API to generate sections and fields. */
        $callback = [ $this, 'field_callback' ];
        foreach ($sections as $index => $section) {
            /** Adds section. */
            add_settings_section("section_$index", $section['title'], false, $slug);
            foreach ($section['fields'] as $field) {
                /** Adds field. */
                add_settings_field($field['name'], $field['title'], $callback, $slug, "section_$index", $field);
                register_setting('plugin-check', $field['name']);
            }
        }
    }

    public function field_callback($arguments)
    {
        switch ($arguments['type']) {
            case ('checkbox'):
                echo "<input type='" . $arguments["type"] . "'"
                    . "name='" . $arguments["name"] . "' "; ?>
                <?php checked('yes', get_option($arguments["name"])); ?> value='yes'/><?php

                break;

            case ('url'):
                echo "<input type='" . $arguments["type"] . "'"
                    . "name='" . $arguments["name"] . "' "
                    . "value='" . esc_url_raw(get_option($arguments["name"])) ."' pattern='https?://.+'";
                break;
        }

        if($_GET['settings-updated']=='true'){

            $this->CheckCron();
        }
    }


    public function CheckCron()
    {
        require_once 'partials/plugin-check-generate-content.php';

        $url = get_option("url_to_send_schedule");
        $CronObj = new Plugin_Check_Generate_Content($url);

        if($_GET['settings-updated'] == 'true'){

            if(get_option("hourly_send_schedule_active") === 'yes'){
                $CronObj->ActivateCronJob();
            }
            else if(get_option("hourly_send_schedule_active") === ''){
                $CronObj->DeactivateCronJob();
            }
        }
    }

}