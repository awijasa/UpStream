<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_milestones_disabled()
    && !upstream_disable_milestones()):

$collapseBox = isset($pluginOptions['collapse_project_milestones'])
    && (bool)$pluginOptions['collapse_project_milestones'] === true;

$itemType = 'milestone';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$projectId = upstream_post_id();

$milestones = UpStream_View::getMilestones($projectId);

$l = array(
    'LB_MILESTONE'     => upstream_milestone_label(),
    'LB_TASKS'         => upstream_task_label_plural(),
    'LB_START_DATE'    => __('Start Date', 'upstream'),
    'LB_END_DATE'      => __('End Date', 'upstream'),
    'LB_NONE'          => __('none', 'upstream'),
    'LB_OPEN'          => _x('Open', 'Task status', 'upstream'),
    'LB_NOTES'         => __('Notes', 'upstream'),
    'LB_COMMENTS'      => __('Comments', 'upstream'),
    'MSG_INVALID_USER' => __('invalid user', 'upstream')
);

$areCommentsEnabled = upstreamAreCommentsEnabledOnMilestones();
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
        <i class="fa fa-flag"></i> <?php echo upstream_milestone_label_plural(); ?>
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <a class="collapse-link">
            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
          </a>
        </li>
        <?php do_action('upstream_project_milestones_top_right'); ?>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
      <div class="c-data-table table-responsive">
        <form class="form-inline c-data-table__filters" data-target="#milestones">
          <div class="hidden-xs">
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-search"></i>
                </div>
                <input type="search" class="form-control" placeholder="<?php echo $l['LB_MILESTONE']; ?>" data-column="milestone" data-compare-operator="contains">
              </div>
            </div>
            <div class="form-group">
              <div class="btn-group">
                <a href="#milestones-filters" role="button" class="btn btn-default" data-toggle="collapse" aria-expanded="false" aria-controls="milestones-filters">
                  <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                </a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
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
          </div>
          <div class="visible-xs">
            <div>
              <a href="#milestones-filters" role="button" class="btn btn-default" data-toggle="collapse" aria-expanded="false" aria-controls="milestones-filters">
                <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
              </a>
              <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
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
          </div>
          <div id="milestones-filters" class="collapse">
            <div class="form-group visible-xs">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-search"></i>
                </div>
                <input type="search" class="form-control" placeholder="<?php echo $l['LB_MILESTONE']; ?>" data-column="milestone" data-compare-operator="contains">
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
                  <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_START_DATE']; ?>" id="milestones-filter-start_date">
              </div>
              <input type="hidden" id="milestones-filter-start_date_timestamp" data-column="start_date" data-compare-operator=">=">
            </div>
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_END_DATE']; ?>" id="milestones-filter-end_date">
              </div>
              <input type="hidden" id="milestones-filter-end_date_timestamp" data-column="end_date" data-compare-operator="<=">
            </div>
          </div>
        </form>
        <table
          id="milestones"
          class="o-data-table table table-bordered table-responsive table-hover is-orderable"
          cellspacing="0"
          width="100%"
          data-type="milestone"
          data-ordered-by="start_date"
          data-order-dir="DESC">
          <thead>
            <?php // echo upstream_output_table_header($itemType); ?>
            <tr scope="row">
              <th scope="col" class="is-clickable is-orderable" data-column="milestone" role="button">
                <?php echo $l['LB_MILESTONE']; ?>
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
              <th scope="col" class="" data-column="tasks"><?php echo $l['LB_TASKS']; ?></th>
              <th scope="col" class="is-orderable" data-column="progress" role="button">
                <?php _e('Progress', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="start_date" role="button">
                <?php echo $l['LB_START_DATE']; ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="end_date" role="button">
                <?php echo $l['LB_END_DATE']; ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($milestones as $row): ?>
            <tr class="is-expandable is-filtered" data-id="<?php echo $row['id']; ?>" aria-expanded="false">
              <td class="is-clickable" role="button">
                <i class="fa fa-angle-right"></i>&nbsp;
                <span data-column="milestone" data-value="<?php echo $row['milestone']; ?>"><?php echo $row['milestone']; ?></span>
              </td>
              <td data-column="assigned_to" data-value="<?php echo $row['assigned_to']; ?>">
                <?php if (isset($row['assigned_to'])
                    && is_numeric($row['assigned_to'])
                    && (int)$row['assigned_to'] > 0
                ): ?>
                    <?php if (isset($users[$row['assigned_to']])): ?>
                    <?php echo $users[$row['assigned_to']]; ?>
                    <?php else: ?>
                    <i class="s-text-color-darkred"><?php echo $l['MSG_INVALID_USER']; ?></i>
                    <?php endif; ?>
                <?php else: ?>
                <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="tasks">
                <?php printf(
                    '%d %s / %d %s',
                    isset($row['tasks_open']) ? $row['tasks_open'] : 0,
                    $l['LB_OPEN'],
                    isset($row['tasks_count']) ? $row['tasks_count'] : 0,
                    $l['LB_TASKS']
                ); ?>
              </td>
              <td data-column="progress" data-value="<?php echo $row['progress']; ?>"><?php echo $row['progress']; ?>%</td>
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
              <td colspan="6">
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
                    <?php echo upstreamRenderCommentsBox($row['id'], 'milestone', $projectId, false, true); ?>
                  </div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php // echo upstream_output_table_rows(get_the_ID(), $itemType); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
