<?php
if (!defined('ABSPATH')) exit;

$project_id = (int)get_the_ID();
$bugsLabel = upstream_bug_label_plural();

$user = upstream_user_data();
$userIsClientUser = $user['role'] === 'Project Client User';

$itemType = 'bugs';
?>

<?php if (!upstream_disable_bugs() && !upstream_are_bugs_disabled()):
$pluginOptions = get_option('upstream_general');
$collapseBox = isset($pluginOptions['collapse_project_bugs']) && (bool)$pluginOptions['collapse_project_bugs'] === true;
?>
<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">

        <div class="x_title">
            <h2><i class="fa fa-bug"></i> <?php echo upstream_bug_label_plural(); ?></h2>

            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i></a></li>
                <?php do_action( 'upstream_project_bugs_top_right' ) ?>
            </ul>

            <div class="clearfix"></div>
        </div>

        <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
            <?php if (!$userIsClientUser): ?>
                <div>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#bugs-table-wrapper" aria-controls="bugs-table-wrapper" role="tab" data-toggle="tab"><?php printf(__('All %s', 'upstream'), $bugsLabel); ?></a>
                        </li>
                        <li role="presentation">
                            <a href="#my-bugs-table-wrapper" aria-controls="my-bugs-table-wrapper" role="tab" data-toggle="tab"><?php _e('Assigned to me only', 'upstream'); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content" style="margin-top: 7px;">
                        <div role="tabpanel" class="tab-pane active" id="bugs-table-wrapper">
                            <table id="bugs" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 4, &quot;asc&quot; ]]" data-type="bug">
                                <thead>
                                    <?php echo upstream_output_table_header($itemType); ?>
                                </thead>
                                <tbody>
                                    <?php echo upstream_output_table_rows($project_id, $itemType); ?>
                                </tbody>
                            </table>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="my-bugs-table-wrapper">
                            <table id="my-bugs" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 4, &quot;asc&quot; ]]" data-type="bug">
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
                <table id="bugs" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 4, &quot;asc&quot; ]]" data-type="bug">
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
