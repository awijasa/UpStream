<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

use \UpStream\Traits\Singleton;
use \Cmb2Grid\Grid\Cmb2Grid;

/**
 * Clients Metabox Class.
 *
 * @package     UpStream
 * @subpackage  Admin\Metaboxes
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2017 UpStream Project Management
 * @license     GPL-3
 * @since       1.11.0
 * @final
 */
final class UpStream_Metaboxes_Clients
{
    use Singleton;

    /**
     * The post type where this metabox will be used.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postType = 'client';

    /**
     * String that represents the singular form of the post type's name.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postTypeLabelSingular = null;

    /**
     * String that represents the plural form of the post type's name.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postTypeLabelPlural = null;

    /**
     * Prefix used on form fields.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $prefix = '_upstream_client_';

    /**
     * Class constructor.
     *
     * @since   1.11.0
     */
    public function __construct()
    {
        self::$postTypeLabelSingular = upstream_client_label();
        self::$postTypeLabelPlural = upstream_client_label_plural();

        self::attachHooks();

        // Enqueues the default ThickBox assets.
        add_thickbox();

        // Render all inner metaboxes.
        self::renderMetaboxes();

        $namespace = get_class(self::$instance);
        // Starting from v1.13.6 UpStream Users cannot be added through here anymore.
        $noticeIdentifier = 'upstream:notices.client.add_new_users_changes';
        $shouldDisplayNotice = (bool)get_option($noticeIdentifier);
        if (!$shouldDisplayNotice) {
            add_action('admin_notices', array($namespace, 'renderAddingClientUsersChangesNotice'));
            update_option($noticeIdentifier, 1);
        }
    }

    /**
     * Render all inner-metaboxes.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderMetaboxes()
    {
        self::renderDetailsMetabox();
        self::renderLogoMetabox();

        $namespace = get_class(self::$instance);
        $metaboxesCallbacksList = array('createUsersMetabox');
        foreach ($metaboxesCallbacksList as $callbackName) {
            add_action('add_meta_boxes', array($namespace, $callbackName));
        }
    }

    /**
     * Retrieve all Client Users associated with a given client.
     *
     * @since   1.11.0
     * @access  private
     * @static
     *
     * @param   int     $client_id  The reference id.
     *
     * @return  array
     */
    private static function getUsersFromClient($client_id)
    {
        if ((int)$client_id <= 0) {
            return array();
        }

        // Let's cache all users basic info so we don't have to query each one of them later.
        global $wpdb;
        $rowset = $wpdb->get_results(sprintf('
            SELECT `ID`, `display_name`, `user_login`, `user_email`
            FROM `%s`',
            $wpdb->prefix . 'users'
        ));

        // Create our users hash map.
        $users = array();
        foreach ($rowset as $row) {
            $users[(int)$row->ID] = array(
                'id'    => (int)$row->ID,
                'name'  => $row->display_name,
                'email' => $row->user_email
            );
        }
        unset($rowset);

        $clientUsersList = array();
        $clientUsersIdsList = array();

        // Retrieve all client users.
        $meta = (array)get_post_meta($client_id, '_upstream_new_client_users');
        if (!empty($meta)) {
            foreach ($meta[0] as $clientUser) {
                if (!empty($clientUser) && is_array($clientUser) && isset($users[$clientUser['user_id']]) && !in_array($clientUser['user_id'], $clientUsersIdsList)) {
                    $user = $users[$clientUser['user_id']];

                    $user['assigned_at'] = $clientUser['assigned_at'];
                    $user['assigned_by'] = $users[$clientUser['assigned_by']]['name'];

                    array_push($clientUsersList, (object)$user);
                    array_push($clientUsersIdsList, $clientUser['user_id']);
                }
            }
        }

        return $clientUsersList;
    }

    /**
     * Renders the modal's html which is used to associate existent client users with a client.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderAddExistentUserModal()
    {
        ?>
        <div id="modal-add-existent-user" style="display: none;">
            <div class="upstream-row">
                <p><?php echo sprintf(__('These are all the users assigned with the role <code>%s</code> and not related to this client yet.', 'upstream'), __('UpStream Client User', 'upstream')); ?></p>
            </div>
            <div class="upstream-row">
                <table id="table-add-existent-users" class="wp-list-table widefat fixed striped posts upstream-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 20px;">
                                <input type="checkbox" />
                            </th>
                            <th><?php echo __('Name', 'upstream'); ?></th>
                            <th><?php echo __('Email', 'upstream'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3"><?php echo __('No users found.', 'upstream'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="upstream-row submit"></div>
        </div>
        <?php
    }

    /**
     * Renders the modal's html which is used to migrate legacy client users.
     *
     * @since   1.11.0
     * @access  private
     * @static
     * @deprecated  1.13.6
     */
    private static function renderMigrateUserModal()
    {
        _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');
    }

