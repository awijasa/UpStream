<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UpStream_Metaboxes_Projects' ) ) :


/**
 * CMB2 Theme Options
 * @version 0.1.0
 */
class UpStream_Metaboxes_Projects {


    /**
     * Post type
     * @var string
     */
    public $type = 'project';

    /**
     * Metabox prefix
     * @var string
     */
    public $prefix = '_upstream_project_';

    public $project_label = '';

    /**
     * Holds an instance of the object
     *
     * @var Myprefix_Admin
     **/
    public static $instance = null;

    /**
     * Indicates if comments section is enabled.
     *
     * @since   1.13.0
     * @access  private
     * @static
     *
     * @var     bool    $allowProjectComments
     */
    private static $allowProjectComments = true;

    public function __construct() {
        $this->project_label = upstream_project_label();

        do_action('upstream_admin_notices_errors');

        // Ensure WordPress can generate and display custom slugs for the project by making it public temporarily fast.
        add_action('edit_form_before_permalink', array($this, 'makeProjectTemporarilyPublic'));
        // Ensure the made public project are non-public as it should.
        add_action('edit_form_after_title', array($this, 'makeProjectPrivateOnceAgain'));

        add_action('cmb2_render_comments', array($this, 'renderCommentsField'), 10, 5);
    }

    /**
     * Returns the running object
     *
     * @return Myprefix_Admin
     **/
    public static function get_instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();

            if (upstream_post_id() > 0) {
                self::$instance->overview();
            }

            if (!upstream_disable_milestones()) {
                self::$instance->milestones();
            }

            if (!upstream_disable_tasks()) {
                self::$instance->tasks();
            }

            if(!upstream_disable_bugs()) {
                self::$instance->bugs();
            }

            if (!upstream_disable_files()) {
                self::$instance->files();
            }

            self::$instance->details();
            self::$instance->sidebar_low();

            self::$allowProjectComments = upstreamAreProjectCommentsEnabled();

            if (self::$allowProjectComments) {
                self::$instance->comments();
            }

            do_action('upstream_details_metaboxes');
        }

        return self::$instance;
    }

