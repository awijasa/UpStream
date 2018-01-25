<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

use UpStream\Traits\Singleton;

/**
 * @since   @todo
 */
class UpStream_View
{
    use Singleton;

    protected static $project = null;

    protected static $milestones = array();
    protected static $tasks = array();

    protected static $users = array();

    public function __construct()
    {
        self::$namespace = get_class(self::$instance);
    }

    public static function setProject($id = 0)
    {
        self::$project = new UpStream_Project($id);
    }

    public static function getProject($id = 0)
    {
        if (empty($project)) {
            self::setProject($id);
        }

        return self::$project;
    }

    public static function getMilestones($projectId = 0)
    {
        $project = self::getProject($projectId);

        if (count(self::$milestones) === 0) {
            $data = array();
            $rowset = array_filter((array)$project->get_meta('milestones'));

            foreach ($rowset as $row) {
                $row['created_by'] = (int)$row['created_by'];
                $row['created_time'] = isset($row['created_time']) ? (int)$row['created_time'] : 0;
                $row['assigned_to'] = isset($row['assigned_to']) ? (int)$row['assigned_to'] : 0;
                $row['progress'] = isset($row['progress']) ? (float)$row['progress'] : 0.00;
                $row['notes'] = isset($row['notes']) ? (string)$row['notes'] : '';
                $row['start_date'] = !isset($row['start_date']) || !is_numeric($row['start_date']) || $row['start_date'] < 0 ? 0 : (int)$row['start_date'];
                $row['end_date'] = !isset($row['end_date']) || !is_numeric($row['end_date']) || $row['end_date'] < 0 ? 0 : (int)$row['end_date'];

                $data[$row['id']] = $row;
            }

            $data = apply_filters('upstream_project_milestones', $data, $projectId);

            self::$milestones = $data;
        } else {
            $data = self::$milestones;
        }

        return $data;
    }

    protected static function getUsers()
    {
        if (count(self::$users) === 0) {
            self::$users = upstreamGetUsersMap();
        }

        return self::$users;
    }

    public static function getTasks($projectId = 0)
    {
        $project = self::getProject($projectId);

        if (count(self::$milestones) === 0) {
            $data = array();
            $rowset = array_filter((array)$project->get_meta('milestones'));

            foreach ($rowset as $row) {
                $row['created_by'] = (int)$row['created_by'];
                $row['created_time'] = isset($row['created_time']) ? (int)$row['created_time'] : 0;
                $row['assigned_to'] = isset($row['assigned_to']) ? (int)$row['assigned_to'] : 0;
                $row['progress'] = isset($row['progress']) ? (float)$row['progress'] : 0.00;
                $row['notes'] = isset($row['notes']) ? (string)$row['notes'] : '';
                $row['start_date'] = !isset($row['start_date']) || !is_numeric($row['start_date']) || $row['start_date'] < 0 ? 0 : (int)$row['start_date'];
                $row['end_date'] = !isset($row['end_date']) || !is_numeric($row['end_date']) || $row['end_date'] < 0 ? 0 : (int)$row['end_date'];

                $data[$row['id']] = $row;
            }

            $data = apply_filters('upstream_project_milestones', $data, $projectId);

            self::$milestones = $data;
        } else {
            $data = self::$milestones;
        }

        return $data;
    }
}
