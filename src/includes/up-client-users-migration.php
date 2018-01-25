<?php
namespace UpStream\Migrations;

// Prevent direct access.
if (!defined('ABSPATH')) exit;

/**
 * @final
 * @deprecated  1.13.6
 */
final class ClientUsers
{
    private static $db;
    private static $usersCache = array(
        'data'     => array(),
        'username' => array(),
        'email'    => array(),
        'projects' => array()
    );
    private static $clientUsersCache = array(
        'clients' => array(),
        'users'   => array()
    );
    private static $projectsCache = array();

    private static $projectsWithNewMembers = array();
    private static $newUsersMap = array();
    private static $projects = array();

    private static function prepareMigration()
    {
        global $wpdb;
        self::$db = &$wpdb;

        self::cacheUsers();
        self::cacheClientUsers();
        self::cacheProjects();
    }

    private static function cacheProjects()
    {
        $db = &self::$db;

        $rowset = $db->get_results('
            SELECT `post_id`, `meta_key`, `meta_value`
            FROM `'. $db->prefix .'postmeta`
            WHERE `meta_key` IN ("_upstream_project_activity", "_upstream_project_discussion", "_upstream_project_members", "_upstream_project_milestones", "_upstream_project_tasks", "_upstream_project_bugs", "_upstream_project_files", "_upstream_project_client_users")'
        );

        if (count($rowset) > 0) {
            foreach ($rowset as $row) {
                $project_id = (int)$row->post_id;

                if (!isset(self::$projects[$project_id])) {
                    self::$projects[$project_id] = array(
                        'id'         => $project_id,
                        'discussion' => array(),
                        'members'    => array(),
                        'milestones' => array(),
                        'tasks'      => array(),
                        'bugs'       => array(),
                        'files'      => array(),
                        'client_users' => array(),
                        'has_changed' => false
                    );
                }

                $metaKeyType = str_replace('_upstream_project_', '', $row->meta_key);

                self::$projects[$project_id][$metaKeyType] = maybe_unserialize($row->meta_value);

                if ($metaKeyType === 'members' || $metaKeyType === 'client_users') {
                    foreach (self::$projects[$project_id][$metaKeyType] as $memberIndex => $memberId) {
                        $memberId = (string)$memberId;

                        self::$projects[$project_id][$metaKeyType][$memberIndex] = $memberId;

                        if (!is_numeric($memberId) && isset(self::$clientUsersMap[$memberId])) {
                            array_push(self::$clientUsersMap[$memberId]['projects'], $project_id);
                        }
                    }
                }
            }
        }
    }

    private static function isMigrationNeeded()
    {
        global $wpdb;

        $clientsCount = (int)$wpdb->get_var('
            SELECT COUNT(`ID`) AS qty
            FROM `' . $wpdb->prefix . 'posts`
            WHERE `post_type` = "client"'
        );

        if ($clientsCount === 0) {
            return false;
        }

        return (string)get_option('upstream:attemptedToMigrateLegacyClientUsers') !== 'yes';
    }

