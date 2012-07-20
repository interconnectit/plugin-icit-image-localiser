<?php

/**
* Returns an ICIT_Image object representing a kitten at the requested size
*
* @param array $size An array containing the dimensions of the desired image, e.g. array(200,300) for a 200x300 image
*
* @example echo icit_kittens(100x200)->getURL(); echos the URL to a kitten image ready for frontend display
*
* @return ICIT_Image    An image object of a kitten
*/
function icit_kittens($size){
   if(is_array($size)){
	   return new ICIT_Image('http://placekitten.com/g/'.$size[0].'/'.$size[1]);
   }
}
//add_filter( 'attachment_fields_to_edit', 'metametameta', 10, 2 );

function metametameta( $form_fields, $post ){
	$metadata = get_metadata('post',$post->ID);

	$form_fields['metadata'] = array(
		'label' => 'metadata',
		'input' => 'html',
		'html' => '<pre>'.print_r($metadata,true).'</pre>'
	);
	return $form_fields;
}

if(!class_exists('ICIT_Image')){
	/**
	* Represents an image/attachment, has methods for acquiring images from alternate sources
	*/
	class ICIT_Image {

		public $ID = 0;

		public function __construct($image_id=0,$parent=0){
			if(is_numeric($image_id)){
				// we have an attachment ID! fill in all the gaps and be thankful
				// no heavy lifting is required
//				error_log('NUMERICS!!!');
			} else if(is_string($image_id)){
				// we have a URL! Prepare for a HTTP request and the creation of
				// a new attachment, do a check to see if we've already grabbed
				// this image first though
				if(!$this->hasDownloaded($image_id)){
					$this->downloadImage($image_id,$parent);
				}
			}
	   }

		public function is_valid(){
			return ($this->ID != 0);
		}


		protected function hasDownloaded($url){
			$q = new WP_Query(array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'post_mime_type' => 'image',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'original-source',
						'value' => $url,
						'compare' => '='
					),
					array(
						'key' => 'icit_original_url',
						'value' => $url,
						'compare' => '='
					)
				)
			));
			if($q->have_posts()){
				while($q->have_posts()){
					$q->the_post();
					$this->ID = get_the_ID();
					wp_reset_postdata();
					return true;
				}
				wp_reset_postdata();
			}
			return false;
		}

		public function attachTo($post_id){
			//
		}

		public function getURL($size=''){
			if(empty($size)){
				return wp_get_attachment_url( $this->ID );
			}
		}

		/**
		 * Download an image from the specified URL and attach it to a post.
		 *
		 * @since 2.6.0
		 *
		 * @param string $file The URL of the image to download
		 * @param int $post_id The post ID the media is to be associated with
		 * @param string $desc Optional. Description of the image
		 * @return string|WP_Error Populated HTML img tag on success
		 */
		protected function downloadImage($file, $post_id, $desc = null) {
			$id = 0;
			if ( ! empty($file) ) {
				$original_file = $file;
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/image.php');

				// Download file to temp location
				//$file = urlencode($file);
				$tmp = download_url( $file );

				// fix file filename
				$file = preg_replace( array( '/[^a-zA-Z0-9\.-_]/', '/_+/' ), '_', $file );
				preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $file, $matches);
				if(empty($matches)){
					$file_array['name'] = md5($file).'.jpg';
				} else {
					$file_array['name'] = basename($matches[0]);
				}
				$file_array['tmp_name'] = $tmp;

				// If error storing temporarily, unlink
				if ( is_wp_error( $tmp ) ) {
					
					$this->error = $tmp->get_error_messages();
					
					@unlink($file_array['tmp_name']);
					$file_array['tmp_name'] = '';
					return 0;
				}

				// do the validation and storage stuff
				$id = media_handle_sideload( $file_array, $post_id, $desc );
				// If error storing permanently, unlink
				if ( is_wp_error($id) ) {
					$this->ID = 0;
					$this->error = $id->get_error_messages();

					@unlink($file_array['tmp_name']);
					return $id;
				}

				add_post_meta($id,'original-source',$original_file);

				//$src = wp_get_attachment_url( $id );
			}
			$this->ID = $id;
			return $id;

		}
	}
}
