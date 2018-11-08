<?php

namespace SmartLink;

class MetaBoxes {
	private $name;
	private $title;
	private $anchors;

	/**
	 * MetaBoxes constructor.
	 *
	 * @param $name string
	 * @param $title string
	 * @param $anchors Anchors
	 */
	public function __construct( $name, $title, $anchors ) {
		$this->name    = $name;
		$this->title   = $title;
		$this->anchors = &$anchors;
		add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
	}

	public function add_some_meta_box() {
		$post = get_post();
		$data = $this->anchors->get_post_anchor_list( $post );
		if ( strlen( $data ) ) {
			add_meta_box(
				$this->name,
				$this->title,
				array( &$this, 'render_meta_box_content' ),
				'post',
				'normal',
				'high',
				array(
					'__block_editor_compatible_meta_box' => true,
					'result' => $data
				)
			);
		}
	}

	public function render_meta_box_content( $post, $data ) {
		echo $data['args']['result'];
	}
}
