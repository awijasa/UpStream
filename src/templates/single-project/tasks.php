<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_tasks_disabled()
    && !upstream_disable_tasks()):

$collapseBox = isset($pluginOptions['collapse_project_tasks'])
    && (bool)$pluginOptions['collapse_project_tasks'] === true;

$tasksStatuses = get_option('upstream_tasks');
$tasksStatuses = $tasksStatuses['statuses'];

$itemType = 'task';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$rowset = array();
$projectId = upstream_post_id();

$milestones = array();
$meta = (array)get_post_meta($projectId, '_upstream_project_milestones', true);
foreach ($meta as $data) {
    if (!isset($data['id'])
        || !isset($data['created_by'])
        || !isset($data['milestone'])
    ) {
        continue;
    }

    $milestones[$data['id']] = $data['milestone'];
  }

$meta = (array)get_post_meta($projectId, '_upstream_project_tasks', true);
foreach ($meta as $data) {
    if (!isset($data['id'])
        || !isset($data['created_by'])
    ) {
        continue;
    }

    $data['created_by'] = (int)$data['created_by'];
    $data['created_time'] = isset($data['created_time']) ? (int)$data['created_time'] : 0;
    $data['assigned_to'] = isset($data['assigned_to']) ? (int)$data['assigned_to'] : 0;
    $data['assigned_to_name'] = isset($users[$data['assigned_to']]) ? $users[$data['assigned_to']] : '';
    $data['progress'] = isset($data['progress']) ? (float)$data['progress'] : 0.00;
    $data['notes'] = isset($data['notes']) ? (string)$data['notes'] : '';
    $data['status'] = isset($data['status']) ? (string)$data['status'] : '';
    $data['milestone'] = isset($data['milestone']) ? (string)$data['milestone'] : '';
    $data['start_date'] = !isset($data['start_date']) || !is_numeric($data['start_date']) || $data['start_date'] < 0 ? 0 : (int)$data['start_date'];
    $data['end_date'] = !isset($data['end_date']) || !is_numeric($data['end_date']) || $data['end_date'] < 0 ? 0 : (int)$data['end_date'];

    $rowset[$data['id']] = $data;
}
unset($data, $meta);

$l = array(
    'LB_MILESTONE' => upstream_milestone_label(),
    'LB_TITLE' => _x('Title', "Task's title", 'upstream'),
    'LB_NONE'  => __('none', 'upstream'),
    'LB_NOTES'         => __('Notes', 'upstream'),
    'LB_COMMENTS'      => __('Comments', 'upstream'),
    'MSG_INVALID_USER' => sprintf(
        _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
        strtolower(__('User'))
    ),
    'MSG_INVALID_MILESTONE' => __('invalid milestone', 'upstream'),
    'LB_START_DATE'    => __('Start Date', 'upstream'),
    'LB_END_DATE'      => __('End Date', 'upstream')
);

$l['MSG_INVALID_MILESTONE'] = sprintf(
    _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
    strtolower($l['LB_MILESTONE'])
);

