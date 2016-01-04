<?php

/*-----------------------------------------------------------------------------------

	Plugin Name: Core Instagram
	Plugin URI: http://themeshash.com/wordpress-plugins/
	Description: A widget for showing Instagram photos via widget.
	Version: 1.0
	Author: Muhammad Faisal
	Author URI: http://themeshash.com/

-----------------------------------------------------------------------------------*/


// Add function to widgets_init that'll load our widget
add_action( 'widgets_init', 'th_instagram_widget_init' );

// Register widget
function th_instagram_widget_init() {
	register_widget( 'th_instagram_widget' );
}

// Widget class
class th_instagram_widget extends WP_Widget {


	#-------------------------------------------------------------------------------#
	#  Widget Setup
	#-------------------------------------------------------------------------------#
	
	function __construct() {

		// Widget settings
		$widget_ops = array(
			'classname' => 'widget-instagram',
			'description' => esc_html__('A widget for showing your latest Instagram photos.', 'themeshash')
		);

		// Widget control settings
		$control_ops = array(
			'width' => 300,
			'height' => 350,
			'id_base' => 'th_instagram_widget'
		);

		// Create the widget
		parent::__construct( 'th_instagram_widget', esc_html__('Instagram Feed', 'themeshash'), $widget_ops, $control_ops );
		
	}


	#-------------------------------------------------------------------------------#
	#  Widget Display
	#-------------------------------------------------------------------------------#
	
	public function widget( $args, $instance ) {
		extract( $args );

		// Our variables from the widget settings
		$title = apply_filters('widget_title', $instance['title'] );
		$username = $instance['username'];
		$limit = $instance['number'];
		$size = $instance['size'];
		$target = $instance['target'];


		// Before widget (defined by theme functions file)
		echo wp_kses_post( $before_widget );

		// Display the widget title if one was input
		if ( $title )
			echo wp_kses_post( $before_title . $title . $after_title );

		?>
	     
        <div class="widget-content">
            
	    	<?php do_action( 'th_before_instagram_widget' ); ?>

			<?php

			if ( $username != '' ) {

				$media_array = $this->scrape_instagram( $username, $limit );

				if ( is_wp_error( $media_array ) ) {

					echo $media_array->get_error_message();

				} else {

					// filter for images only?
					if ( $images_only = apply_filters( 'wpiw_images_only', FALSE ) )
						$media_array = array_filter( $media_array, array( $this, 'images_only' ) );

					?>
					<ul class="instagram-pics instagram-size-<?php echo $size; ?>">
					<?php foreach ( $media_array as $item ) {
						echo '<li><a href="'. esc_url( $item['link'] ) .'" target="'. esc_attr( $target ) .'"><img src="'. esc_url( $item[$size] ) .'"  alt="'. esc_attr( $item['description'] ) .'" title="'. esc_attr( $item['description'] ).'" /></a></li>';
					} ?>
					</ul>
					<?php
				}
			}

			?>

	    	<?php do_action( 'th_after_instagram_widget' ); ?>
                        
        </div>

		<?php

		// After widget (defined by theme functions file)
		echo wp_kses_post( $after_widget );
		
	}

	#-------------------------------------------------------------------------------#
	#  Widget Update
	#-------------------------------------------------------------------------------#
	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// Strip tags to remove HTML (important for text inputs)
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['username'] = strip_tags( $new_instance['username'] );
		$instance['number'] = strip_tags( $new_instance['number'] );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['target'] = strip_tags( $new_instance['target'] );

