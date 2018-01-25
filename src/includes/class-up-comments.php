<?php
namespace UpStream;

// Prevent direct access.
if (!defined('ABSPATH')) exit;

use UpStream\Traits\Singleton;
use UpStream\Comment;

/**
 * This class will act as a controller handling incoming requests regarding comments on UpStream items.
 *
 * @since   1.13.0
 */
class Comments
{
    use Singleton;

    /**
     * The current full namespace.
     *
     * @since   1.13.0
     * @access  private
     * @static
     *
     * @var     string  $namespace
     */
    private static $namespace;

    /**
     * Class constructor.
     *
     * @since   1.13.0
     */
    public function __construct()
    {
        self::$namespace = get_class(self::$instance);

        $this->attachHooks();
    }

    /**
     * Check if the item type is valid.
     *
     * @since   1.13.0
     * @static
     *
     * @param   string  $itemType   Value to be validated.
     *
     * @return  bool
     */
    public static function isItemTypeValid($itemType)
    {
        $itemTypes = array('project', 'milestone', 'task', 'bug', 'file');

        return in_array($itemType, $itemTypes);
    }

    /**
     * Attach all relevant actions to handle comments.
     *
     * @since   1.13.0
     * @access  private
     */
    private function attachHooks()
    {
        add_action('wp_ajax_upstream:project.add_comment', array(self::$namespace, 'storeComment'));
        add_action('wp_ajax_upstream:project.add_comment_reply', array($this, 'storeCommentReply'));
        add_action('wp_ajax_upstream:project.trash_comment', array(self::$namespace, 'trashComment'));
        add_action('wp_ajax_upstream:project.unapprove_comment', array(self::$namespace, 'unapproveComment'));
        add_action('wp_ajax_upstream:project.approve_comment', array(self::$namespace, 'approveComment'));
        add_action('wp_ajax_upstream:project.fetch_comments', array(self::$namespace, 'fetchComments'));
        add_filter('comment_notification_recipients', array(self::$namespace, 'customizeRecipients'), 10, 2);
    }

