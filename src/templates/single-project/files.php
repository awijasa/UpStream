<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_files_disabled()
    && !upstream_disable_files()):

$collapseBox = isset($pluginOptions['collapse_project_files'])
    && (bool)$pluginOptions['collapse_project_files'] === true;

$itemType = 'file';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$rowset = array();
$projectId = upstream_post_id();

$meta = (array)get_post_meta($projectId, '_upstream_project_files', true);
foreach ($meta as $data) {
    if (!isset($data['id'])
        || !isset($data['created_by'])
    ) {
        continue;
    }

    $data['created_by'] = (int)$data['created_by'];
    $data['created_time'] = isset($data['created_time']) ? (int)$data['created_time'] : 0;
    $data['title'] = isset($data['title']) ? (string)$data['title'] : '';
    $data['file_id'] = (int)$data['file_id'];
    $data['description'] = isset($data['description']) ? (string)$data['description'] : '';

    $rowset[$data['id']] = $data;
}

$l = array(
    'LB_TITLE'       => _x('Title', "File's title", 'upstream'),
    'LB_NONE'        => __('none', 'upstream'),
    'LB_DESCRIPTION' => __('Description', 'upstream'),
    'LB_COMMENTS'    => __('Comments', 'upstream'),
    'LB_FILE'        => __('File', 'upstream'),
    'LB_UPLOADED_AT' => __('Uploaded at', 'upstream')
);

$areCommentsEnabled = upstreamAreCommentsEnabledOnFiles();
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
        <i class="fa fa-file"></i> <?php echo upstream_file_label_plural(); ?>
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <a class="collapse-link">
            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
          </a>
        </li>
        <?php do_action('upstream_project_files_top_right'); ?>
      </ul>
      <div class="clearfix"></div>
    </div>
    <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
      <div class="c-data-table table-responsive">
        <form class="form-inline c-data-table__filters" data-target="#files">
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
              <select class="form-control o-select2" data-column="created_by" data-placeholder="<?php _e('Uploader', 'upstream'); ?>">
                <option value></option>
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
              <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_UPLOADED_AT']; ?>" id="files-filter-uploaded_at_from">
            </div>
            <input type="hidden" id="files-filter-uploaded_at_from_timestamp" data-column="created_time" data-compare-operator=">=">
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
          id="files"
          class="o-data-table table table-striped table-bordered table-responsive is-orderable"
          cellspacing="0"
          width="100%"
          data-type="file"
          data-ordered-by="created_at"
          data-order-dir="DESC">
          <thead>
            <tr scope="row">
              <th scope="col" class="is-clickable is-orderable" data-column="title" role="button" style="width: 25%;">
                <?php echo $l['LB_TITLE']; ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="created_by" role="button">
                <?php _e('Uploaded by', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="created_at" role="button">
                <?php _e('Uploaded at', 'upstream'); ?>
                <span class="pull-right o-order-direction">
                  <i class="fa fa-sort"></i>
                </span>
              </th>
              <th scope="col" class="is-orderable" data-column="file" data-type="file">
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
              <td data-column="created_by" data-value="<?php echo (int)$row['created_by'] > 0 ? $row['created_by'] : '__none__'; ?>">
                <?php if ((int)$row['created_by'] > 0): ?>
                    <?php if (isset($users[$row['created_by']])): ?>
                    <?php echo $users[$row['created_by']]; ?>
                    <?php else: ?>
                    <i class="s-text-color-darkred"><?php echo $l['MSG_INVALID_USER']; ?></i>
                    <?php endif; ?>
                <?php else: ?>
                <i class="s-text-color-gray"><?php echo $l['LB_NONE']; ?></i>
                <?php endif; ?>
              </td>
              <td data-column="created_at" data-value="<?php echo $row['created_time']; ?>">
                <?php echo upstream_convert_UTC_date_to_timezone($row['created_time'], false); ?>
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
              <td colspan="4">
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
                    <?php echo upstreamRenderCommentsBox($row['id'], 'file', $projectId, false, true); ?>
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