		// No need to strip tags
		return $instance;
	}

	#-------------------------------------------------------------------------------#
	#  Widget Form
	#-------------------------------------------------------------------------------#
		 
	public function form( $instance ) {

		// Set up some default widget settings
		$defaults = array(
			'title' => __('Instagram', 'themeshash'),
			'username' => '',
			'number' => '9',
			'size' => 'small',
			'target' => '_blank',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Title: Text Input -->	
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e('Title', 'themeshash') ?>:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<!-- User Name: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>"><?php esc_html_e('Username', 'themeshash') ?>:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'username' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['username'] ); ?>" />
		</p>

		<!-- Number: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e('Number of Photos', 'themeshash') ?>:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['number'] ); ?>" />
		</p>

		<!-- Size: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Photo size', 'themeshash' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" class="widefat">
				<option value="thumbnail" <?php selected( 'thumbnail', $instance['size'] ) ?>><?php _e( 'Thumbnail', 'themeshash' ); ?></option>
				<option value="small" <?php selected( 'small', $instance['size'] ) ?>><?php _e( 'Small', 'themeshash' ); ?></option>
				<option value="large" <?php selected( 'large', $instance['size'] ) ?>><?php _e( 'Large', 'themeshash' ); ?></option>
				<option value="original" <?php selected( 'original', $instance['size'] ) ?>><?php _e( 'Original', 'themeshash' ); ?></option>
			</select>
		</p>

		<!-- Target: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e( 'Open links in', 'themeshash' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'target' ); ?>" name="<?php echo $this->get_field_name( 'target' ); ?>" class="widefat">
				<option value="_self" <?php selected( '_self', $instance['target'] ) ?>><?php _e( 'Current window (_self)', 'themeshash' ); ?></option>
				<option value="_blank" <?php selected( '_blank', $instance['target'] ) ?>><?php _e( 'New window (_blank)', 'themeshash' ); ?></option>
			</select>
		</p>

		<?php

	}

	#-------------------------------------------------------------------------------#
	#  Instagram Scarpper
	#-------------------------------------------------------------------------------#

	// based on https://gist.github.com/cosmocatalano/4544576
	public function scrape_instagram( $username, $slice = 9 ) {

		$username = strtolower( $username );
		$username = str_replace( '@', '', $username );

		if ( false === ( $instagram = get_transient( 'instagram-media-5-'.sanitize_title_with_dashes( $username ) ) ) ) {

			$remote = wp_remote_get( 'http://instagram.com/'.trim( $username ) );

			if ( is_wp_error( $remote ) )
				return new WP_Error( 'site_down', __( 'Unable to communicate with Instagram.', 'wp-instagram-widget' ) );

			if ( 200 != wp_remote_retrieve_response_code( $remote ) )
				return new WP_Error( 'invalid_response', __( 'Instagram did not return a 200.', 'wp-instagram-widget' ) );

			$shards = explode( 'window._sharedData = ', $remote['body'] );
			$insta_json = explode( ';</script>', $shards[1] );
			$insta_array = json_decode( $insta_json[0], TRUE );

			if ( ! $insta_array )
				return new WP_Error( 'bad_json', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );

			if ( isset( $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'] ) ) {
				$images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
			} else {
				return new WP_Error( 'bad_json_2', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );
			}

			if ( ! is_array( $images ) )
				return new WP_Error( 'bad_array', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );

			$instagram = array();

			foreach ( $images as $image ) {

				$image['thumbnail_src'] = preg_replace( "/^https:/i", "", $image['thumbnail_src'] );
				$image['thumbnail'] = str_replace( 's640x640', 's160x160', $image['thumbnail_src'] );
				$image['small'] = str_replace( 's640x640', 's320x320', $image['thumbnail_src'] );
				$image['large'] = $image['thumbnail_src'];
				$image['display_src'] = preg_replace( "/^https:/i", "", $image['display_src'] );

				if ( $image['is_video'] == true ) {
					$type = 'video';
				} else {
					$type = 'image';
				}

				$caption = __( 'Instagram Image', 'wp-instagram-widget' );
				if ( ! empty( $image['caption'] ) ) {
					$caption = $image['caption'];
				}

				$instagram[] = array(
					'description'   => $caption,
					'link'		  	=> '//instagram.com/p/' . $image['code'],
					'time'		  	=> $image['date'],
					'comments'	  	=> $image['comments']['count'],
					'likes'		 	=> $image['likes']['count'],
					'thumbnail'	 	=> $image['thumbnail'],
					'small'			=> $image['small'],
					'large'			=> $image['large'],
					'original'		=> $image['display_src'],
					'type'		  	=> $type
				);
			}

			// do not set an empty transient - should help catch private or empty accounts
			if ( ! empty( $instagram ) ) {
				$instagram = base64_encode( serialize( $instagram ) );
				set_transient( 'instagram-media-5-'.sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS*2 ) );
			}
		}

		if ( ! empty( $instagram ) ) {

			$instagram = unserialize( base64_decode( $instagram ) );
			return array_slice( $instagram, 0, $slice );

		} else {

			return new WP_Error( 'no_images', __( 'Instagram did not return any images.', 'wp-instagram-widget' ) );

		}
	}

	public function images_only( $media_item ) {

		if ( $media_item['type'] == 'image' )
			return true;

		return false;
	}


}