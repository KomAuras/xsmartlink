<?
namespace SmartLink;

use WP_Widget;

class xsmartlink_widget extends WP_Widget {

	public $plugin_slug;
	public $version;
	public $option_name;
	public $settings;
	public $anchors;

    public function __construct() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-anchors.php';

		$this->plugin_slug = Info::SLUG;
		$this->version     = Info::VERSION;
		$this->option_name = Info::OPTION_NAME;
		$this->settings    = get_option( $this->option_name );

        parent::__construct(
        	'xsmartlink_widget',
        	__('Acceptors links', $this->plugin_slug ),
        	array(
        		'description' => __('Show links from Acceptors', $this->plugin_slug ),
        	)
        );

		$this->anchors = new Anchors( $this->plugin_slug, $this->version, $this->option_name );
    }

    public function widget( $args, $instance ) {
        if( is_single()  ){
            $text = $this->anchors->get_post_anchor_list( get_post(), false );
            if (!empty($text)){
                $title = apply_filters( 'widget_title', $instance['title'] );
                echo $args['before_widget'];
                if ( ! empty( $title ) )
                echo $args['before_title'] . $title . $args['after_title'];
                echo $text;
                echo $args['after_widget'];
			}
        }
    }

    public function form( $instance ) {
        $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : __( 'See also', $this->plugin_slug );
        ?>
        <p>
        	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
        	<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}