/* ======================================================================================
                                        OVERVIEW
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function overview() {
        $areMilestonesDisabled = upstream_are_milestones_disabled();
        $areMilestonesDisabledAtAll = upstream_disable_milestones();
        $areTasksDisabled = upstream_are_tasks_disabled();
        $areBugsDisabled = upstream_are_bugs_disabled();

        if ((!$areMilestonesDisabled && $areMilestonesDisabledAtAll) || !$areTasksDisabled || !$areBugsDisabled) {
            $metabox = new_cmb2_box( array(
                'id'            => $this->prefix . 'overview',
                'title'         => $this->project_label . __( ' Overview', 'upstream' ) .
                    '<span class="progress align-right"><progress value="' . upstream_project_progress() . '" max="100"></progress> <span>' . upstream_project_progress() . '%</span></span>',
                'object_types'  => array( $this->type ),
            ) );

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            $columnsList = array();

            if (!$areMilestonesDisabled && !$areMilestonesDisabledAtAll) {
                array_push($columnsList, $metabox->add_field( array(
                    'name'  => '<span>' . upstream_count_total( 'milestones', upstream_post_id() ) . '</span> ' . upstream_milestone_label_plural(),
                    'id'    => $this->prefix . 'milestones',
                    'type'  => 'title',
                    'after' => 'upstream_output_overview_counts'
                )));
            }

            if (!upstream_disable_tasks()) {
                if (!$areTasksDisabled) {
                    $grid2 = $metabox->add_field( array(
                        'name'              => '<span>' . upstream_count_total( 'tasks', upstream_post_id() ) . '</span> ' . upstream_task_label_plural(),
                        'desc'              => '',
                        'id'                => $this->prefix . 'tasks',
                        'type'              => 'title',
                        'after'             => 'upstream_output_overview_counts',
                    ) );
                    array_push($columnsList, $grid2);
                }
            }

            if (!$areBugsDisabled) {
                $grid3 = $metabox->add_field( array(
                    'name'              => '<span>' . upstream_count_total( 'bugs', upstream_post_id() ) . '</span> ' . upstream_bug_label_plural(),
                    'desc'              => '',
                    'id'                => $this->prefix . 'bugs',
                    'type'              => 'title',
                    'after'             => 'upstream_output_overview_counts',
                ) );
                array_push($columnsList, $grid3);
            }

            //Create now a Grid of group fields
            $row = $cmb2Grid->addRow();
            $row->addColumns($columnsList);
        }
    }


/* ======================================================================================
                                        MILESTONES
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function milestones() {
        $areMilestonesDisabled = upstream_are_milestones_disabled();
        $areMilestonesDisabledAtAll = upstream_disable_milestones();
        $userHasAdminPermissions = upstream_admin_permissions('disable_project_milestones');

        if ($areMilestonesDisabledAtAll || ($areMilestonesDisabled && !$userHasAdminPermissions)) {
            return;
        }

        $label          = upstream_milestone_label();
        $label_plural   = upstream_milestone_label_plural();

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'milestones',
            'title'         => '<span class="dashicons dashicons-flag"></span> ' . esc_html( $label_plural ),
            'object_types'  => array( $this->type )
        ) );

        //Create a default grid
        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        /*
         * Outputs some hidden data for dynamic use.
         */
        $metabox->add_field( array(
            'id'                => $this->prefix . 'hidden',
            'type'              => 'title',
            'description'       => '',
            'after'             => 'upstream_admin_output_milestone_hidden_data',
            'attributes'        => array(
                'class'             => 'hidden',
                'data-publish'      => upstream_admin_permissions( 'publish_project_milestones' ),
            )
        ) );

        if (!$areMilestonesDisabled) {
            $group_field_id = $metabox->add_field( array(
                'id'                => $this->prefix . 'milestones',
                'type'              => 'group',
                'description'       => '',
                'permissions'       => 'delete_project_milestones', // also set on individual row level
                'options'           => array(
                    'group_title'   => esc_html( $label ) . " {#}",
                    'add_button'    => sprintf( __( "Add %s", 'upstream' ), esc_html( $label ) ),
                    'remove_button' => sprintf( __( "Delete %s", 'upstream' ), esc_html( $label ) ),
                    'sortable'      => upstream_admin_permissions( 'sort_project_milestones' ),
                ),
                'after_group' =>
                    $this->getFiltersHeaderHtml() .
                    $this->getAssignedToFilterHtml() .
                    $this->getFiltersFooterHtml()
            ) );

            $fields = array();

            $fields[0] = array(
                'id'            => 'id',
                'type'          => 'text',
                'before'        => 'upstream_add_field_attributes',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );

            $allowComments = upstreamAreCommentsEnabledOnMilestones();
            if ($allowComments) {
                $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __('Data', 'upstream') . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
            }

            $fields[1] = array(
                'id'            => 'created_by',
                'type'          => 'text',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );
            $fields[2] = array(
                'id'            => 'created_time',
                'type'          => 'text',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );


            // start row
            $fields[10] = array(
                'name'              => esc_html( $label ),
                'id'                => 'milestone',
                'type'              => 'select',
                //'show_option_none' => true, // ** IMPORTANT - enforce a value in this field.
                // An row with no value here is considered to be a deleted row.
                'permissions'       => 'milestone_milestone_field',
                'before'            => 'upstream_add_field_attributes',
                'options_cb'        => 'upstream_admin_get_options_milestones',
                'attributes'        => array(
                    'class' => 'milestone',
                )
            );

            $fields[11] = array(
                'name'              => __( "Assigned To", 'upstream' ),
                'id'                => 'assigned_to',
                'type'              => 'select',
                'permissions'       => 'milestone_assigned_to_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none'  => true,
                'options_cb'        => 'upstream_admin_get_all_project_users',
            );


            // start row
            $fields[20] = array(
                'name'              => __( "Start Date", 'upstream' ),
                'id'                => 'start_date',
                'type'              => 'text_date_timestamp',
                'date_format'       => 'Y-m-d',
                'permissions'       => 'milestone_start_date_field',
                'before'            => 'upstream_add_field_attributes',
                'attributes'        => array(
                    //'data-validation'     => 'required',
                )
            );
            $fields[21] = array(
                'name'              => __( "End Date", 'upstream' ),
                'id'                => 'end_date',
                'type'              => 'text_date_timestamp',
                'date_format'       => 'Y-m-d',
                'permissions'       => 'milestone_end_date_field',
                'before'            => 'upstream_add_field_attributes',
                'attributes'        => array(
                    //'data-validation'     => 'required',
                )
            );

            // start row
            $fields[30] = array(
                'name'              => __( "Notes", 'upstream' ),
                'id'                => 'notes',
                'type'              => 'wysiwyg',
                'permissions'       => 'milestone_notes_field',
                'before'            => 'upstream_add_field_attributes',
                'options'           => array(
                    'media_buttons' => true,
                    'textarea_rows' => 5
                ),
                'escape_cb'         => 'applyOEmbedFiltersToWysiwygEditorContent'
            );

            if ($allowComments) {
                $fields[40] = array(
                    'name' => '&nbsp;',
                    'id'   => 'comments',
                    'type' => 'comments',
                    'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>'
                );
            }

            // set up the group grid plugin
            $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid( $group_field_id );

            // define nuber of rows
            $rows = apply_filters( 'upstream_milestone_metabox_rows', 4 );

            // filter the fields & sort numerically
            $fields = apply_filters( 'upstream_milestone_metabox_fields', $fields );
            ksort( $fields );

            // loop through ordered fields and add them to the group
            if( $fields ) {
                foreach ($fields as $key => $value) {
                    $fields[$key] = $metabox->add_group_field( $group_field_id, $value );
                }
            }

            // loop through number of rows
            for ($i=0; $i < $rows; $i++) {

                // add each row
                $row[$i] = $cmb2GroupGrid->addRow();

                // this is our hidden row that must remain as is
                if( $i == 0 ) {

                    $row[0]->addColumns( array( $fields[0], $fields[1], $fields[2] ) );

                } else {

                    // this allows up to 4 columns in each row
                    $array = array();
                    if( isset( $fields[$i * 10] ) ) {
                        $array[] = $fields[$i * 10];
                    }
                    if( isset( $fields[$i * 10 + 1] ) ) {
                        $array[] = $fields[$i * 10 + 1];
                    }
                    if( isset( $fields[$i * 10 + 2] ) ) {
                        $array[] = $fields[$i * 10 + 2];
                    }
                    if( isset( $fields[$i * 10 + 3] ) ) {
                        $array[] = $fields[$i * 10 + 3];
                    }

                    // add the fields as columns
                    // probably don't need this to be filterable but will leave it for now
                    $row[$i]->addColumns(
                        apply_filters( "upstream_milestone_row_{$i}_columns", $array )
                    );
                }
            }
        }

        if ($userHasAdminPermissions) {
            $metabox->add_field(array(
                'id'          => $this->prefix .'disable_milestones',
                'type'        => 'checkbox',
                'description' => __('Disable Milestones for this project', 'upstream')
            ));
        }
    }

    /**
     * Return the Assigned To filter HTML.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getAssignedToFilterHtml()
    {
        $upstreamUsersList = upstream_admin_get_all_project_users();
        $usersOptionsHtml = '<option>- ' . __('Show Everyone', 'upstream') . ' -</option>';
        foreach ($upstreamUsersList as $userId => $userName) {
            $usersOptionsHtml .= sprintf('<option value="%s">%s</option>', $userId, $userName);
        }

        $html = sprintf('
            <div class="col-md-4">
                <div>
                    <label>%s</label>
                    <select class="cmb-type-select upstream-filter upstream-filter-assigned_to" data-disabled="false" data-owner="true" data-no-items-found-message="%s" data-column="assigned_to">
                        %s
                    </select>
                </div>
            </div>',
            __('Assigned To', 'upstream'),
            __('No items found.', 'upstream'),
            $usersOptionsHtml
        );

        return $html;
    }

    /**
     * Return the Status filter HTML.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getStatusFilterHtml()
    {
        $upstreamStatusList = upstream_admin_get_task_statuses();
        $statusOptionsHtml = '<option>- ' . __('Show All', 'upstream') . ' -</option>';
        foreach ($upstreamStatusList as $statusId => $statusTitle) {
            $statusOptionsHtml .= sprintf('<option value="%s">%s</option>', $statusId, $statusTitle);
        }

        $html = sprintf('
            <div class="col-md-4">
                <div>
                    <label>%s</label>
                    <select class="cmb-type-select upstream-filter upstream-filter-status" data-disabled="false" data-owner="true" data-no-items-found-message="%s" data-column="status">
                        %s
                    </select>
                </div>
            </div>',
            __('Status', 'upstream'),
            __('No items found.', 'upstream'),
            $statusOptionsHtml
        );

        return $html;
    }

    /**
     * Return the Severity filter HTML.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getSeverityFilterHtml()
    {
        $upstreamSeveritiesList = upstream_admin_get_bug_severities();
        $statusOptionsHtml = '<option>- ' . __('Show All', 'upstream') . ' -</option>';
        foreach ($upstreamSeveritiesList as $severityId => $severityTitle) {
            $statusOptionsHtml .= sprintf('<option value="%s">%s</option>', $severityId, $severityTitle);
        }

        $html = sprintf('
            <div class="col-md-4">
                <div>
                    <label>%s</label>
                    <select class="cmb-type-select upstream-filter upstream-filter-severity" data-disabled="false" data-owner="true" data-column="severity" data-no-items-found-message="%s">
                        %s
                    </select>
                </div>
            </div>',
            __('Severity', 'upstream'),
            __('No items found.', 'upstream'),
            $statusOptionsHtml
        );

        return $html;
    }

    /**
     * Return the HTML that opens the Filters wrapper.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getFiltersHeaderHtml()
    {
        $html = '<div class="row upstream-filters-wrapper">';

        return $html;
    }

    /**
     * Return the HTML that closes the Filters wrapper.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getFiltersFooterHtml()
    {
        $html = '</div>';

        return $html;
    }

    /**
     * Return the Milestone filter HTML.
     *
     * @since   1.0.0
     * @access  private
     *
     * @return  string
     */
    private function getMilestoneFilterHtml()
    {
        $upstreamMilestonesList = upstream_admin_get_options_milestones();
        $milestonesOptionsHtml = '<option>- ' . __('Show All', 'upstream') . ' -</option>';
        foreach ($upstreamMilestonesList as $milestoneId => $milestoneTitle) {
            $milestonesOptionsHtml .= sprintf('<option value="%s">%s</option>', $milestoneId, $milestoneTitle);
        }

        $html = sprintf('
            <div class="col-md-4">
                <div>
                    <label>%s</label>
                    <select class="cmb-type-select upstream-filter upstream-filter-milestone" data-disabled="false" data-owner="true" data-no-items-found-message="%s" data-column="milestone">
                        %s
                    </select>
                </div>
            </div>',
            __('Milestone', 'upstream'),
            __('No items found.', 'upstream'),
            $milestonesOptionsHtml
        );

        return $html;
    }