    public static function run()
    {
        return _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');

        // Check if we really need to run the migration.
        if (!self::isMigrationNeeded()) {
            return;
        }

        self::prepareMigration();

        $db = &self::$db;

        if (count(self::$clientUsersMap) === 0) {
            return;
        }

        $clientUsersHavingErrors = array();
        $clientUsersMetaKey = '_upstream_new_client_users';
        $currentUser = get_userdata(get_current_user_id());

        foreach (self::$clientUsersMap as $rawClientUser) {
            $rawClientUser = (array)$rawClientUser;
            $client_id = (int)$rawClientUser['client_id'];

            // Check if the client user is potentially valid.
            if (empty($rawClientUser) || !isset($rawClientUser['id']) || empty($rawClientUser['id'])) {
                // There's no need to worry about this user since doesn't have an id.
                continue;
            }

            // Check if user's email address is potentially acceptable.
            $clientUserEmail = isset($rawClientUser['email']) ? trim($rawClientUser['email']) : null;
            if (empty($clientUserEmail)) {
                if (!isset($clientUsersHavingErrors[$client_id])) {
                    $clientUsersHavingErrors[$client_id] = array();
                }

                $clientUsersHavingErrors[$client_id][$rawClientUser['id']] = 'ERR_EMPTY_EMAIL';

                continue;
            } else if (!is_email($clientUserEmail) || !filter_var($clientUserEmail, FILTER_VALIDATE_EMAIL)) {
                if (!isset($clientUsersHavingErrors[$client_id])) {
                    $clientUsersHavingErrors[$client_id] = array();
                }

                $clientUsersHavingErrors[$client_id][$rawClientUser['id']] = 'ERR_INVALID_EMAIL';

                continue;
            }

            // Check if user's email address is unique.
            if (!self::isUserEmailUnique($clientUserEmail)) {
                if (!isset($clientUsersHavingErrors[$client_id])) {
                    $clientUsersHavingErrors[$client_id] = array();
                }

                $clientUsersHavingErrors[$client_id][$rawClientUser['id']] = 'ERR_EMAIL_NOT_AVAILABLE';

                continue;
            }

            $clientUserFirstName = isset($rawClientUser['fname']) ? trim($rawClientUser['fname']) : '';
            $clientUserLastName = isset($rawClientUser['lname']) ? trim($rawClientUser['lname']) : '';

            $clientUserName = $clientUserFirstName;

            if (!empty($clientUserLastName)) {
                $clientUserName .= ' ' . $clientUserLastName;
            } else {
                $clientUserName = $clientUserEmail;
            }

            $userData = array(
                'user_login'    => $clientUserEmail,
                'user_pass'     => $clientUserEmail,
                'user_nicename' => $clientUserEmail,
                'user_email'    => $clientUserEmail,
                'display_name'  => $clientUserName,
                'nickname'      => $clientUserName,
                'first_name'    => $clientUserFirstName,
                'last_name'     => $clientUserLastName,
                'role'          => 'upstream_client_user'
            );

            $userId = wp_insert_user($userData);
            if (is_wp_error($userId)) {
                if (!isset($clientUsersHavingErrors[$client_id])) {
                    $clientUsersHavingErrors[$client_id] = array();
                }

                $clientUsersHavingErrors[$client_id][$rawClientUser['id']] = $userId->get_error_message();

                continue;
            }

            self::$newUsersMap[$rawClientUser['id']] = $userId;

            if (isset($rawClientUser['phone']) && !empty($rawClientUser['phone'])) {
                add_user_meta($userId, 'phone', trim($rawClientUser['phone']), false);
            }

            $clientUserRoleCapabilities = array('publish_project_tasks', 'publish_project_bugs', 'publish_project_files', 'publish_project_discussion');
            if (isset($rawClientUser['capability'])) {
                $notAllowedCapabilites = (array)array_diff($clientUserRoleCapabilities, $rawClientUser['capability']);
            } else {
                $notAllowedCapabilites = $clientUserRoleCapabilities;
            }

            if (isset($notAllowedCapabilites) && count($notAllowedCapabilites) > 0) {
                $user = new \WP_User($userId);
                foreach ($notAllowedCapabilites as $userCapability) {
                    $user->add_cap($userCapability, false);
                }
                unset($user);
            }

            $clientUsersList = (array)get_post_meta($client_id, $clientUsersMetaKey, true);
            array_push($clientUsersList, array(
                'user_id'     => $userId,
                'assigned_by' => $currentUser->ID,
                'assigned_at' => date('Y-m-d H:i:s', time())
            ));
            update_post_meta($client_id, $clientUsersMetaKey, $clientUsersList);

            // Convert the user id on projects metas.
            foreach (self::$clientUsersMap[$rawClientUser['id']]['projects'] as $projectId) {
                $project = self::$projects[$projectId];

                $memberIndex = array_search($rawClientUser['id'], $project['members']);
                $project['members'][$memberIndex] = (string)$userId;

                foreach (array('milestones', 'tasks', 'bugs', 'files') as $itemType) {
                    if (isset($project[$itemType]) && !empty($project[$itemType])) {
                        foreach ($project[$itemType] as $projectItemIndex => $projectItem) {
                            if ((string)$projectItem['created_by'] === $rawClientUser['id']) {
                                $projectItem['created_by'] = (string)$userId;
                                $project[$itemType][$projectItemIndex] = $projectItem;
                            }
                        }
                    }
                }

                if (!empty($project['client_users'])) {
                    $legacyIdIndex = array_search($rawClientUser['id'], $project['client_users']);
                    if ($legacyIdIndex !== false) {
                        $project['client_users'][$legacyIdIndex] = $userId;
                    }
                }

                $project['has_changed'] = true;

                self::$projects[$projectId] = $project;
            }
        }

        if (count($clientUsersHavingErrors) > 0) {
            foreach ($clientUsersHavingErrors as $client_id => $flaggedUsers) {
                update_post_meta($client_id, '_upstream_client_legacy_users_errors', $flaggedUsers);
            }
        }

        $convertUsersLegacyIdFromHaystack = function(&$haystack) {
            foreach ($haystack as &$needle) {
                if (isset(self::$newUsersMap[$needle])) {
                    $needle = (string)self::$newUsersMap[$needle];
                }
            }
        };

        foreach (self::$projects as $project) {
            if ($project['has_changed']) {
                foreach (array('members', 'milestones', 'tasks', 'bugs', 'files', 'discussion', 'client_users') as $itemType) {
                    update_post_meta($project['id'], '_upstream_project_' . $itemType, $project[$itemType]);
                }

                // Update project activity.
                $projectActivities = (array)get_post_meta($project['id'], '_upstream_project_activity');
                if (!empty($projectActivities)) {
                    foreach ($projectActivities[0] as $activityIndex => $activity) {
                        $activityUserId = (string)$activity['user_id'];
                        if (isset(self::$newUsersMap[$activityUserId])) {
                            $activity['user_id'] = self::$newUsersMap[$activityUserId];
                        }

                        if (isset($activity['fields'])) {
                            if (isset($activity['fields']['single'])) {
                                foreach ($activity['fields']['single'] as $activitySingleIndentifier => $activitySingle) {
                                    if ($activitySingleIndentifier === '_upstream_project_client_users') {
                                        if (isset($activitySingle['add'])) {
                                            if (is_array($activitySingle['add'])) {
                                                $convertUsersLegacyIdFromHaystack($activitySingle['add']);
                                            }
                                        }

                                        if (isset($activitySingle['from'])) {
                                            if (is_array($activitySingle['from'])) {
                                                $convertUsersLegacyIdFromHaystack($activitySingle['from']);
                                            }
                                        }

                                        if (isset($activitySingle['to'])) {
                                            if (is_array($activitySingle['to'])) {
                                                $convertUsersLegacyIdFromHaystack($activitySingle['to']);
                                            }
                                        }
                                    }

                                    $activity['fields']['single'][$activitySingleIndentifier] = $activitySingle;
                                }
                            }

                            if (isset($activity['fields']['group'])) {
                                foreach ($activity['fields']['group'] as $groupIdentifier => $groupData) {
                                    if (isset($groupData['add'])) {
                                        foreach ($groupData['add'] as $rowIndex => $row) {
                                            if (isset($row['created_by']) && isset(self::$newUsersMap[$row['created_by']])) {
                                                $row['created_by'] = self::$newUsersMap[$row['created_by']];
                                                $groupData['add'][$rowIndex] = $row;
                                            }
                                        }
                                    }

                                    if (isset($groupData['remove'])) {
                                        foreach ($groupData['remove'] as $rowIndex => $row) {
                                            if (isset($row['created_by']) && isset(self::$newUsersMap[$row['created_by']])) {
                                                $row['created_by'] = self::$newUsersMap[$row['created_by']];
                                                $groupData['remove'][$rowIndex] = $row;
                                            }
                                        }
                                    }

                                    $activity['fields']['group'][$groupIdentifier] = $groupData;
                                }
                            }
                        }

                        $projectActivities[0][$activityIndex] = $activity;
                    }

                    update_post_meta($project['id'], '_upstream_project_activity', $projectActivities[0]);
                }
            }
        }

        $db->delete($db->prefix . 'postmeta', array('meta_key' => '_upstream_client_password'));

        update_option('upstream:attemptedToMigrateLegacyClientUsers', 'yes');
    }

