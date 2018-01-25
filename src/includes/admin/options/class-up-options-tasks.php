<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UpStream_Options_Tasks' ) ) :

/**
 * CMB2 Theme Options
 * @version 0.1.0
 */
class UpStream_Options_Tasks {

    /**
     * Array of metaboxes/fields
     * @var array
     */
    public $id = 'upstream_tasks';

    /**
     * Page title
     * @var string
     */
    protected $title = '';

    /**
     * Menu Title
     * @var string
     */
    protected $menu_title = '';

    /**
     * Menu Title
     * @var string
     */
    protected $description = '';

    /**
     * Holds an instance of the object
     *
     * @var Myprefix_Admin
     **/
    public static $instance = null;

    /**
     * Constructor
     * @since 0.1.0
     */
    public function __construct() {
        // Set our title
        $this->title = upstream_task_label_plural();
        $this->menu_title = $this->title;
        //$this->description = sprintf( __( '%s Description', 'upstream' ), upstream_task_label() );
    }
    /**
     * Returns the running object
     *
     * @return Myprefix_Admin
     **/
    public static function get_instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }



    /**
     * Add the options metabox to the array of metaboxes
     * @since  0.1.0
     */
    public function options() {

        $options = apply_filters( $this->id . '_option_fields', array(
            'id'         => $this->id, // upstream_tasks
            'title'      => $this->title,
            'menu_title' => $this->menu_title,
            'desc'       => $this->description,
            'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->id ), ),
            'show_names' => true,
            'fields'     => array(
                array(
                    'name' => __( 'Statuses', 'upstream' ),
                    'id'   => 'status_title',
                    'type' => 'title',
                    'desc' => sprintf( __( 'The statuses and colors that can be used for the status of %s.<br>These will become available in the %s Status dropdown within each %s', 'upstream' ), upstream_task_label_plural(), upstream_task_label(), upstream_task_label() )
                ),
                array(
                    'id'          => 'statuses',
                    'type'        => 'group',
                    'name'        => '',
                    'description' => '',
                    'options'     => array(
                        'group_title'   => __( 'Status {#}', 'upstream' ),
                        'add_button'    => __( 'Add Status', 'upstream' ),
                        'remove_button' => __( 'Remove Entry', 'upstream' ),
                        'sortable'      => true, // beta
                    ),
                    'fields'     => array(
                        array(
                            'name'      => __( 'Status Color', 'upstream' ),
                            'id'        => 'color',
                            'type'      => 'colorpicker',
                            'attributes' => array(
                                'data-colorpicker' => json_encode( array(
                                    // Iris Options set here as values in the 'data-colorpicker' array
                                    'palettes' => upstream_colorpicker_default_colors(),
                                    'width' => 300,
                                ) ),
                            ),
                        ),
                        array(
                            'name' => __( 'Status Name', 'upstream' ),
                            'id'   => 'name',
                            'type' => 'text',
                        ),
                        array(
                            'name' => __( 'Type of Status', 'upstream' ),
                            'id'   => 'type',
                            'type' => 'radio',
                            'default' => 'open',
                            'desc' => __("A Status Name such as 'In Progress' or 'Overdue' would be considered Open.", 'upstream') . '<br>' . __("A Status Name such as 'Complete' or 'Cancelled' would be considered Closed.", 'upstream'),
                            'options' => array (
                                'open' => __( 'Open', 'upstream' ),
                                'closed' => __( 'Closed', 'upstream' ),
                            )
                        )
                    )
                ),



            ) )
        );

        return $options;

    }



}


endif;


