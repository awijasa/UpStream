<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_bugs_disabled()
    && !upstream_disable_bugs()):

$collapseBox = isset($pluginOptions['collapse_project_bugs'])
    && (bool)$pluginOptions['collapse_project_bugs'] === true;

$bugsSettings = get_option('upstream_bugs');
$bugsStatuses = $bugsSettings['statuses'];
$bugsSeverities = $bugsSettings['severities'];

$itemType = 'bug';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$rowset = array();
$projectId = upstream_post_id();

$meta = (array)get_post_meta($projectId, '_upstream_project_bugs', true);
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
    $data['description'] = isset($data['description']) ? (string)$data['description'] : '';
    $data['severity'] = isset($data['severity']) ? (string)$data['severity'] : '';
    $data['status'] = isset($data['status']) ? (string)$data['status'] : '';
    $data['start_date'] = !isset($data['start_date']) || !is_numeric($data['start_date']) || $data['start_date'] < 0 ? 0 : (int)$data['start_date'];
    $data['end_date'] = !isset($data['end_date']) || !is_numeric($data['end_date']) || $data['end_date'] < 0 ? 0 : (int)$data['end_date'];

    $rowset[$data['id']] = $data;
}
unset($data, $meta);

$l = array(
    'LB_TITLE'         => _x('Title', "Bug's title", 'upstream'),
    'LB_NONE'          => __('none', 'upstream'),
    'LB_DESCRIPTION'   => __('Description', 'upstream'),
    'LB_COMMENTS'      => __('Comments', 'upstream'),
    'MSG_INVALID_USER' => sprintf(
        _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
        strtolower(__('User'))
    ),
    'LB_DUE_DATE'      => __('Due Date', 'upstream')
);

$areCommentsEnabled = upstreamAreCommentsEnabledOnBugs();
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
        <i class="fa fa-bug"></i> <?php echo upstream_bug_label_plural(); ?>
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <a class="collapse-link">
            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
          </a>
        </li>
        <?php do_action('upstream_project_bugs_top_right'); ?>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
      <div class="c-data-table table-responsive">
        <form class="form-inline c-data-table__filters" data-target="#bugs">
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
                <i class="fa fa-asterisk"></i>
              </div>
              <select class="form-control o-select2" data-column="severity" data-placeholder="<?php _e('Severity', 'upstream'); ?>">
                <option value></option>
                <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                <optgroup label="<?php _e('Severity', 'upstream'); ?>">
                  <?php foreach ($bugsSeverities as $severity): ?>
                  <option value="<?php echo $severity['name']; ?>"><?php echo $severity['name']; ?></option>
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
                  <?php foreach ($bugsStatuses as $status): ?>
                  <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
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
              <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_DUE_DATE']; ?> <?php echo $l['LB_DUE_DATE']; ?>" id="tasks-filter-due_date_from">
            </div>
            <input type="hidden" id="tasks-filter-due_date_from_timestamp" data-column="due_date" data-compare-operator=">=">
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
          id="bugs"
          class="o-data-table table table-striped table-bordered table-responsive is-orderable"
          cellspacing="0"
          width="100%"
          data-type="bug"
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
              <th scope="col" class="is-orderable" data-column="severity" role="button">
                <?php _e('Severity', 'upstream'); ?>
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
              <th scope="col" class="is-orderable" data-column="due_date" role="button">
                <?php _e('Due Date', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" data-column="file" data-type="file">
                <?php _e('File', 'upstream'); ?>
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
              <td data-column="severity" data-value="<?php echo !empty($row['severity']) ? $row['severity'] : '__none__'; ?>">
                <?php if (!empty($row['severity'])): ?>
                <?php echo $row['severity']; ?>
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
              <td data-column="due_date" data-value="<?php echo $row['due_date']; ?>">
                <?php if ($row['due_date'] > 0): ?>
                  <?php echo upstream_convert_UTC_date_to_timezone($row['due_date'], false); ?>
                <?php else: ?>
                  <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="file">
                <?php
                if (strlen($row['file']) > 0) {
                  if (@is_array(getimagesize($row['file']))) {
                    printf(
                      '<a href="%s" target="_blank">
                        <img class="avatar itemfile" width="32" height="32" src="%1$s">
                      </a>',
                      $row['file']
                    );
                  } else {
                    printf(
                      '<a href="%s" target="_blank">%s</a>',
                      $row['file'],
                      basename($row['file'])
                    );
                  }
                } else {
                  echo '<i class="s-text-color-gray">'. $l['LB_NONE'] .'</i>';
                }
                ?>
              </td>
            </tr>
            <tr data-parent="<?php echo $row['id']; ?>" style="display: none;">
              <td colspan="6">
                <div class="hidden-xs">
                  <div class="form-group">
                    <label><?php echo $l['LB_DESCRIPTION']; ?></label>
                    <?php
                    if (isset($row['description'])
                        && strlen($row['description']) > 0
                    ): ?>
                    <blockquote><?php echo $row['description']; ?></blockquote>
                    <?php else: ?>
                    <p>
                      <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                    </p>
                    <?php endif; ?>
                  </div>
                  <?php if ($areCommentsEnabled): ?>
                  <div class="form-group">
                    <label><?php echo $l['LB_COMMENTS']; ?></label>
                    <?php echo upstreamRenderCommentsBox($row['id'], 'bug', $projectId, false, true); ?>
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