    protected static function isUserUsernameUnique($username)
    {
        return !isset(self::$usersCache['username'][$username]);
    }

    protected static function isUserEmailUnique($email)
    {
        return !isset(self::$usersCache['email'][$email]);
    }

    protected static function cacheUser($id, $username, $email, $projects = array())
    {
        if (!self::isUserUsernameUnique($username) || !self::isUserEmailUnique($email)) {
            return;
        }

        $user = array(
            'id'       => (int)$id,
            'username' => $username,
            'email'    => $email,
            'projects' => $projects
        );

        self::$usersCache['data'][$user['id']] = $user;
        self::$usersCache['username'][$user['username']] = &self::$usersCache['data'][$user['id']];
        self::$usersCache['email'][$user['email']] = &self::$usersCache['data'][$user['id']];
    }

    protected static function cacheUsers()
    {
        $db = &self::$db;

        $rowset = $db->get_results(sprintf('
            SELECT `ID`, `user_login`, `user_email`
            FROM `%s`',
            $db->prefix . 'users'
        ));

        if (count($rowset) > 0) {
            foreach ($rowset as $rowIndex => $row) {
                self::cacheUser($row->ID, $row->user_login, $row->user_email);
            }
        }
    }

    private static $clientUsersMap = array();
    private static $clientsMap = array();

