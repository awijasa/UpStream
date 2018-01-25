<?php
/**
 * UpStream Admin
 *
 * @class    UpStream_Admin
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * UpStream_Admin class.
 */
class UpStream_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'includes' ) );
        add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
        add_filter( 'ajax_query_attachments_args', array( $this, 'filter_user_attachments' ), 10, 1 );
        add_action('admin_menu', array($this, 'limitUpStreamUserAccess'));

        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            add_filter('comment_status_links', array($this, 'commentStatusLinks'), 10, 1);
            add_action('pre_get_comments', array($this, 'preGetComments'), 10, 1);
        }

        add_action('wp_ajax_upstream:project.get_all_items_comments', array('UpStream_Metaboxes_Projects', 'fetchAllItemsComments'));
    }

    public function limitUpStreamUserAccess()
    {
        if (empty($_GET) || !is_admin()) {
            return;
        }

        $user = wp_get_current_user();
        $userIsUpStreamUser = count(array_intersect($user->roles, array('administrator', 'upstream_manager'))) === 0;

        if ($userIsUpStreamUser) {
            global $pagenow;

            $shouldRedirect = false;

            $postType = isset($_GET['post_type']) ? $_GET['post_type'] : '';
            $isPostTypeProject = $postType === 'project';

            if ($pagenow === 'edit-tags.php') {
                if (isset($_GET['taxonomy'])
                    && $_GET['taxonomy'] === 'project_category'
                    && $isPostTypeProject
                ) {
                    $shouldRedirect = true;
                }
            } else if ($pagenow === 'post.php'
                && $isPostTypeProject
            ) {
                $projectMembersList = (array)get_post_meta((int)$_GET['post'], '_upstream_project_members', true);
                // Since he's not and Administrator nor an UpStream Manager, we need to check if he's participating in the project.
                if (!in_array($user->ID, $projectMembersList)) {
                    $shouldRedirect = true;
                }
            } else if ($pagenow === 'post-new.php'
                && $isPostTypeProject
            ) {
                $shouldRedirect = true;
            } else if ($pagenow === 'edit.php'
                && $postType === 'client'
            ) {
                $shouldRedirect = true;
            }

            if ($shouldRedirect) {
                // Redirect the user to the projects list page.
                $pagenow = 'edit.php';
                wp_redirect(admin_url('/edit.php?post_type=project'));
                exit;
            }
        }
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes() {

        // option pages
        include_once( 'class-up-admin-options.php' );
        include_once( 'options/option-functions.php' );

        // metaboxes
        include_once( 'class-up-admin-metaboxes.php' );
        include_once( 'metaboxes/metabox-functions.php' );

        include_once( 'up-enqueues.php' );
        include_once( 'class-up-admin-projects-menu.php' );
        include_once( 'class-up-admin-project-columns.php' );
        include_once( 'class-up-admin-client-columns.php' );
        include_once( 'class-up-admin-pointers.php' );
    }


    /**
     * Adds one or more classes to the body tag in the dashboard.
     *
     * @param  String $classes Current body classes.
     * @return String          Altered body classes.
     */
    public function admin_body_class( $classes ) {

        $screen = get_current_screen();

        if ( in_array( $screen->id, array( 'client', 'edit-client', 'project', 'edit-project', 'edit-project_category', 'project_page_tasks', 'project_page_bugs', 'toplevel_page_upstream_general', 'upstream_page_upstream_bugs', 'upstream_page_upstream_tasks', 'upstream_page_upstream_milestones', 'upstream_page_upstream_clients', 'upstream_page_upstream_projects' ) ) ) {

            return "$classes upstream";

        }

    }


    /**
     * Only show current users media items
     *
     */
    public function filter_user_attachments( $query = array() ) {
        $user_id = get_current_user_id();
        if( $user_id ) {
            $query['author'] = $user_id;
        }
        return $query;
    }

    /**
     * Filter comments for Comments.php page.
     *
     * @since   1.13.0
     * @static
     *
     * @param   array   $query  Query args array.
     */
    public static function preGetComments($query)
    {
        if (!isUserEitherManagerOrAdmin()) {
            $user = wp_get_current_user();

            if (in_array('upstream_user', $user->roles) || in_array('upstream_client_user', $user->roles)) {
                // Limit comments visibility to projects user is participating in.
                $allowedProjects = upstream_get_users_projects($user);
                $query->query_vars['post__in'] = array_keys($allowedProjects);

                $userCanModerateComments = user_can($user, 'moderate_comments');
                $userCanDeleteComments = user_can($user, 'delete_project_discussion');

                $query->query_vars['status'] = array('approve');

                if ($userCanModerateComments) {
                    $query->query_vars['status'][] = 'hold';
                } else if (empty($allowedProjects)) {
                    $query->query_vars['post__in'] = -1;
                }
            } else {
                // Hide Projects comments from other user types.
                $projects = get_posts(array(
                    'post_type'      => "project",
                    'posts_per_page' => -1
                ));

                $ids = array();
                foreach ($projects as $project) {
                    $ids[] = $project->ID;
                }

                $query->query_vars['post__not_in'] = $ids;
            }
        }
    }

    /**
     * Set up WP-Table filters links.
     *
     * @since   1.13.0
     * @static
     *
     * @param   array   $links  Associative array of table filters.
     *
     * @return  array   $links
     */
    public static function commentStatusLinks($links)
    {
        if (!isUserEitherManagerOrAdmin()) {
            $user = wp_get_current_user();

            if (in_array('upstream_user', $user->roles) || in_array('upstream_client_user', $user->roles)) {
                $userCanModerateComments = user_can($user, 'moderate_comments');
                $userCanDeleteComments = user_can($user, 'delete_project_discussion');

                if (!$userCanModerateComments) {
                    unset($links['moderated'], $links['approved'], $links['spam']);

                    if (!$userCanDeleteComments) {
                        unset($links['trash']);
                    }
                }

                $projects = upstream_get_users_projects($user);

                $commentsQueryArgs = array(
                    'post_type' => "project",
                    'post__in'  => array_keys($projects),
                    'count'     => true
                );

                $commentsCount = new stdClass();
                $commentsCount->all = get_comments($commentsQueryArgs);

                $links['all'] = preg_replace('/<span class="all-count">\d+<\/span>/', '<span class="all-count">'. $commentsCount->all .'</span>', $links['all']);

                if (isset($links['moderated'])) {
                    $commentsCount->approved = get_comments(array_merge($commentsQueryArgs, array('status' => "approve")));

                    $links['approved'] = preg_replace('/<span class="approved-count">\d+<\/span>/', '<span class="approved-count">'. $commentsCount->approved .'</span>', $links['approved']);

                    $commentsCount->pending = get_comments(array_merge($commentsQueryArgs, array('status' => "hold")));

                    $links['moderated'] = preg_replace('/<span class="pending-count">\d+<\/span>/', '<span class="pending-count">'. $commentsCount->pending .'</span>', $links['moderated']);
                }

                if (isset($links['trash'])) {
                    $commentsCount->trash = get_comments(array_merge($commentsQueryArgs, array('status' => "trash")));

                    $links['trash'] = preg_replace('/<span class="trash-count">\d+<\/span>/', '<span class="trash-count">'. $commentsCount->trash .'</span>', $links['trash']);
                }
            } else {
                $projects = get_posts(array(
                    'post_type'      => "project",
                    'posts_per_page' => -1
                ));

                $projectsIds = array();
                foreach ($projects as $project) {
                    $projectsIds[] = $project->ID;
                }

                $commentsQueryArgs = array(
                    'post__not_in'  => $projectsIds,
                    'count'         => true
                );

                if (isset($links['all'])) {
                    $count = get_comments($commentsQueryArgs);
                    $links['all'] = preg_replace('/<span class="all-count">\d+<\/span>/', '<span class="all-count">'. $count .'</span>', $links['all']);
                }

                if (isset($links['moderated'])) {
                    $count = get_comments(array_merge($commentsQueryArgs, array('status' => "hold")));
                    $links['moderated'] = preg_replace('/<span class="pending-count">\d+<\/span>/', '<span class="pending-count">'. $count .'</span>', $links['moderated']);
                }

                if (isset($links['approved'])) {
                    $count = get_comments(array_merge($commentsQueryArgs, array('status' => "approve")));
                    $links['approved'] = preg_replace('/<span class="approved-count">\d+<\/span>/', '<span class="approved-count">'. $count .'</span>', $links['approved']);
                }

                if (isset($links['spam'])) {
                    $count = get_comments(array_merge($commentsQueryArgs, array('status' => "spam")));
                    $links['spam'] = preg_replace('/<span class="spam-count">\d+<\/span>/', '<span class="spam-count">'. $count .'</span>', $links['spam']);
                }

                if (isset($links['trash'])) {
                    $count = get_comments(array_merge($commentsQueryArgs, array('status' => "trash")));
                    $links['trash'] = preg_replace('/<span class="trash-count">\d+<\/span>/', '<span class="trash-count">'. $count .'</span>', $links['trash']);
                }
            }
        }

        return $links;
    }
}

return new UpStream_Admin();
