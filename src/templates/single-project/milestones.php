<?php
if (!defined('ABSPATH')) exit;
?>

<?php if (!upstream_are_milestones_disabled() && !upstream_disable_milestones()):
$pluginOptions = get_option('upstream_general');
$collapseBox = isset($pluginOptions['collapse_project_milestones']) && (bool)$pluginOptions['collapse_project_milestones'] === true;
?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-flag"></i> <?php echo upstream_milestone_label_plural(); ?></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i></a></li>
                    <?php do_action( 'upstream_project_milestones_top_right' ); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
                <table id="milestones" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 4, &quot;asc&quot; ]]" data-type="milestone">
                    <thead>
                        <?php echo upstream_output_table_header( 'milestones' ); ?>
                    </thead>
                    <tbody>
                        <?php echo upstream_output_table_rows( get_the_ID(), 'milestones' ); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
