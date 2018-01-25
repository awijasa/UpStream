<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class UpStream_Template_Loader {

    /**
     * Get things going
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'template_include', array( $this, 'template_loader' ) );
    }

    /**
     * Load a template.
     *
     * Handles template usage so that we can use our own templates instead of the themes.
     *
     * Templates are in the 'templates' folder. upstream looks for theme.
     * overrides in /theme/upstream/ by default.
     *
     * @param mixed $template
     * @return string
     */
    public function template_loader( $template ) {

        $file = '';

        if( get_post_type() != 'project' )
            return $template;

        if ( is_single() ) {
            require_once UPSTREAM_PLUGIN_DIR . 'includes/admin/metaboxes/metabox-functions.php';

            $file = 'single-project.php';

            $user_id = get_current_user_id();
            $project_id = upstream_post_id();

            if (!upstream_user_can_access_project($user_id, $project_id)) {
                wp_redirect(site_url('projects'));
                exit;
            }
        }

        if (is_archive()) {
            $file = 'archive-project.php';
        }

        if (isset($_GET['action']) && $_GET['action'] === 'logout' && !isset($_POST['login'])) {
            UpStream_Login::doDestroySession();

            if (preg_match('/^\/projects/i', $_SERVER['REQUEST_URI'])) {
                $redirectTo = wp_login_url(site_url('projects'));
            } else {
                $redirectTo = get_permalink();
            }

            wp_redirect($redirectTo);
            exit;
        }

        /*
         * Login page if not logged in
         */
        if ( ! upstream_is_user_logged_in() ) {
            $file = 'login.php';
            $GLOBALS['login_template'] = true;
        }

        if ( $file ) {

            $check_dirs = array(
                trailingslashit( get_stylesheet_directory() ) . upstream_template_path(),
                trailingslashit( get_template_directory() ) . upstream_template_path(),
                UPSTREAM_PLUGIN_DIR . 'templates/'
            );

            foreach ( $check_dirs as $dir ) {
                if ( file_exists( trailingslashit( $dir ) . $file ) ) {
                    load_template( $dir . $file );
                    return;
                }
            }

        }

        return $template;
    }

}

new UpStream_Template_Loader();
