<?php

include('icit_image.php');

if(!class_exists('ICIT_Feed_Images')){
	/**
	 * This class intercepts new Aggregator feed posts and downloads, then
	 * replaces all images in the content with locally hosted copies
	 */
	class ICIT_Feed_Images {
		public function __construct(){
			//add_filter('icit_rss_post_import_content',array(&$this,'sideloadImages'),1,3);

			add_filter('icit_rss_post_pre_update',array(&$this,'icit_rss_post_pre_update'),1,2);
			add_action('icit_rss_post_after_insertion',array(&$this,'icit_rss_post_after_insertion'),1,2);
		}

		public function icit_rss_post_pre_update($post_id, $post_data){
			$c = $this->sideloadImages($post_data['post_content'],$post_id);
			if($c != false){
				$post_data['post_content'] = $c;
			}
			return $post_data;
		}

		public function icit_rss_post_after_insertion($post_id, $post_data){
			$c = $this->sideloadImages($post_data['post_content'],$post_id);
			if($c != false){
				$post_data['ID'] = $post_id;
				$post_data['post_content'] = $c;
				wp_update_post($post_data);
			}
		}


		/**
		 * Searches for images in content, then downloads them, saves them
		 * locally, and replaces the original URLs with the local copies.
		 *
		 * Intended to be used with the filter "icit_rss_post_import_content
		 *
		 * @param string $content The content being processed
		 * @param integer $post_id The ID of the post the content was pulled from
		 *
		 * @return string    The processed content
		 */
		public function sideloadImages($content, $post_id){
			$images = $this->img_finder($content);
			if (is_array($images) && !empty($images)) {
				// images were found
				$first = 0;
				foreach($images as $image){
					//$kitten = icit_kittens(array($image['width'],$image['height']));
					//$kitty_url = $kitten->getURL();
					if(!$this->is_local_URL($image['src'])){
					
						$i = new ICIT_Image($image['src'],$post_id);
						if($i->is_valid()){
							if($first == 0){
//								error_log('first: '.print_r($image,true));
								$this->set_featured_image($post_id,$i->ID);
								$content = str_replace($image['tag'],'',$content);
							}
							$content = str_replace($image['src'],$i->getURL(),$content);
						} else {
							//error_log('invalid?'.print_r($i->error,true));
							return false;
						}
					}
					$first++;
				}
				return $content;
			}
			return $content;

		}

		public function set_featured_image($post_id,$attach_id){
			add_post_meta($post_id, '_thumbnail_id', $attach_id);
		}

		function is_local_URL($url){
			$site_url = get_site_url();
			//error_log('site_url: '.$site_url.' url: '.$url);
			if(strpos($url,$site_url) !== false){
				return true;
			}
			return false;
		}


		public function sideload_remote_images($post){
			return $this->sideloadImages($post->post_content,$post->ID);
		}

		public function acquire_remote_image($url,$parent=0){
			//
			$id = $this->media_sideload_image($url,$parent);
			$this->id = $id;
		}



		/**
		 * Searches HTML content for IMG tags and returns an array containing all
		 * IMG tags with src, alt, title, width and height attribites.
		 *
		 *
		 * @param string $content The content of the feed itme being processed
		 *
		 * @return array/bool    False when nothing found otherwise will return
		 * array like this:
		 * [0] => Array (
		 * 	[tag] => <img src="http://example.com/image.jpg" class="left image500" width="500" height="200" alt="text" title="more text"/>
		 * 	[src] => http://example.com/image.jpg
		 * 	[width] => 500
		 * 	[height] => 200
		 * 	[title] => more text
		 * 	[alt] => text
		 */
		function img_finder($content) {

			// Find all image tags in $content
			preg_match_all('!(<img\s+[^>]*?>)!si', $content, $matches);

			$index = 0;
			foreach((array)$matches[0] as $image) {
				$images[$index]['tag'] = $image;

				// Find some important attributes
				preg_match_all('!(src|alt|title|width|height)\s*?=\s*?([\'|"])([^\2]*?)\2!si', $image, $attributes);
				unset($attributes[0], $attributes[2]);



				foreach((array)$attributes[1] as $key => $attribute) {
					$images[$index][$attribute] = trim($attributes[3][$key]);
				}
				$index++;
			}

			if (count($images)) {
				return $images;
			} else {
				return false;
			}
		}
	}

	global $icit_feed_images;
	$icit_feed_images = new ICIT_Feed_Images();
}