/* ======================================================================================
                                        TASKS
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function tasks() {
        $areTasksDisabled = upstream_are_tasks_disabled();
        $userHasAdminPermissions = upstream_admin_permissions('disable_project_tasks');

        if (upstream_disable_tasks() || ($areTasksDisabled && !$userHasAdminPermissions)) {
            return;
        }

        $label          = upstream_task_label();
        $label_plural   = upstream_task_label_plural();

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'tasks',
            'title'         => '<span class="dashicons dashicons-admin-tools"></span> ' . esc_html( $label_plural ),
            'object_types'  => array( $this->type ),
        ) );

        //Create a default grid
        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        /*
         * Outputs some hidden data for dynamic use.
         */
        $metabox->add_field( array(
            'id'                => $this->prefix . 'hidden',
            'type'              => 'title',
            'description'       => '',
            'after'             => 'upstream_admin_output_task_hidden_data',
            'attributes'        => array(
                'class'         => 'hidden',
                'data-empty'    => upstream_empty_group( 'tasks' ),
                'data-publish'  => upstream_admin_permissions( 'publish_project_tasks' ),
            )
        ) );

        $group_field_id = $metabox->add_field( array(
            'id'                => $this->prefix . 'tasks',
            'type'              => 'group',
            'description'       => '',
            'permissions'       => 'delete_project_tasks', // also set on individual row level
            'options'           => array(
                'group_title'   => esc_html( $label ) . " {#}",
                'add_button'    => sprintf( __( "Add %s", 'upstream' ), esc_html( $label ) ),
                'remove_button' => sprintf( __( "Delete %s", 'upstream' ), esc_html( $label ) ),
                'sortable'      => upstream_admin_permissions( 'sort_project_tasks' ), // beta
            ),
            'after_group'       =>
                $this->getFiltersHeaderHtml() .
                $this->getAssignedToFilterHtml() .
                $this->getMilestoneFilterHtml() .
                $this->getStatusFilterHtml() .
                $this->getFiltersFooterHtml()
        ) );

        if (!$areTasksDisabled) {
            $fields = array();

            $fields[0] = array(
                'id'                => 'id',
                'type'              => 'text',
                'before'            => 'upstream_add_field_attributes',
                'permissions'       => '',
                'attributes'        => array(
                    'class' => 'hidden',
                )
            );

            $allowComments = upstreamAreCommentsEnabledOnTasks();
            if ($allowComments) {
                $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __('Data', 'upstream') . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
            }

            $fields[1] = array(
                'id'                => 'created_by',
                'type'              => 'text',
                'attributes'        => array(
                    'class' => 'hidden',
                )
            );
            $fields[2] = array(
                'id'                => 'created_time',
                'type'              => 'text',
                'attributes'        => array(
                    'class' => 'hidden',
                )
            );

            // start row
            $fields[10] = array(
                'name'              => __( 'Title', 'upstream' ),
                'id'                => 'title',
                'type'              => 'text',
                'permissions'       => 'task_title_field',
                'before'            => 'upstream_add_field_attributes',
                'attributes'        => array(
                    'class'             => 'task-title',
                    //'data-validation'     => 'required',
                )
            );

            $fields[11] = array(
                'name'              => __( "Assigned To", 'upstream' ),
                'id'                => 'assigned_to',
                'type'              => 'select',
                'permissions'       => 'task_assigned_to_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none'  => true,
                'options_cb'        => 'upstream_admin_get_all_project_users',
            );

            // start row
            $fields[20] = array(
                'name'              => __( "Status", 'upstream' ),
                'id'                => 'status',
                'type'              => 'select',
                'permissions'       => 'task_status_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none' => true,  // ** IMPORTANT - do not enforce a value in this field.
                // An row with no value here is considered to be a deleted row.
                'options_cb'        => 'upstream_admin_get_task_statuses',
                'attributes'        => array(
                    'class' => 'task-status',
                )
            );

            $fields[21] = array(
                'name'              => __( "Progress", 'upstream' ),
                'id'                => 'progress',
                'type'              => 'select',
                'permissions'       => 'task_progress_field',
                'before'            => 'upstream_add_field_attributes',
                'options_cb'        => 'upstream_get_percentages_for_dropdown',
                'attributes'        => array(
                    'class' => 'task-progress',
                )
            );

            // start row
            $fields[30] = array(
                'name'              => __( "Start Date", 'upstream' ),
                'id'                => 'start_date',
                'type'              => 'text_date_timestamp',
                'date_format'       => 'Y-m-d',
                'permissions'       => 'task_start_date_field',
                'before'            => 'upstream_add_field_attributes'
            );
            $fields[31] = array(
                'name'              => __( "End Date", 'upstream' ),
                'id'                => 'end_date',
                'type'              => 'text_date_timestamp',
                'date_format'       => 'Y-m-d',
                'permissions'       => 'task_end_date_field',
                'before'            => 'upstream_add_field_attributes'
            );

            $fields[40] = array(
                'name'              => __( "Notes", 'upstream' ),
                'id'                => 'notes',
                'type'              => 'wysiwyg',
                'permissions'       => 'task_notes_field',
                'before'            => 'upstream_add_field_attributes',
                'options'           => array(
                    'media_buttons' => true,
                    'textarea_rows' => 5
                ),
                'escape_cb'         => 'applyOEmbedFiltersToWysiwygEditorContent'
            );

            if (!upstream_are_milestones_disabled() && !upstream_disable_milestones()) {
                $fields[41] = array(
                    'name'              => '<span class="dashicons dashicons-flag"></span> ' . esc_html( upstream_milestone_label() ),
                    'id'                => 'milestone',
                    'desc'              =>
                        __( 'Selecting a milestone will count this task\'s progress toward that milestone as well as overall project progress.', 'upstream' ),
                    'type'              => 'select',
                    'permissions'       => 'task_milestone_field',
                    'before'            => 'upstream_add_field_attributes',
                    'show_option_none'  => true,
                    'options_cb'        => 'upstream_admin_get_project_milestones',
                    'attributes'        => array(
                        'class' => 'task-milestone',
                    )
                );
            } else {
                $fields[41] = array(
                    'id'          => "milestone",
                    'type'        => "text",
                    'permissions' => 'task_milestone_field',
                    'attributes'  => array(
                        'class' => "hidden"
                    )
                );
            }

            if ($allowComments) {
                $fields[50] = array(
                    'name' => '&nbsp;',
                    'id'   => 'comments',
                    'type' => 'comments',
                    'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>'
                );
            }

            // set up the group grid plugin
            $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid( $group_field_id );

            // define nuber of rows
            $rows = apply_filters( 'upstream_task_metabox_rows', 10 );

            // filter the fields & sort numerically
            $fields = apply_filters( 'upstream_task_metabox_fields', $fields );
            ksort( $fields );

            // loop through ordered fields and add them to the group
            if( $fields ) {
                foreach ($fields as $key => $value) {
                    $fields[$key] = $metabox->add_group_field( $group_field_id, $value );
                }
            }

            // loop through number of rows
            for ($i=0; $i < 7; $i++) {

                // add each row
                $row[$i] = $cmb2GroupGrid->addRow();

                // this is our hidden row that must remain as is
                if( $i == 0 ) {

                    $row[0]->addColumns( array( $fields[0], $fields[1], $fields[2] ) );

                } else {

                    // this allows up to 4 columns in each row
                    $array = array();
                    if( isset( $fields[$i * 10] ) ) {
                        $array[] = $fields[$i * 10];
                    }
                    if( isset( $fields[$i * 10 + 1] ) ) {
                        $array[] = $fields[$i * 10 + 1];
                    }
                    if( isset( $fields[$i * 10 + 2] ) ) {
                        $array[] = $fields[$i * 10 + 2];
                    }
                    if( isset( $fields[$i * 10 + 3] ) ) {
                        $array[] = $fields[$i * 10 + 3];
                    }

                    if (empty($array)) {
                        continue;
                    }
                    // add the fields as columns
                    $row[$i]->addColumns(
                        apply_filters( "upstream_task_row_{$i}_columns", $array )
                    );
                }
            }
        }

        if ($userHasAdminPermissions) {
            $metabox->add_field(array(
                'id'          => $this->prefix .'disable_tasks',
                'type'        => 'checkbox',
                'description' => __('Disable Tasks for this project', 'upstream')
            ));
        }
    }




    private static $commentsFieldsNonce = false;

    private static $itemsCommentsSectionCache = array();

    public static function renderCommentsField($field, $escapedValue, $object_id, $objectType, $fieldType)
    {
        if (!self::$commentsFieldsNonce) {
            echo '<input type="hidden" id="project_all_items_comments_nonce" value="' . wp_create_nonce('project.get_all_items_comments') . '">';
            self::$commentsFieldsNonce = true;
        }

        $field_id = $field->args['id'];

        if (!isset(self::$itemsCommentsSectionCache[$field_id])) {
            $editorIdentifier = $field_id .'_editor';

            preg_match('/^_upstream_project_([a-z]+)_([0-9]+)_comments/i', $field_id, $matches);

            echo '<div class="c-comments" data-type="' . rtrim($matches[1], "s") . '"></div>';

            printf(
                '<input type="hidden" id="%s" value="%s">',
                $field_id . '_add_comment_nonce',
                wp_create_nonce('upstream:project.' . $matches[1] . '.add_comment')
            );

            wp_editor("", $editorIdentifier, array(
                'media_buttons' => true,
                'textarea_rows' => 5,
                'textarea_name' => $editorIdentifier
            ));

            self::$itemsCommentsSectionCache[$field_id] = 1;
        }
    }