    private static function cacheClientUsers()
    {
        $db = &self::$db;

        $rowset = (array)$db->get_results(sprintf('
            SELECT `post_id`, `meta_value`
            FROM `%s`
            WHERE `meta_key` = "_upstream_client_users"',
            $db->prefix . 'postmeta'
        ));

        foreach ($rowset as $row) {
            $client_id = (int)$row->post_id;

            self::$clientsMap[$client_id] = array();

            $clientUsersMapList = (array)maybe_unserialize($row->meta_value);
            foreach ($clientUsersMapList as $clientUser) {
                if (is_array($clientUser) && !empty($clientUser)) {
                    $clientUser['id'] = (string)$clientUser['id'];
                    $clientUser['client_id'] = $client_id;
                    $clientUser['projects'] = array();

                    self::$clientUsersMap[$clientUser['id']] = $clientUser;
                    array_push(self::$clientsMap[$client_id], $clientUser['id']);
                }
            }
        }
    }

    public static function insertNewClientUser($data, $client_id)
    {
        return _doing_it_wrong(__FUNCTION__, 'This method is deprecated and it will be removed on future releases.', '1.13.5');

        global $wpdb;

        $data = json_decode(json_encode($data));
        $client_id = (int)$client_id;

        if ($client_id <= 0) {
            throw new \Exception(__("Invalid UpStream Client ID.", 'upstream'));
        }

        if (empty($data) || empty($data->id)) {
            throw new \Exception(__("Invalid UpStream Client User.", 'upstream'));
        }

        $userEmail = isset($data->email) ? trim((string)$data->email) : '';
        if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL) || !is_email($userEmail)) {
            throw new \Exception(__("Invalid email address.", 'upstream'));
        } else {
            $emailExists = (bool)$wpdb->get_var(sprintf('
                SELECT COUNT(`ID`)
                FROM `%s`
                WHERE `user_email` = "%s"',
                $wpdb->prefix . 'users',
                $userEmail
            ));

            if ($emailExists) {
                throw new \Exception(__("This email address is not available.", 'upstream'));
            }
        }

        $userPassword = isset($data->password) ? (string)$data->password : '';
        if (strlen($userPassword) < 6) {
            throw new \Exception(__("Passwords must be at least 6 characters long.", 'upstream'));
        }

        $userPasswordConfirmation = isset($data->password_c) ? (string)$data->password_c : '';
        if (strcmp($userPassword, $userPasswordConfirmation) !== 0) {
            throw new \Exception(__("Passwords don't match.", 'upstream'));
        }

        $userFirstName = isset($data->fname) ? trim((string)$data->fname) : '';
        $userLastName = isset($data->lname) ? trim((string)$data->lname) : '';
        $userDisplayName = $userFirstName;

        if (!empty($userDisplayName) && !empty($userLastName)) {
            $userDisplayName .= ' ' . $userLastName;
        }

        if (empty($userDisplayName)) {
            $userDisplayName = $userEmail;
        }

        $userData = array(
            'user_login'    => $userEmail,
            'user_pass'     => $userPassword,
            'user_nicename' => $userDisplayName,
            'user_email'    => $userEmail,
            'display_name'  => $userDisplayName,
            'nickname'      => $userDisplayName,
            'first_name'    => $userFirstName,
            'last_name'     => $userLastName,
            'role'          => 'upstream_client_user'
        );

        $user_id = wp_insert_user($userData);
        if (is_wp_error($user_id)) {
            throw new \Exception($user_id->get_error_message());
        }

        $currentUser = get_userdata(get_current_user_id());

        $assignedAt = date('Y-m-d H:i:s', time());

        $clientUsersMetaKey = '_upstream_new_client_users';
        $clientUsersList = (array)get_post_meta($client_id, $clientUsersMetaKey, true);
        array_push($clientUsersList, array(
            'user_id'     => $user_id,
            'assigned_by' => $currentUser->ID,
            'assigned_at' => $assignedAt
        ));
        update_post_meta($client_id, $clientUsersMetaKey, $clientUsersList);

        $response = array(
            'legacy_id'      => $data->id,
            'id'             => $user_id,
            'name'           => $userDisplayName,
            'email'          => $userEmail,
            'assigned_at'    => upstream_convert_UTC_date_to_timezone($assignedAt),
            'assigned_by_id' => $currentUser->ID,
            'assigned_by'    => $currentUser->display_name
        );

        return $response;
    }
}
