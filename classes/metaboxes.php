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
		$post = get_post();
		$data = $this->anchors->get_post_anchor_list( $post );
		if ( strlen( $data ) ) {
			add_meta_box(
				'sea_shortcodes_meta_box',
				$this->title,
				array( &$this, 'render_meta_box_content' ),
				'post',
				'normal',
				'high',
				array( 'result' => $data )
			);
		}
	}

	public function render_meta_box_content( $post, $data ) {
		echo $data['args']['result'];
	}
}