/* ======================================================================================
                                        BUGS
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function bugs()  {
        $areBugsDisabled = upstream_are_bugs_disabled();
        $userHasAdminPermissions = upstream_admin_permissions('disable_project_bugs');

        if (upstream_disable_bugs() || ($areBugsDisabled && !$userHasAdminPermissions)) {
            return;
        }

        $label          = upstream_bug_label();
        $label_plural   = upstream_bug_label_plural();

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'bugs',
            'title'         => '<span class="dashicons dashicons-warning"></span> ' . esc_html( $label_plural ),
            'object_types'  => array( $this->type ),
            'attributes'  => array( 'data-test' => 'test' ),
        ) );

        //Create a default grid
        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        /*
         * Outputs some hidden data for dynamic use.
         */
        $metabox->add_field( array(
            'id'            => $this->prefix . 'hidden',
            'type'          => 'title',
            'description'   => '',
            'after'         => 'upstream_admin_output_bug_hidden_data',
            'attributes'    => array(
                'class'         => 'hidden',
                'data-empty'    => upstream_empty_group( 'bugs' ),
                'data-publish'  => upstream_admin_permissions( 'publish_project_bugs' ),
            )
        ) );

        $group_field_id = $metabox->add_field( array(
            'id'                => $this->prefix . 'bugs',
            'type'              => 'group',
            'description'       => '',
            'permissions'       => 'delete_project_bugs', // also set on individual row level
            'options'           => array(
                'group_title'   => esc_html( $label ) . " {#}",
                'add_button'    => sprintf( __( "Add %s", 'upstream' ), esc_html( $label ) ),
                'remove_button' => sprintf( __( "Delete %s", 'upstream' ), esc_html( $label ) ),
                'sortable'      => upstream_admin_permissions( 'sort_project_bugs' ),
            ),
            'after_group'       =>
                $this->getFiltersHeaderHtml() .
                $this->getAssignedToFilterHtml() .
                $this->getStatusFilterHtml() .
                $this->getSeverityFilterHtml() .
                $this->getFiltersFooterHtml()
        ) );

        if (!$areBugsDisabled) {
            $fields = array();

            $fields[0] = array(
                'id'            => 'id',
                'type'          => 'text',
                'before'        => 'upstream_add_field_attributes',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );

            $allowComments = upstreamAreCommentsEnabledOnBugs();
            if ($allowComments) {
                $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __('Data', 'upstream') . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
            }

            $fields[1] = array(
                'id'            => 'created_by',
                'type'          => 'text',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );
            $fields[2] = array(
                'id'            => 'created_time',
                'type'          => 'text',
                'attributes'    => array(
                    'class' => 'hidden',
                )
            );

            // start row
            $fields[10] = array(
                'name'              => __( 'Title', 'upstream' ),
                'id'                => 'title',
                'type'              => 'text',
                'permissions'       => 'bug_title_field',
                'before'            => 'upstream_add_field_attributes',
                'attributes'        => array(
                    'class'             => 'bug-title',
                )
            );

            $fields[11] = array(
                'name'              => __( "Assigned To", 'upstream' ),
                'id'                => 'assigned_to',
                'type'              => 'select',
                'permissions'       => 'bug_assigned_to_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none'  => true,
                'options_cb'        => 'upstream_admin_get_all_project_users',
            );

            // start row
            $fields[20] = array(
                'name'              => __( "Description", 'upstream' ),
                'id'                => 'description',
                'type'              => 'wysiwyg',
                'permissions'       => 'bug_description_field',
                'before'            => 'upstream_add_field_attributes',
                'options'           => array(
                    'media_buttons' => true,
                    'textarea_rows' => 5
                ),
                'escape_cb'         => 'applyOEmbedFiltersToWysiwygEditorContent'
            );

            // start row
            $fields[30] = array(
                'name'              => __( "Status", 'upstream' ),
                'id'                => 'status',
                'type'              => 'select',
                'permissions'       => 'bug_status_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none' => true, // ** IMPORTANT - do not enforce a value in this field.
                // An row with no value here is considered to be a deleted row.
                'options_cb'        => 'upstream_admin_get_bug_statuses',
                'attributes'        => array(
                    'class'             => 'bug-status',
                )
            );
            $fields[31] = array(
                'name'              => __( "Severity", 'upstream' ),
                'id'                => 'severity',
                'type'              => 'select',
                'permissions'       => 'bug_severity_field',
                'before'            => 'upstream_add_field_attributes',
                'show_option_none'  => true,
                'options_cb'        => 'upstream_admin_get_bug_severities',
                'attributes'        => array(
                    'class' => 'bug-severity',
                )
            );

            // start row
            $fields[40] = array(
                'name'              => __( 'Attachments', 'upstream' ),
                'desc'              => '',
                'id'                => 'file',
                'type'              => 'file',
                'permissions'       => 'bug_files_field',
                'before'            => 'upstream_add_field_attributes',
                'options' => array(
                    'url' => false, // Hide the text input for the url
                ),
            );

            $fields[41] = array(
                'name'              => __( "Due Date", 'upstream' ),
                'id'                => 'due_date',
                'type'              => 'text_date_timestamp',
                'date_format'       => 'Y-m-d',
                'permissions'       => 'bug_due_date_field',
                'before'            => 'upstream_add_field_attributes'
            );

            if ($allowComments) {
                $fields[50] = array(
                    'name' => '&nbsp;',
                    'id'   => 'comments',
                    'type' => 'comments',
                    'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>'
                );
            }

            // set up the group grid plugin
            $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid( $group_field_id );

            // define nuber of rows
            $rows = apply_filters( 'upstream_bug_metabox_rows', 5 );

            // filter the fields & sort numerically
            $fields = apply_filters( 'upstream_bug_metabox_fields', $fields );
            ksort( $fields );

            // loop through ordered fields and add them to the group
            if( $fields ) {
                foreach ($fields as $key => $value) {
                    $fields[$key] = $metabox->add_group_field( $group_field_id, $value );
                }
            }

            // loop through number of rows
            for ($i=0; $i < $rows; $i++) {

                // add each row
                $row[$i] = $cmb2GroupGrid->addRow();

                // this is our hidden row that must remain as is
                if( $i == 0 ) {

                    $row[0]->addColumns( array( $fields[0], $fields[1], $fields[2] ) );

                } else {

                    // this allows up to 4 columns in each row
                    $array = array();
                    if( isset( $fields[$i * 10] ) ) {
                        $array[] = $fields[$i * 10];
                    }
                    if( isset( $fields[$i * 10 + 1] ) ) {
                        $array[] = $fields[$i * 10 + 1];
                    }
                    if( isset( $fields[$i * 10 + 2] ) ) {
                        $array[] = $fields[$i * 10 + 2];
                    }
                    if( isset( $fields[$i * 10 + 3] ) ) {
                        $array[] = $fields[$i * 10 + 3];
                    }

                    // add the fields as columns
                    $row[$i]->addColumns(
                        apply_filters( "upstream_bug_row_{$i}_columns", $array )
                    );
                }
            }
        }

        if ($userHasAdminPermissions) {
            $metabox->add_field(array(
                'id'          => $this->prefix .'disable_bugs',
                'type'        => 'checkbox',
                'description' => __('Disable Bugs for this project', 'upstream')
            ));
        }
    }



