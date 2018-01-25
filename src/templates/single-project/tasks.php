<?php
if (!defined('ABSPATH')) exit;

$project_id = (int)get_the_ID();
$tasksLabel = upstream_task_label_plural();

$user = upstream_user_data();
$userIsClientUser = $user['role'] === 'Project Client User';

$itemType = 'tasks';
?>

<?php if (!upstream_are_tasks_disabled() && !upstream_disable_tasks()):
$pluginOptions = get_option('upstream_general');
$collapseBox = isset($pluginOptions['collapse_project_tasks']) && (bool)$pluginOptions['collapse_project_tasks'] === true;
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
                <?php do_action( 'upstream_project_tasks_top_right' ); ?>
            </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
            <?php if (!$userIsClientUser): ?>
            <div>
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tasks-table-wrapper" aria-controls="tasks-table-wrapper" role="tab" data-toggle="tab"><?php printf(__('All %s', 'upstream'), $tasksLabel); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#my-tasks-table-wrapper" aria-controls="my-tasks-table-wrapper" role="tab" data-toggle="tab"><?php _e('Assigned to me only', 'upstream'); ?></a>
                    </li>
                </ul>
                <div class="tab-content" style="margin-top: 7px;">
                    <div role="tabpanel" class="tab-pane active" id="tasks-table-wrapper">
                        <table id="tasks" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 5, &quot;asc&quot; ]]" data-type="task">
                            <thead>
                                <?php echo upstream_output_table_header($itemType); ?>
                            </thead>
                            <tbody>
                                <?php echo upstream_output_table_rows($project_id, $itemType); ?>
                            </tbody>
                        </table>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="my-tasks-table-wrapper">
                        <table id="my-tasks" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 5, &quot;asc&quot; ]]" data-type="task">
                            <thead>
                                <?php echo upstream_output_table_header($itemType); ?>
                            </thead>
                            <tbody>
                                <?php echo upstream_output_table_rows($project_id, $itemType, true); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <table id="tasks" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 5, &quot;asc&quot; ]]" data-type="task">
                <thead>
                    <?php echo upstream_output_table_header($itemType); ?>
                </thead>
                <tbody>
                    <?php echo upstream_output_table_rows($project_id, $itemType); ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
