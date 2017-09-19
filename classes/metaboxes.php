<?php

namespace SmartLink;

class MetaBoxes {
	private $title;
	private $anchors;

	/**
	 * MetaBoxes constructor.
	 *
	 * @param $title string
	 * @param $anchors Anchors
	 */
	public function __construct( $title, $anchors ) {
		$this->title   = $title;
		$this->anchors = &$anchors;
		add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
	}

	public function add_some_meta_box() {
		$post_data = get_post();
		if ( $post_data->post_link_type == "donor" ) {
			$links  = $this->anchors->get_list( $post_data );
			$result = "<ul>{interests_list}</ul >";
			if ( $links[0] ) {
				$result = str_replace( "{interests_list}", $links[0], $result );
				add_meta_box(
					'sea_shortcodes_meta_box',
					$this->title,
					array( &$this, 'render_meta_box_content' ),
					'post',
					'normal',
					'high',
					array( 'result' => $result )
				);
			}
		}
	}

	public function render_meta_box_content( $post, $data ) {
		echo $data['args']['result'];
	}
}