/* ======================================================================================
                                        SIDEBAR TOP
   ====================================================================================== */

    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function details()
    {
        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'details',
            'title'         => '<span class="dashicons dashicons-admin-generic"></span> ' . sprintf( __( "%s Details", 'upstream' ), $this->project_label ),
            'object_types'  => array( $this->type ),
            'context'       => 'side',
            'priority'      => 'high',
        ) );

        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid( $metabox );

        $fields = array();

        $fields[0] = array(
            'name'              => __( 'Status', 'upstream' ),
            'desc'              => '',
            'id'                => $this->prefix . 'status',
            'type'              => 'select',
            'show_option_none'  => true,
            'permissions'       => 'project_status_field',
            'before'            => 'upstream_add_field_attributes',
            'options_cb'        => 'upstream_admin_get_project_statuses',
            'save_field'        => upstream_admin_permissions('project_status_field')
        );

        $fields[1] = array(
            'name'              => __( 'Owner', 'upstream' ),
            'desc'              => '',
            'id'                => $this->prefix . 'owner',
            'type'              => 'select',
            'show_option_none'  => true,
            'permissions'       => 'project_owner_field',
            'before'            => 'upstream_add_field_attributes',
            'options_cb'        => 'upstream_admin_get_all_project_users',
            'save_field'        => upstream_admin_permissions('project_owner_field')
        );

        if (!is_clients_disabled()) {
            $client_label = upstream_client_label();

            $fields[2] = array(
                'name'              => $client_label,
                'desc'              => '',
                'id'                => $this->prefix . 'client',
                'type'              => 'select',
                'show_option_none'  => true,
                'permissions'       => 'project_client_field',
                'before'            => 'upstream_add_field_attributes',
                'options_cb'        => 'upstream_admin_get_all_clients',
                'save_field'        => upstream_admin_permissions('project_client_field')
            );

            $fields[3] = array(
                'name'              => sprintf( __( '%s Users', 'upstream' ), $client_label ),
                'id'                => $this->prefix . 'client_users',
                'type'              => 'multicheck',
                'select_all_button' => false,
                'permissions'       => 'project_users_field',
                'before'            => 'upstream_add_field_attributes',
                'options_cb'        => 'upstream_admin_get_all_clients_users',
                'save_field'        => upstream_admin_permissions('project_users_field')
            );
        }

        $fields[10] = array(
            'name'              => __( 'Start Date', 'upstream' ),
            'desc'              => '',
            'id'                => $this->prefix . 'start',
            'type'              => 'text_date_timestamp',
            'date_format'       => 'Y-m-d',
            'permissions'       => 'project_start_date_field',
            'before'            => 'upstream_add_field_attributes',
            'show_on_cb'        => 'upstream_show_project_start_date_field',
            'save_field'        => upstream_admin_permissions('upstream_start_date_field')
        );
        $fields[11] = array(
            'name'              => __( 'End Date', 'upstream' ),
            'desc'              => '',
            'id'                => $this->prefix . 'end',
            'type'              => 'text_date_timestamp',
            'date_format'       => 'Y-m-d',
            'permissions'       => 'project_end_date_field',
            'before'            => 'upstream_add_field_attributes',
            'show_on_cb'        => 'upstream_show_project_end_date_field',
            'save_field'        => upstream_admin_permissions('project_end_date_field')
        );

        $fields[12] = array(
            'name'              => __( "Description", 'upstream' ),
            'desc'              => '',
            'id'                => $this->prefix . 'description',
            'type'              => 'wysiwyg',
            'permissions'       => 'project_description',
            'before'            => 'upstream_add_field_attributes',
            'options'           => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
                'teeny'         => true
            ),
            'save_field'        => upstream_admin_permissions('project_description')
        );

        // filter the fields & sort numerically
        $fields = apply_filters( 'upstream_details_metabox_fields', $fields );
        ksort( $fields );

        // loop through ordered fields and add them to the group
        if( $fields ) {
            foreach ($fields as $key => $value) {
                $fields[$key] = $metabox->add_field( $value );
            }
        }

        $row = $cmb2Grid->addRow();
        $row->addColumns(array( $fields[10], $fields[11] ));


    }