$areCommentsEnabled = upstreamAreCommentsEnabledOnTasks();
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
        <i class="fa fa-wrench"></i> <?php echo upstream_task_label_plural(); ?>
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <a class="collapse-link">
            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
          </a>
        </li>
        <?php do_action('upstream_project_tasks_top_right'); ?>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
      <div class="c-data-table table-responsive">
        <form class="form-inline c-data-table__filters" data-target="#tasks">
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-search"></i>
              </div>
              <input type="search" class="form-control" placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title" data-compare-operator="contains">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-user"></i>
              </div>
              <select class="form-control o-select2" data-column="assigned_to" data-placeholder="<?php _e('Assignee', 'upstream'); ?>">
                <option value></option>
                <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                <optgroup label="<?php _e('Users'); ?>">
                  <?php foreach ($users as $user_id => $userName): ?>
                    <?php if ($user_id === $currentUserId) continue; ?>
                    <option value="<?php echo $user_id; ?>"><?php echo $userName; ?></option>
                    <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-bookmark"></i>
              </div>
              <select class="form-control o-select2" data-column="status" data-placeholder="<?php _e('Status', 'upstream'); ?>">
                <option value></option>
                <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                <optgroup label="<?php _e('Status', 'upstream'); ?>">
                  <?php foreach ($tasksStatuses as $status): ?>
                  <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-flag"></i>
              </div>
              <select class="form-control o-select2" data-column="milestone" data-placeholder="<?php echo $l['LB_MILESTONE']; ?>">
                <option value></option>
                <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                <optgroup label="<?php echo upstream_milestone_label_plural(); ?>">
                  <?php foreach ($milestones as $milestone_id => $milestone): ?>
                  <option value="<?php echo $milestone_id; ?>"><?php echo $milestone; ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </div>
              <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_START_DATE']; ?>" id="tasks-filter-start_date">
            </div>
            <input type="hidden" id="tasks-filter-start_date_timestamp" data-column="start_date" data-compare-operator=">=">
          </div>
          <div class="form-group">
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </div>
              <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_END_DATE']; ?>" id="tasks-filter-end_date">
            </div>
            <input type="hidden" id="tasks-filter-end_date_timestamp" data-column="end_date" data-compare-operator="<=">
          </div>
          <div class="form-group">
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-download"></i>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-right">
                <li>
                  <a href="#" data-action="export" data-type="txt">
                    <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php _e('Plain Text', 'upstream'); ?>
                  </a>
                </li>
                <li>
                  <a href="#" data-action="export" data-type="csv">
                    <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php _e('CSV', 'upstream'); ?>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </form>
        <table
          id="tasks"
          class="o-data-table table table-striped table-bordered table-responsive is-orderable"
          cellspacing="0"
          width="100%"
          data-type="task"
          data-ordered-by="start_date"
          data-order-dir="DESC">
          <thead>
            <tr scope="row">
              <th scope="col" class="is-clickable is-orderable" data-column="title" role="button" style="width: 25%;">
                <?php _e('Title', "Task's title", 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="assigned_to" role="button">
                <?php _e('Assigned To', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="status" role="button">
                <?php _e('Status', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="progress" role="button">
                <?php _e('Progress', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="milestone" role="button">
                <?php echo $l['LB_MILESTONE']; ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="start_date" role="button">
                <?php _e('Start Date', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="end_date" role="button">
                <?php _e('End Date', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rowset as $row): ?>
            <tr class="is-expandable is-filtered" data-id="<?php echo $row['id']; ?>" aria-expanded="false">
              <td class="is-clickable" role="button">
                <i class="fa fa-angle-right"></i>&nbsp;
                <span data-column="title" data-value="<?php echo $row['title']; ?>"><?php echo $row['title']; ?></span>
              </td>
              <td data-column="assigned_to" data-value="<?php echo (int)$row['assigned_to'] > 0 ? $row['assigned_to'] : '__none__'; ?>">
                <?php if ((int)$row['assigned_to'] > 0): ?>
                    <?php if (isset($users[$row['assigned_to']])): ?>
                    <?php echo $users[$row['assigned_to']]; ?>
                    <?php else: ?>
                    <i class="s-text-color-darkred"><?php echo $l['MSG_INVALID_USER']; ?></i>
                    <?php endif; ?>
                <?php else: ?>
                <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="status" data-value="<?php echo !empty($row['status']) ? $row['status'] : '__none__'; ?>">
                <?php if (!empty($row['status'])): ?>
                <?php echo $row['status']; ?>
                <?php else: ?>
                <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="progress" data-value="<?php echo $row['progress']; ?>"><?php echo $row['progress']; ?>%</td>
              <td data-column="milestone" data-value="<?php echo !empty($row['milestone']) ? $row['milestone'] : '__none__'; ?>">
                <?php if (!empty($row['milestone'])): ?>
                  <?php if (isset($milestones[$row['milestone']])): ?>
                    <?php echo $milestones[$row['milestone']]; ?>
                  <?php else: ?>
                    <i class="s-text-color-darkred"><?php echo $l['MSG_INVALID_MILESTONE']; ?></i>
                  <?php endif; ?>
                <?php else: ?>
                <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="start_date" data-value="<?php echo $row['start_date']; ?>">
                <?php if ($row['start_date'] > 0): ?>
                  <?php echo upstream_convert_UTC_date_to_timezone($row['start_date'], false); ?>
                <?php else: ?>
                  <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="end_date" data-value="<?php echo $row['end_date']; ?>">
                <?php if ($row['end_date'] > 0): ?>
                  <?php echo upstream_convert_UTC_date_to_timezone($row['end_date'], false); ?>
                <?php else: ?>
                  <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
            </tr>
            <tr data-parent="<?php echo $row['id']; ?>" style="display: none;">
              <td colspan="7">
                <div class="hidden-xs">
                  <div class="form-group">
                    <label><?php echo $l['LB_NOTES']; ?></label>
                    <?php
                    if (isset($row['notes'])
                        && strlen($row['notes']) > 0
                    ): ?>
                    <blockquote><?php echo $row['notes']; ?></blockquote>
                    <?php else: ?>
                    <p>
                      <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                    </p>
                    <?php endif; ?>
                  </div>
                  <?php if ($areCommentsEnabled): ?>
                  <div class="form-group">
                    <label><?php echo $l['LB_COMMENTS']; ?></label>
                    <?php echo upstreamRenderCommentsBox($row['id'], 'task', $projectId, false, true); ?>
                  </div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
