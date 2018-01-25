<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueues the required admin scripts.
 *
 */
function upstream_load_admin_scripts($hook)
{
    $isAdmin = is_admin();
    if (!$isAdmin) {
        return;
    }

    $postType = get_post_type();
    if (empty($postType)) {
        $postType = isset($_GET['post_type']) ? $_GET['post_type'] : '';
    }

    $assetsDir =  UPSTREAM_PLUGIN_URL . 'includes/admin/assets/';

    $admin_deps = array( 'jquery', 'cmb2-scripts' );

    global $pagenow;

    if (in_array($pagenow, array('edit.php', 'post.php', 'post-new.php'))) {
        if ($postType === 'project') {
            global $post_type_object;

            wp_register_script( 'upstream-project', $assetsDir . 'js/edit-project.js', $admin_deps, UPSTREAM_VERSION, false );
            wp_enqueue_script( 'upstream-project' );
            wp_localize_script( 'upstream-project', 'upstream_project', apply_filters( 'upstream_project_script_vars', array(
                'version'   => UPSTREAM_VERSION,
                'user'      => upstream_current_user_id(),
                'slugBox'   => !(get_post_status() === "pending" && !current_user_can($post_type_object->cap->publish_posts)),
                'l'         => array(
                    'LB_CANCEL'           => __('Cancel'),
                    'LB_SEND_REPLY'       => __('Add Reply', 'upstream'),
                    'LB_REPLY'            => __('Reply'),
                    'LB_ADD_COMMENT'      => __('Add Comment'),
                    'LB_ADD_NEW_COMMENT'  => __('Add new Comment'),
                    'LB_ADD_NEW_REPLY'    => __('Add Comment Reply', 'upstream'),
                    'LB_ADDING'           => __('Adding...', 'upstream'),
                    'LB_REPLYING'         => __('Replying...', 'upstream'),
                    'LB_DELETE'           => __('Delete', 'upstream'),
                    'LB_DELETING'         => __('Deleting...', 'upstream'),
                    'LB_UNAPPROVE'        => __('Unapprove'),
                    'LB_UNAPPROVING'      => __('Unapproving...', 'upstream'),
                    'LB_APPROVE'          => __('Approve'),
                    'LB_APPROVING'        => __('Approving...', 'upstream'),
                    'MSG_ARE_YOU_SURE'    => __('Are you sure? This action cannot be undone.', 'upstream'),
                    'MSG_COMMENT_NOT_VIS' => __('This comment is not visible by regular users.', 'upstream')
                )
            ) ) );
        } else if ($postType === 'client') {
            wp_enqueue_script('up-metabox-client', $assetsDir . 'js/metabox-client.js', array('jquery'), UPSTREAM_VERSION, true);
            wp_localize_script('up-metabox-client', 'upstreamMetaboxClientLangStrings', array(
                'ERR_JQUERY_NOT_FOUND'     => __('UpStream requires jQuery.', 'upstream'),
                'MSG_NO_ASSIGNED_USERS'    => __("There's no users assigned yet.", 'upstream'),
                'MSG_NO_USER_SELECTED'     => __('Please, select at least one user', 'upstream'),
                'MSG_ADD_ONE_USER'         => __('Add 1 User', 'upstream'),
                'MSG_ADD_MULTIPLE_USERS'   => __('Add %d Users', 'upstream'),
                'MSG_NO_USERS_FOUND'       => __('No users found.', 'upstream'),
                'LB_ADDING_USERS'          => __('Adding...', 'upstream'),
                'MSG_ARE_YOU_SURE'         => __('Are you sure? This action cannot be undone.', 'upstream'),
                'MSG_FETCHING_DATA'        => __('Fetching data...', 'upstream'),
                'MSG_NO_DATA_FOUND'        => __('No data found.', 'upstream'),
                'MSG_MANAGING_PERMISSIONS' => __("Managing %s\'s Permissions", 'upstream')
            ));
        }

        $postTypesUsingCmb2 = apply_filters('upstream:post_types_using_cmb2', array('project', 'client'));

        if (in_array($postType, $postTypesUsingCmb2)) {
            wp_register_style('upstream-admin', $assetsDir . 'css/upstream.css', array(), UPSTREAM_VERSION);
            wp_enqueue_style('upstream-admin');
        }
    } else if ($pagenow === 'admin.php'
        && isset($_GET['page'])
        && preg_match('/^upstream_/i', $_GET['page'])
    ) {
        wp_register_style('upstream-admin', $assetsDir . 'css/upstream.css', array(), UPSTREAM_VERSION);
        wp_enqueue_style('upstream-admin');
    }

    wp_register_style('upstream-admin-icon', $assetsDir . 'css/admin-upstream-icon.css', array(), UPSTREAM_VERSION);
    wp_enqueue_style('upstream-admin-icon');
}
add_action('admin_enqueue_scripts', 'upstream_load_admin_scripts', 100);