/* ======================================================================================
                                        Files
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function files() {
        $areFilesDisabled = upstream_are_files_disabled();
        $userHasAdminPermissions = upstream_admin_permissions('disable_project_files');

        if (upstream_disable_files() || ($areFilesDisabled && !$userHasAdminPermissions)) {
            return;
        }

        $label          = upstream_file_label();
        $label_plural   = upstream_file_label_plural();

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'files',
            'title'         => '<span class="dashicons dashicons-paperclip"></span> ' . esc_html( $label_plural ),
            'object_types'  => array( $this->type ),
        ) );

        //Create a default grid
        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        /*
         * Outputs some hidden data for dynamic use.
         */
        $metabox->add_field( array(
            'id'            => $this->prefix . 'hidden',
            'type'          => 'title',
            'description'   => '',
            //'after'       => 'upstream_admin_output_files_hidden_data',
            'attributes'    => array(
                'class'         => 'hidden',
                'data-empty'    => upstream_empty_group( 'files' ),
                'data-publish'  => upstream_admin_permissions( 'publish_project_files' ),

            ),
        ) );

        $group_field_id = $metabox->add_field( array(
            'id'                => $this->prefix . 'files',
            'type'              => 'group',
            'description'       => '',
            'permissions'       => 'delete_project_files', // also set on individual row level
            'options'           => array(
                'group_title'   => esc_html( $label ) . " {#}",
                'add_button'    => sprintf( __( "Add %s", 'upstream' ), esc_html( $label ) ),
                'remove_button' => sprintf( __( "Delete %s", 'upstream' ), esc_html( $label ) ),
                'sortable'      => upstream_admin_permissions( 'sort_project_files' ),
            ),
        ) );

        if (!$areFilesDisabled) {
            $fields = array();

            // start row
            $fields[0] = array(
                'id'            => 'id',
                'type'          => 'text',
                'before'        => 'upstream_add_field_attributes',
                'attributes'    => array( 'class' => 'hidden' )
            );

            $allowComments = upstreamAreCommentsEnabledOnFiles();
            if ($allowComments) {
                $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __('Data', 'upstream') . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
            }

            $fields[1] = array(
                'id'            => 'created_by',
                'type'          => 'text',
                'attributes'    => array( 'class' => 'hidden' )
            );
            $fields[2] = array(
                'id'            => 'created_time',
                'type'          => 'text',
                'attributes'    => array( 'class' => 'hidden' )
            );

            // start row
            $fields[10] = array(
                'name'              => __( 'Title', 'upstream' ),
                'id'                => 'title',
                'type'              => 'text',
                'permissions'       => 'file_title_field',
                'before'            => 'upstream_add_field_attributes',
                'attributes'        => array(
                    'class'             => 'file-title',
                )
            );
            $fields[11] = array(
                'name'              => esc_html( $label ),
                'desc'              => '',
                'id'                => 'file',
                'type'              => 'file',
                'permissions'       => 'file_files_field',
                'before'            => 'upstream_add_field_attributes',
                'options' => array(
                    'url' => false, // Hide the text input for the url
                ),
            );

            // start row
            $fields[20] = array(
                'name'              => __( "Description", 'upstream' ),
                'id'                => 'description',
                'type'              => 'wysiwyg',
                'permissions'       => 'file_description_field',
                'before'            => 'upstream_add_field_attributes',
                'options'           => array(
                    'media_buttons' => true,
                    'textarea_rows' => 3
                )
            );

            if ($allowComments) {
                $fields[30] = array(
                    'name' => '&nbsp;',
                    'id'   => 'comments',
                    'type' => 'comments',
                    'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>'
                );
            }

            // set up the group grid plugin
            $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid( $group_field_id );

            // define nuber of rows
            $rows = apply_filters( 'upstream_file_metabox_rows', 3 );

            // filter the fields & sort numerically
            $fields = apply_filters( 'upstream_file_metabox_fields', $fields );
            ksort( $fields );

            // loop through ordered fields and add them to the group
            if( $fields ) {
                foreach ($fields as $key => $value) {
                    $fields[$key] = $metabox->add_group_field( $group_field_id, $value );
                }
            }

            // loop through number of rows
            for ($i=0; $i < $rows; $i++) {

                // add each row
                $row[$i] = $cmb2GroupGrid->addRow();

                // this is our hidden row that must remain as is
                if( $i == 0 ) {

                    $row[0]->addColumns( array( $fields[0], $fields[1], $fields[2] ) );

                } else {

                    // this allows up to 4 columns in each row
                    $array = array();
                    if( isset( $fields[$i * 10] ) ) {
                        $array[] = $fields[$i * 10];
                    }
                    if( isset( $fields[$i * 10 + 1] ) ) {
                        $array[] = $fields[$i * 10 + 1];
                    }
                    if( isset( $fields[$i * 10 + 2] ) ) {
                        $array[] = $fields[$i * 10 + 2];
                    }
                    if( isset( $fields[$i * 10 + 3] ) ) {
                        $array[] = $fields[$i * 10 + 3];
                    }

                    // add the fields as columns
                    $row[$i]->addColumns(
                        apply_filters( "upstream_file_row_{$i}_columns", $array )
                    );
                }
            }
        }

        if ($userHasAdminPermissions) {
            $metabox->add_field(array(
                'id'          => $this->prefix .'disable_files',
                'type'        => 'checkbox',
                'description' => __('Disable Files for this project', 'upstream')
            ));
        }
    }