    /**
     * Renders the users metabox.
     * This is where all client users are listed.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderUsersMetabox()
    {
        $client_id = get_the_id();
        $usersList = self::getUsersFromClient($client_id);
        ?>

        <div class="upstream-row">
            <a
                id="add-existent-user"
                name="<?php echo __('Add Existing Users', 'upstream'); ?>"
                href="#TB_inline?width=600&height=300&inlineId=modal-add-existent-user"
                class="thickbox button"
            ><?php echo __('Add Existing Users', 'upstream'); ?></a>
        </div>
        <div class="upstream-row">
            <table id="table-users" class="wp-list-table widefat fixed striped posts upstream-table">
                <thead>
                    <tr>
                        <th><?php echo __('Name', 'upstream'); ?></th>
                        <th><?php echo __('Email', 'upstream'); ?></th>
                        <th><?php echo __('Assigned by', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Assigned at', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Remove?', 'upstream'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usersList) > 0):
                    $instanceTimezone = upstreamGetTimeZone();
                    $dateFormat = get_option('date_format') . ' ' . get_option('time_format');

                    foreach ($usersList as $user):
                    $assignedAt = new DateTime($user->assigned_at);
                    // Convert the date, which is in UTC, to the instance's timezone.
                    $assignedAt->setTimeZone($instanceTimezone);
                    ?>
                    <tr data-id="<?php echo $user->id; ?>">
                        <td>
                            <a title="<?php echo sprintf(__("Managing %s's Permissions"), $user->name); ?>" href="#TB_inline?width=600&height=425&inlineId=modal-user-permissions" class="thickbox"><?php echo $user->name; ?></a>
                        </td>
                        <td><?php echo $user->email; ?></td>
                        <td><?php echo $user->assigned_by; ?></td>
                        <td class="text-center"><?php echo $assignedAt->format($dateFormat); ?></td>
                        <td class="text-center">
                            <a href="#" onclick="javascript:void(0);" class="up-u-color-red" data-remove-user>
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr data-empty>
                        <td colspan="5"><?php echo __("There are no users assigned yet.", 'upstream'); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <span class="dashicons dashicons-info"></span> <?php echo __('Removing a user only means that they will no longer be associated with this client. Their WordPress account will not be deleted.', 'upstream'); ?>
            </p>
        </div>

        <?php
        self::renderUserPermissionsModal();
        self::renderAddExistentUserModal();
    }

    /**
     * It defines the Users metabox.
     *
     * @since   1.11.0
     * @static
     */
    public static function createUsersMetabox()
    {
        add_meta_box(
            self::$prefix . 'users',
            '<span class="dashicons dashicons-groups"></span>' . __("Users", 'upstream'),
            array(get_class(self::$instance), 'renderUsersMetabox'),
            self::$postType,
            'normal'
        );
    }

    /**
     * It defines the Legacy Users metabox.
     * This metabox lists all legacy client users that couldn't be automatically
     * migrated for some reason, which is also displayed here.
     *
     * If there's no legacy user to be migrated, the box is not shown.
     *
     * @since   1.11.0
     * @static
     * @deprecated  1.13.6
     */
    public static function createLegacyUsersMetabox()
    {
        _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');
    }