    /**
     * AJAX endpoint that stores a new comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function storeComment()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'error'   => null
        );

        try {
            // Check if the request payload is potentially invalid.
            if (
                !defined('DOING_AJAX')
                || !DOING_AJAX
                || empty($_POST)
                || !isset($_POST['nonce'])
                || !isset($_POST['project_id'])
                || !isset($_POST['item_type'])
                || !self::isItemTypeValid($_POST['item_type'])
                || !isset($_POST['content'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Prepare data to verify nonce.
            $commentTargetItemType = strtolower($_POST['item_type']);
            if ($commentTargetItemType !== 'project') {
                if (
                    !isset($_POST['item_id'])
                    || empty($_POST['item_id'])
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }

                $item_id = $_POST['item_id'];

                $nonceIdentifier = 'upstream:project.' . $commentTargetItemType . 's.add_comment';
            } else {
                $nonceIdentifier = 'upstream:project.add_comment';
            }

            // Verify nonce.
            if (!wp_verify_nonce($_POST['nonce'], $nonceIdentifier)) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the user has enough permissions to insert a new comment.
            if (!upstream_admin_permissions('publish_project_discussion')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $user_id = get_current_user_id();

            $comment = new Comment($_POST['content'], $project_id, $user_id);

            $comment->created_by->ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
            $comment->created_by->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $comment->save();

            update_comment_meta($comment->id, 'type', $commentTargetItemType);

            if ($commentTargetItemType !== "project") {
                update_comment_meta($comment->id, 'id', $item_id);
            }

            $useAdminLayout = !isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = $comment->render(true, $useAdminLayout);
            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that adds a new comment reply.
     *
     * @since   1.13.0
     * @static
     */
    public static function storeCommentReply()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'error'   => null
        );

        try {
            // Check if the request payload is potentially invalid.
            if (
                !defined('DOING_AJAX')
                || !DOING_AJAX
                || empty($_POST)
                || !isset($_POST['nonce'])
                || !isset($_POST['project_id'])
                || !isset($_POST['item_type'])
                || !self::isItemTypeValid($_POST['item_type'])
                || !isset($_POST['content'])
                || !isset($_POST['parent_id'])
                || !is_numeric($_POST['parent_id'])
                || !wp_verify_nonce($_POST['nonce'], 'upstream:project.add_comment_reply:' . $_POST['parent_id'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            $commentTargetItemType = strtolower($_POST['item_type']);
            if ($commentTargetItemType !== 'project') {
                if (
                    !isset($_POST['item_id'])
                    || empty($_POST['item_id'])
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }
            }

            // Check if the user has enough permissions to insert a new comment.
            if (!upstream_admin_permissions('publish_project_discussion')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $user_id = get_current_user_id();

            $comment = new Comment($_POST['content'], $project_id, $user_id);
            $comment->parent_id = (int)$_POST['parent_id'];
            $comment->created_by->ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
            $comment->created_by->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $comment->save();

            update_comment_meta($comment->id, 'type', $commentTargetItemType);

            if ($commentTargetItemType !== "project") {
                update_comment_meta($comment->id, 'id', $_POST['item_id']);
            }

            $useAdminLayout = !isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $parent = get_comment($comment->parent_id);

            $commentsCache = array(
                $parent->comment_ID => json_decode(json_encode(array(
                    'created_by' => array(
                        'name' => $parent->comment_author
                    )
                )))
            );

            $response['comment_html'] = $comment->render(true, $useAdminLayout, $commentsCache);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that trashes a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function trashComment()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'error'   => null
        );

        try {
            // Check if the request payload is potentially invalid.
            if (
                !defined('DOING_AJAX')
                || !DOING_AJAX
                || empty($_POST)
                || !isset($_POST['nonce'])
                || !isset($_POST['project_id'])
                || !isset($_POST['comment_id'])
                || !wp_verify_nonce($_POST['nonce'], 'upstream:project.trash_comment:' . $_POST['comment_id'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if the Discussion/Comments section is disabled for the current project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Comments are disabled for this project.", 'upstream'));
            }

            // Check if the parent comment exists.
            $comment_id = (int)$_POST['comment_id'];
            $comment = get_comment($comment_id);

            if (empty($comment)
                // Check if the comment belongs to that project.
                || (isset($comment->comment_post_ID)
                    && (int)$comment->comment_post_ID !== $project_id
                )
            ) {
                throw new \Exception(_x('Comment not found.', 'Removing a comment in projects', 'upstream'));
            }

            $user_id = (int)get_current_user_id();

            if (!upstream_admin_permissions('delete_project_discussion')
                && !current_user_can('moderate_comments')
                && (int)$comment->user_id !== $user_id
            ) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            $success = wp_trash_comment($comment);
            if (!$success) {
                throw new \Exception(__("It wasn't possible to delete this comment.", 'upstream'));
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Either approves/unapproves a given comment.
     * This method is called by the correspondent AJAX endpoints.
     *
     * @since   1.13.0
     * @access  private
     * @static
     *
     * @throws  \Exception when something went wrong or failed on validations.
     *
     * @param   int     $comment_id         Comment ID being edited.
     * @param   bool    $newApprovalStatus  Either the comment will be approved or not.
     *
     * @param   Comment $comment
     */
    private static function toggleCommentApprovalStatus($comment_id, $isApproved)
    {
        // Check if the request payload is potentially invalid.
        if (
            !defined('DOING_AJAX')
            || !DOING_AJAX
            || empty($_POST)
            || !isset($_POST['nonce'])
            || !isset($_POST['project_id'])
            || !isset($_POST['comment_id'])
            || !wp_verify_nonce($_POST['nonce'], 'upstream:project.' . ($isApproved ? 'approve_comment' : 'unapprove_comment') . ':' . $_POST['comment_id'])
        ) {
            throw new \Exception(__('Invalid request.', 'upstream'));
        }

        // Check if the user has enough permissions to do this.
        if (!current_user_can('moderate_comments')) {
            throw new \Exception(__("You're not allowed to do this.", 'upstream'));
        }

        // Check if the project potentially exists.
        $project_id = (int)$_POST['project_id'];
        if ($project_id <= 0) {
            throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream'), 'project_id'));
        }

        // Check if the Discussion/Comments section is disabled for the current project.
        if (upstream_are_comments_disabled($project_id)) {
            throw new \Exception(__('Comments are disabled for this project.', 'upstream'));
        }

        $comment = Comment::load($_POST['comment_id']);
        if (!($comment instanceof Comment)) {
            throw new \Exception(__('Comment not found.', 'upstream'));
        }

        $success = (bool)$isApproved ? $comment->approve() : $comment->unapprove();
        if (!$success) {
            throw new \Exception(__('Unable to save the data into database.', 'upstream'));
        }

        return $comment;
    }

    /**
     * AJAX endpoint that unapproves a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function unapproveComment()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'error'   => null,
        );

        try {
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $comment = self::toggleCommentApprovalStatus($comment_id, false);

            $comments = array();
            if ($comment->parent_id > 0) {
                $parentComment = get_comment($comment->parent_id);
                if (is_numeric($parentComment->comment_approved)) {
                    if ((bool)$parentComment->comment_approved) {
                        $comments = array(
                            $comment->parent_id => json_decode(json_encode(array(
                                'created_by' => array(
                                    'name' => $parentComment->comment_author
                                )
                            )))
                        );
                    } else {
                        $user = wp_get_current_user();
                        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                        $userCanModerateComments = !$userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;

                        if ($userCanModerateComments) {
                            $comments = array(
                                $comment->parent_id => json_decode(json_encode(array(
                                    'created_by' => array(
                                        'name' => $parentComment->comment_author
                                    )
                                )))
                            );
                        }
                    }
                }
                unset($parentComment);
            }

            $useAdminLayout = !isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = $comment->render(true, $useAdminLayout, $comments);

            wp_new_comment_notify_moderator($comment->id);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that approves a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function approveComment()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'error'   => null,
        );

        try {
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $comment = self::toggleCommentApprovalStatus($comment_id, true);

            $comments = array();
            if ($comment->parent_id > 0) {
                $parentComment = get_comment($comment->parent_id);
                if (is_numeric($parentComment->comment_approved)) {
                    if ((bool)$parentComment->comment_approved) {
                        $comments = array(
                            $comment->parent_id => json_decode(json_encode(array(
                                'created_by' => array(
                                    'name' => $parentComment->comment_author
                                )
                            )))
                        );
                    } else {
                        $user = wp_get_current_user();
                        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                        $userCanModerateComments = !$userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;

                        if ($userCanModerateComments) {
                            $comments = array(
                                $comment->parent_id => json_decode(json_encode(array(
                                    'created_by' => array(
                                        'name' => $parentComment->comment_author
                                    )
                                )))
                            );
                        }
                    }
                }
                unset($parentComment);
            }

            $useAdminLayout = !isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = $comment->render(true, $useAdminLayout, $comments);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that fetches all comments from a given item/project.
     *
     * @since   1.13.0
     * @static
     */
    public static function fetchComments()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'error'   => null
        );

        try {
            // Check if the request payload is potentially invalid.
            if (
                !defined('DOING_AJAX')
                || !DOING_AJAX
                || empty($_GET)
                || !isset($_GET['nonce'])
                || !isset($_GET['project_id'])
                || !isset($_GET['item_type'])
                || !self::isItemTypeValid($_GET['item_type'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project potentially exists.
            $project_id = (int)$_GET['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Prepare data to verify nonce.
            $commentTargetItemType = strtolower($_GET['item_type']);
            if ($commentTargetItemType !== 'project') {
                if (
                    !isset($_GET['item_id'])
                    || empty($_GET['item_id'])
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }

                $item_id = $_GET['item_id'];

                $nonceIdentifier = 'upstream:project.' . $commentTargetItemType . 's.fetch_comments';
            } else {
                $nonceIdentifier = 'upstream:project.fetch_comments';
            }

            // Verify nonce.
            if (!wp_verify_nonce($_GET['nonce'], $nonceIdentifier)) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $useAdminLayout = !isset($_GET['teeny']) ? true : (bool)$_GET['teeny'] === false;

            $usersCache = array();
            $usersRowset = get_users(array(
                'fields' => array(
                    'ID', 'display_name'
                )
            ));
            foreach ($usersRowset as $userRow) {
                $userRow->ID *= 1;

                $usersCache[$userRow->ID] = (object)array(
                    'id'     => $userRow->ID,
                    'name'   => $userRow->display_name,
                    'avatar' => getUserAvatarURL($userRow->ID)
                );
            }
            unset($userRow, $usersRowset);

            $dateFormat = get_option('date_format');
            $timeFormat = get_option('time_format');
            $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
            $utcTimeZone = new \DateTimeZone('UTC');
            $currentTimezone = upstreamGetTimeZone();
            $currentTimestamp = time();

            $user = wp_get_current_user();
            $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
            $userCanReply = !$userHasAdminCapabilities ? user_can($user, 'publish_project_discussion') : true;
            $userCanModerate = !$userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;
            $userCanDelete = !$userHasAdminCapabilities ? $userCanModerate || user_can($user, 'delete_project_discussion') : true;

            $commentsStatuses = array('approve');
            if ($userHasAdminCapabilities || $userCanModerate) {
                $commentsStatuses[] = 'hold';
            }

            $itemsRowset = (array)get_post_meta($project_id, '_upstream_project_' . $commentTargetItemType . 's', true);
            if (count($itemsRowset) > 0) {
                foreach ($itemsRowset as $row) {
                    if (isset($item_id)) {
                        if ($item_id != $row['id']) {
                            continue;
                        }
                    }

                    $comments = get_comments(array(
                        'post_id'    => $project_id,
                        'status'     => $commentsStatuses,
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key'   => 'type',
                                'value' => $commentTargetItemType
                            ),
                            array(
                                'key'   => 'id',
                                'value' => $row['id']
                            )
                        )
                    ));

                    if (count($comments) > 0) {
                        $commentsCache = array();
                        foreach ($comments as $comment) {
                            $author = $usersCache[(int)$comment->user_id];

                            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $comment->comment_date_gmt, $utcTimeZone);

                            $commentData = json_decode(json_encode(array(
                                'id'         => (int)$comment->comment_ID,
                                'parent_id'  => (int)$comment->comment_parent,
                                'content'    => $comment->comment_content,
                                'state'      => $comment->comment_approved,
                                'created_by' => $author,
                                'created_at' => array(
                                    'localized' => "",
                                    'humanized' => sprintf(
                                        _x('%s ago', '%s = human-readable time difference', 'upstream'),
                                        human_time_diff($date->getTimestamp(), $currentTimestamp)
                                    )
                                ),
                                'currentUserCap' => array(
                                    'can_reply'    => $userCanReply,
                                    'can_moderate' => $userCanModerate,
                                    'can_delete'   => $userCanDelete || $author->id === $user->ID
                                ),
                                'replies' => array()
                            )));

                            $date->setTimezone($currentTimezone);

                            $commentData->created_at->localized = $date->format($theDateTimeFormat);

                            $commentsCache[$commentData->id] = $commentData;
                        }

                        foreach ($commentsCache as $comment) {
                            if ($comment->parent_id > 0) {
                                if (isset($commentsCache[$comment->parent_id])) {
                                    $commentsCache[$comment->parent_id]->replies[] = $comment;
                                } else {
                                    unset($commentsCache[$comment->id]);
                                }
                            }
                        }

                        foreach ($commentsCache as $comment) {
                            if ($comment->parent_id === 0) {
                                ob_start();
                                if ($useAdminLayout) {
                                    upstream_admin_display_message_item($comment, array());
                                } else {
                                    upstream_display_message_item($comment, array());
                                }

                                $response['data'][] = trim(ob_get_contents());
                                ob_end_clean();
                            }
                        }
                    }
                }
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Notify users about new comments.
     * Any user participating on the given discussion will be notified.
     *
     * @since   @todo
     * @static
     *
     * @param   array   $recipients     List of the notification recipients.
     * @param   int     $commentId      The added comment ID.
     *
     * @return  array
     */
    public static function customizeRecipients($recipients, $commentId)
    {
        $comment = get_comment($commentId);
        if ($comment instanceof \WP_Comment
            && $comment->comment_ID > 0
        ) {
            $projectId = $comment->comment_post_ID;

            $project = get_post($projectId);
            if ($project instanceof \WP_Post
                && $project->post_type === 'project'
            ) {
                $itemType = get_comment_meta($commentId, 'type', true);
                if ($itemType === 'project') {
                    // Notify the Project Owner.
                    $projectOwnerId = (int)get_post_meta($projectId, '_upstream_project_owner', true);
                    if ($projectOwnerId > 0) {
                        $projectOwner = get_user_by('id', $projectOwnerId);
                        if ($projectOwner instanceof \WP_User
                            && !empty($projectOwner->user_email)
                        ) {
                            $recipients[] = $projectOwner->user_email;
                        }
                    }

                    // Notify the Project Author.
                    $projectAuthor = get_user_by('id', $project->post_author);
                    if ($projectAuthor instanceof \WP_User
                        && !empty($projectAuthor->user_email)
                    ) {
                        $recipients[] = $projectAuthor->user_email;
                    }
                } else if (in_array($itemType, array('milestone', 'task', 'bug', 'file'))) {
                    $needle = get_comment_meta($commentId, 'item_id', true);

                    // Notify the assignee.
                    $items = (array)get_post_meta($projectId, "_upstream_project_{$itemType}s", true);
                    if (count($items) > 0) {
                        foreach ($items as $item) {
                            if (isset($item['id'])
                                && $item['id'] === $needle
                                && isset($item['assigned_to'])
                                && (int)$item['assigned_to'] > 0
                            ) {
                                $assignee = get_user_by('id', $item['assigned_to']);
                                if ($assignee instanceof \WP_User
                                    && !empty($assignee->user_email)
                                ) {
                                    $recipients[] = $assignee->user_email;
                                }

                                break;
                            }
                        }
                    }
                }

                $recipients = array_unique($recipients);
            }
        }

        return $recipients;
    }
}