/* ======================================================================================
                                        SIDEBAR LOW
   ====================================================================================== */
    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function sidebar_low() {

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'activity',
            'title'         => '<span class="dashicons dashicons-update"></span> ' . __( 'Activity', 'upstream' ),
            'object_types'  => array( $this->type ),
            'context'      => 'side', //  'normal', 'advanced', or 'side'
            'priority'     => 'low',  //  'high', 'core', 'default' or 'low'
        ) );

        //Create a default grid
        $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

        /*
         * Outputs some hidden data for dynamic use.
         */
        $metabox->add_field( array(
            'name'              => '',
            'desc'              => '',
            'id'                => $this->prefix . 'activity',
            'type'              => 'title',
            'before'            => 'upstream_activity_buttons',
            'after'             => 'upstream_output_activity',
        ) );



    }

    /**
     * Add the metaboxes
     * @since  0.1.0
     */
    public function comments() {
        $areCommentsDisabled = upstream_are_comments_disabled();
        $userHasAdminPermissions = upstream_admin_permissions('disable_project_comments');

        if (!self::$allowProjectComments || ($areCommentsDisabled && !$userHasAdminPermissions)) {
            return;
        }

        $metabox = new_cmb2_box( array(
            'id'            => $this->prefix . 'discussions',
            'title'         => '<span class="dashicons dashicons-format-chat"></span> ' . __('Discussion'),
            'object_types'  => array( $this->type ),
            'priority'      => 'low',
        ) );

        if (!$areCommentsDisabled) {
            $metabox->add_field( array(
                'name'              => __('Add new Comment'),
                'desc'              => '',
                'id'                => $this->prefix . 'new_message',
                'type'              => 'wysiwyg',
                'permissions'       => 'publish_project_discussion',
                'before'            => 'upstream_add_field_attributes',
                'after_field'       => '<p class="u-text-right"><button class="button button-primary" type="button" data-action="comments.add_comment" data-nonce="' . wp_create_nonce('upstream:project.add_comment') . '">' . __('Add Comment') . '</button></p></div></div>',
                'after_row'         => 'upstreamRenderCommentsBox',
                'options'           => array(
                    'media_buttons' => true,
                    'textarea_rows' => 5
                ),
                'escape_cb'         => 'applyOEmbedFiltersToWysiwygEditorContent',
                'before_field'      => '<div class="row"><div class="hidden-xs hidden-sm col-md-12 col-lg-12"><label for="' . $this->prefix . 'new_message' . '">' . __('Add new Comment') . '</label>'
            ) );
        }

        if ($userHasAdminPermissions) {
            $metabox->add_field(array(
                'id'          => $this->prefix .'disable_comments',
                'type'        => 'checkbox',
                'description' => __('Disable Discussion for this project', 'upstream')
            ));
        }
    }

    /**
     * This method ensures WordPress generate and show custom slugs based on project's title automaticaly below the field.
     * Since it will do so only for public posts and Projects-post-type are not public (they would appear on sites searches),
     * we rapidly make it public and switch back to non-public status. This temporary change will not cause search/visibility side effects.
     *
     * Called by the "edit_form_before_permalink" action right before the "edit_form_after_title" hook.
     *
     * @since   1.12.3
     * @static
     *
     * @global  $post_type_object
     */
    public static function makeProjectTemporarilyPublic()
    {
        global $post_type_object;

        if ($post_type_object->name === "project") {
            $post_type_object->public = true;
        }
    }

    /**
     * This method is called right after the makeProjectTemporarilyPublic() and ensures the project is non-public once again. side effects.
     *
     * Called by the "edit_form_after_title" action right after the "edit_form_before_permalink" hook.
     *
     * @since   1.12.3
     * @static
     *
     * @see     self::makeProjectTemporarilyPublic()
     *
     * @global  $post_type_object
     */
    public static function makeProjectPrivateOnceAgain()
    {
        global $post_type_object;

        if ($post_type_object->name === "project") {
            $post_type_object->public = false;
        }
    }

    /**
     * AJAX endpoint that retrieves all comments from all items on the give project.
     *
     * @since   1.13.0
     * @static
     */
    public static function fetchAllItemsComments()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(
                'milestones' => array(),
                'tasks'      => array(),
                'bugs'       => array(),
                'files'      => array()
            ),
            'error'   => null
        );

        try {
            // Check if the request payload is potentially invalid.
            if (
                !defined('DOING_AJAX')
                || !DOING_AJAX
                || empty($_GET)
                || !isset($_GET['nonce'])
                || !isset($_GET['project_id'])
                || !wp_verify_nonce($_GET['nonce'], 'project.get_all_items_comments')
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_GET['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            $usersCache = array();
            $usersRowset = get_users(array(
                'fields' => array(
                    'ID', 'display_name'
                )
            ));
            foreach ($usersRowset as $userRow) {
                $userRow->ID *= 1;

                $usersCache[$userRow->ID] = (object)array(
                    'id'     => $userRow->ID,
                    'name'   => $userRow->display_name,
                    'avatar' => getUserAvatarURL($userRow->ID)
                );
            }
            unset($userRow, $usersRowset);

            $dateFormat = get_option('date_format');
            $timeFormat = get_option('time_format');
            $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
            $utcTimeZone = new \DateTimeZone('UTC');
            $currentTimezone = upstreamGetTimeZone();
            $currentTimestamp = time();

            $user = wp_get_current_user();
            $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
            $userCanReply = !$userHasAdminCapabilities ? user_can($user, 'publish_project_discussion') : true;
            $userCanModerate = !$userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;
            $userCanDelete = !$userHasAdminCapabilities ? $userCanModerate || user_can($user, 'delete_project_discussion') : true;

            $commentsStatuses = array('approve');
            if ($userHasAdminCapabilities || $userCanModerate) {
                $commentsStatuses[] = 'hold';
            }

            $itemsTypes = array('milestones', 'tasks', 'bugs', 'files');
            foreach ($itemsTypes as $itemType) {
                $itemTypeSingular = rtrim($itemType, 's');
                $rowset = (array)get_post_meta($project_id, '_upstream_project_' . $itemType, true);
                if (count($rowset) > 0) {
                    foreach ($rowset as $row) {
                        $comments = get_comments(array(
                            'post_id'    => $project_id,
                            'status'     => $commentsStatuses,
                            'meta_query' => array(
                                'relation' => 'AND',
                                array(
                                    'key'   => 'type',
                                    'value' => $itemTypeSingular
                                ),
                                array(
                                    'key'   => 'id',
                                    'value' => $row['id']
                                )
                            )
                        ));

                        if (count($comments) > 0) {
                            $response['data'][$itemType][$row['id']] = array();

                            foreach ($comments as $comment) {
                                $author = $usersCache[(int)$comment->user_id];

                                $date = DateTime::createFromFormat('Y-m-d H:i:s', $comment->comment_date_gmt, $utcTimeZone);

                                $commentData = json_decode(json_encode(array(
                                    'id'         => $comment->comment_ID,
                                    'parent_id'  => $comment->comment_parent,
                                    'content'    => $comment->comment_content,
                                    'state'      => $comment->comment_approved,
                                    'created_by' => $author,
                                    'created_at' => array(
                                        'localized' => "",
                                        'humanized' => sprintf(
                                            _x('%s ago', '%s = human-readable time difference', 'upstream'),
                                            human_time_diff($date->getTimestamp(), $currentTimestamp)
                                        )
                                    ),
                                    'currentUserCap' => array(
                                        'can_reply'    => $userCanReply,
                                        'can_moderate' => $userCanModerate,
                                        'can_delete'   => $userCanDelete || $author->id === $user->ID
                                    )
                                )));

                                $date->setTimezone($currentTimezone);

                                $commentData->created_at->localized = $date->format($theDateTimeFormat);

                                $commentsCache = array();
                                if ((int)$comment->comment_parent > 0) {
                                    $parent = get_comment($comment->comment_parent);
                                    $commentsCache = array(
                                        $parent->comment_ID => json_decode(json_encode(array(
                                            'created_by' => array(
                                                'name' => $parent->comment_author
                                            )
                                        )))
                                    );
                                }

                                ob_start();
                                upstream_admin_display_message_item($commentData, $commentsCache);
                                $response['data'][$itemType][$row['id']][] = ob_get_contents();
                                ob_end_clean();
                            }
                        }
                    }
                }
            }

            $response['success'] = true;
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }
}

endif;