    /**
     * Renders the Legacy Users metabox.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderLegacyUsersMetabox()
    {
        $client_id = upstream_post_id();

        $legacyUsersErrors = get_post_meta($client_id, '_upstream_client_legacy_users_errors')[0];

        $legacyUsersMeta = get_post_meta($client_id, '_upstream_client_users')[0];
        $legacyUsers = array();
        foreach ($legacyUsersMeta as $a) {
            $legacyUsers[$a['id']] = $a;
        }
        unset($legacyUsersMeta);
        ?>
        <div class="upstream-row">
            <p><?php echo __('The users listed below are those old <code>UpStream Client Users</code> that could not be automatically converted/migrated to <code>WordPress Users</code> by UpStream for some reason. More details on the Disclaimer metabox.', 'upstream'); ?></p>
        </div>
        <div class="upstream-row">
            <table id="table-legacy-users" class="wp-list-table widefat fixed striped posts upstream-table">
                <thead>
                    <tr>
                        <th><?php echo __('First Name', 'upstream'); ?></th>
                        <th><?php echo __('Last Name', 'upstream'); ?></th>
                        <th><?php echo __('Email', 'upstream'); ?></th>
                        <th><?php echo __('Phone', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Migrate?', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Discard?', 'upstream'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($legacyUsersErrors as $legacyUserId => $legacyUserError):
                $user = $legacyUsers[$legacyUserId];
                $userFirstName = isset($user['fname']) ? trim($user['fname']) : '';
                $userLastName = isset($user['lname']) ? trim($user['lname']) : '';
                $userEmail = isset($user['email']) ? trim($user['email']) : '';
                $userPhone = isset($user['phone']) ? trim($user['phone']) : '';

                switch ($legacyUserError) {
                    case 'ERR_EMAIL_NOT_AVAILABLE':
                        $errorMessage = __("This email address is already being used by another user.", 'upstream');
                        break;
                    case 'ERR_EMPTY_EMAIL':
                        $errorMessage = __("Email addresses cannot be empty.", 'upstream');
                        break;
                    case 'ERR_INVALID_EMAIL':
                        $errorMessage = __("Invalid email address.", 'upstream');
                        break;
                    default:
                        $errorMessage = $legacyUserError;
                        break;
                }

                $emptyValueString = '<i>' . __('empty', 'upstream') .'</i>';
                ?>
                    <tr data-id="<?php echo $legacyUserId; ?>">
                        <td data-column="fname"><?php echo !empty($userFirstName) ? $userFirstName : $emptyValueString; ?></td>
                        <td data-column="lname"><?php echo !empty($userLastName) ? $userLastName : $emptyValueString; ?></td>
                        <td data-column="email"><?php echo !empty($userEmail) ? $userEmail : $emptyValueString; ?></td>
                        <td data-column="phone"><?php echo !empty($userPhone) ? $userPhone : $emptyValueString; ?></td>
                        <td class="text-center">
                            <a name="<?php echo __('Migrating Client User', 'upstream'); ?>" href="#TB_inline?width=350&height=400&inlineId=modal-migrate-user" class="thickbox" data-modal-identifier="user-migration">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </a>
                        </td>
                        <td class="text-center">
                            <a href="#" onclick="javascript:void(0);" class="up-u-color-red" data-action="legacyUser:discard">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <tr data-id="<?php echo $legacyUserId; ?>">
                        <td colspan="7">
                            <span class="dashicons dashicons-warning"></span>&nbsp;<?php echo $errorMessage; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renders the Details metabox using CMB2.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderDetailsMetabox()
    {
        $metabox = new_cmb2_box(array(
            'id'           => self::$prefix . 'details',
            'title'        => '<span class="dashicons dashicons-admin-generic"></span>' . __('Details', 'upstream'),
            'object_types' => array(self::$postType),
            'context'      => 'side',
            'priority'     => 'high'
        ));

        $phoneField = $metabox->add_field(array(
            'name' => __('Phone Number', 'upstream'),
            'id'   => self::$prefix . 'phone',
            'type' => 'text'
        ));

        $websiteField = $metabox->add_field(array(
            'name' => __('Website', 'upstream'),
            'id'   => self::$prefix . 'website',
            'type' => 'text_url'
        ));

        $addressField = $metabox->add_field(array(
            'name' => __('Address', 'upstream'),
            'id'   => self::$prefix . 'address',
            'type' => 'textarea_small'
        ));

        $metaboxGrid = new Cmb2Grid($metabox);
        $metaboxGridRow = $metaboxGrid->addRow(array($phoneField, $websiteField, $addressField));
    }

    /**
     * Renders Logo metabox using CMB2.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderLogoMetabox()
    {
        $metabox = new_cmb2_box(array(
            'id'           => self::$prefix . 'logo',
            'title'        => '<span class="dashicons dashicons-format-image"></span>' . __("Logo", 'upstream'),
            'object_types' => array(self::$postType),
            'context'      => 'side',
            'priority'     => 'core'
        ));

        $logoField = $metabox->add_field(array(
            'id'   => self::$prefix . 'logo',
            'type' => 'file',
            'name' => __('Image URL', 'upstream')
        ));

        $metaboxGrid = new Cmb2Grid($metabox);
        $metaboxGridRow = $metaboxGrid->addRow(array($logoField));
    }

    /**
     * Ajax endpoint responsible for removing Client Users from a given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function removeUser()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $userId = (int)@$_POST['user'];
            if ($userId <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            $clientUsersMetaKey = '_upstream_new_client_users';
            $meta = (array)get_post_meta($clientId, $clientUsersMetaKey);
            if (!empty($meta)) {
                $newClientUsersList = array();
                foreach ($meta[0] as $clientUser) {
                    if (!empty($clientUser) && is_array($clientUser)) {
                        if ((int)$clientUser['user_id'] !== $userId) {
                            array_push($newClientUsersList, $clientUser);
                        }
                    }
                }

                update_post_meta($clientId, $clientUsersMetaKey, $newClientUsersList);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for fetching all Client Users that are not related to
     * the given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function fetchUnassignedUsers()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_GET) || !isset($_GET['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_GET['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $clientUsers = self::getUsersFromClient($clientId);
            $excludeTheseIds = array(get_current_user_id());
            if (count($clientUsers) > 0) {
                foreach ($clientUsers as $clientUser) {
                    array_push($excludeTheseIds, $clientUser->id);
                }
            }

            $rowset = get_users(array(
                'exclude'  => $excludeTheseIds,
                'role__in' => array('upstream_client_user'),
                'orderby'  => 'ID'
            ));

            global $wp_roles;

            foreach ($rowset as $row) {
                $user = array(
                    'id'       => $row->ID,
                    'name'     => $row->display_name,
                    'username' => $row->user_login,
                    'email'    => $row->user_email
                );

                array_push($response['data'], $user);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for associating existent Client Users to a given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function addExistentUsers()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            if (!isset($_POST['users']) && empty($_POST['users'])) {
                throw new \Exception(__('Users IDs cannot be empty.', 'upstream'));
            }

            $currentUser = get_userdata(get_current_user_id());
            $nowTimestamp = time();
            $now = date('Y-m-d H:i:s', $nowTimestamp);

            $clientUsersMetaKey = '_upstream_new_client_users';
            $clientUsersList = (array)get_post_meta($clientId, $clientUsersMetaKey, true);
            $clientNewUsersList = array();

            $usersIdsList = (array)$_POST['users'];
            foreach ($usersIdsList as $user_id) {
                $user_id = (int)$user_id;
                if ($user_id > 0) {
                    array_push($clientUsersList, array(
                        'user_id'     => $user_id,
                        'assigned_by' => $currentUser->ID,
                        'assigned_at' => $now
                    ));
                }
            }

            foreach ($clientUsersList as $clientUser) {
                $clientUser = (array)$clientUser;
                $clientUser['user_id'] = (int)$clientUser['user_id'];

                if (!isset($clientNewUsersList[$clientUser['user_id']])) {
                    $clientNewUsersList[$clientUser['user_id']] = $clientUser;
                }
            }
            update_post_meta($clientId, $clientUsersMetaKey, array_values($clientNewUsersList));

            global $wpdb;

            $rowset = (array)$wpdb->get_results(sprintf('
                SELECT `ID`, `display_name`, `user_login`, `user_email`
                FROM `%s`
                WHERE `ID` IN ("%s")',
                $wpdb->prefix . 'users',
                implode('", "', $usersIdsList)
            ));

            $assignedAt = upstream_convert_UTC_date_to_timezone($now);

            foreach ($rowset as $user) {
                array_push($response['data'], array(
                    'id'          => (int)$user->ID,
                    'name'        => $user->display_name,
                    'email'       => $user->user_email,
                    'assigned_by' => $currentUser->display_name,
                    'assigned_at' => $assignedAt
                ));
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for migrating a given Legacy Client User.
     *
     * @since   1.11.0
     * @static
     * @deprecated  1.13.6
     */
    public static function migrateLegacyUser()
    {
        header('Content-Type: application/json');

        global $wpdb;

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for discard a given Legacy Client User.
     *
     * @since   1.11.0
     * @static
     * @deprecated  1.13.6
     */
    public static function discardLegacyUser()
    {
        header('Content-Type: application/json');

        global $wpdb;

        $response = array(
            'success' => false,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__("Invalid UpStream Client ID.", 'upstream'));
            }

            _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Renders the modal's html which is used to manage a given Client User's permissions.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderUserPermissionsModal()
    {
        ?>
        <div id="modal-user-permissions" style="display: none;">
            <div id="form-user-permissions">
                <div>
                    <h3><?php echo __("UpStream's Custom Permissions", 'upstream'); ?></h3>
                    <table class="wp-list-table widefat fixed striped posts upstream-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 20px;">
                                    <input type="checkbox" />
                                </th>
                                <th><?php echo __('Permission', 'upstream'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div>
                    <div class="up-form-group">
                        <button
                            type="submit"
                            class="button button-primary"
                            data-label="<?php echo __('Update Permissions', 'upstream'); ?>"
                            data-loading-label="<?php echo __('Updating...', 'upstream'); ?>"
                        ><?php echo __('Update Permissions', 'upstream'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Ajax endpoint responsible for fetching all permissions a given Client User might have.
     *
     * @since   1.11.0
     * @static
     */
    public static function fetchUserPermissions()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_GET) || !isset($_GET['client']) || !isset($_GET['user'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $client_id = (int)$_GET['client'];
            if ($client_id <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $client_user_id = (int)$_GET['user'];
            if ($client_user_id <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            if (!upstream_do_client_user_belongs_to_client($client_user_id, $client_id)) {
                throw new \Exception(__("This Client User is not associated with this Client.", 'upstream'));
            }

            $response['data'] = array_values(upstream_get_client_user_permissions($client_user_id));

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for updating a given Client User permissions.
     *
     * @since   1.11.0
     * @static
     */
    public static function updateUserPermissions()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $client_id = (int)$_POST['client'];
            if ($client_id <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $client_user_id = isset($_POST['user']) ? (int)$_POST['user'] : 0;
            if ($client_user_id <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            if (!upstream_do_client_user_belongs_to_client($client_user_id, $client_id)) {
                throw new \Exception(__("This Client User is not associated with this Client.", 'upstream'));
            }

            $clientUser = new \WP_User($client_user_id);
            if (array_search('upstream_client_user', $clientUser->roles) === false) {
                throw new \Exception(__("This user doesn't seem to be a valid Client User.", 'upstream'));
            }

            if (isset($_POST['permissions']) && !empty($_POST['permissions'])) {
                $permissions = upstream_get_client_users_permissions();
                $newPermissions = (array)$_POST['permissions'];

                $deniedPermissions = (array)array_diff(array_keys($permissions), $newPermissions);
                foreach ($deniedPermissions as $permissionKey) {
                    // Make sure this is a valid permission.
                    if (isset($permissions[$permissionKey])) {
                        $clientUser->add_cap($permissionKey, false);
                    }
                }

                foreach ($newPermissions as $permissionKey) {
                    // Make sure this is a valid permission.
                    if (isset($permissions[$permissionKey])) {
                        $clientUser->add_cap($permissionKey, true);
                    }
                }
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Add notice to users warning about Client Users creation changes.
     *
     * @since   1.13.6
     * @static
     */
    public static function renderAddingClientUsersChangesNotice()
    {
        ?>
        <div class="notice notice-info is-dismissible">
          <h3><?php _e('Important notice', 'upstream'); ?></h3>
          <p><?php _e('New users can no longer be added through here.', 'upstream'); ?></p>
          <p><?php _e('From now on, to ensure newly created users are listed on the Existing Users table, do the following:', 'upstream'); ?></p>
          <ol>
            <li>
              <?php printf(
                  'Go to the %s page',
                  sprintf(
                      '<a href="%s" target="_blank">%s</a>',
                      admin_url('user-new.php'),
                      __('Users')
                  )
              ); ?>
            </li>
            <li><?php _e("Add the users as you need if you haven't already", 'upstream'); ?></li>
            <li>
              <?php printf(
                  'Make sure they have the %s role assigned to them',
                  sprintf(
                      '<code>%s</code>',
                      __('UpStream Client User', 'upstream')
                  )
              ); ?>
            </li>
          </ol>
        </div>
        <?php
    }

    /**
     * Attach all hooks.
     *
     * @since   1.13.6
     * @static
     */
    public static function attachHooks()
    {
        // Define all ajax endpoints.
        $ajaxEndpointsSchema = array(
            'remove_user'             => 'removeUser',
            'fetch_unassigned_users'  => 'fetchUnassignedUsers',
            'add_existent_users'      => 'addExistentUsers',
            'migrate_legacy_user'     => 'migrateLegacyUser',
            'discard_legacy_user'     => 'discardLegacyUser',
            'fetch_user_permissions'  => 'fetchUserPermissions',
            'update_user_permissions' => 'updateUserPermissions'
        );

        $namespace = get_class(self::$instance);
        foreach ($ajaxEndpointsSchema as $endpoint => $callbackName) {
            add_action('wp_ajax_upstream:client.' . $endpoint, array($namespace, $callbackName));
        }
    }
}
