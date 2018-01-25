<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;
?>

<?php if (!upstream_are_files_disabled() && !upstream_disable_files()):
$pluginOptions = get_option('upstream_general');
$collapseBox = isset($pluginOptions['collapse_project_files']) && (bool)$pluginOptions['collapse_project_files'] === true;
?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">

            <div class="x_title">
                <h2><i class="fa fa-file"></i> <?php _e( 'Files', 'upstream' ); ?></h2>

                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i></a></li>
                    <?php do_action( 'upstream_project_files_top_right' ); ?>
                </ul>

                <div class="clearfix"></div>
            </div>

            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">

                <table id="files" class="datatable table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%" data-order="[[ 0, &quot;asc&quot; ]]" data-type="file">
                    <thead>
                        <?php echo upstream_output_table_header( 'files' ); ?>
                    </thead>
                    <tbody>
                        <?php echo upstream_output_table_rows( get_the_ID(), 'files' ); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
