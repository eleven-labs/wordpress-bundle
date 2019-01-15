<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class Image
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Image extends Entity
{
	public $type;
	public static $wp_upload_dir = false;


	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $data = $this->get($id) )
			$this->import($data, false, 'post_');
	}


	private function uploadDir($field)
	{
		if ( !self::$wp_upload_dir )
			self::$wp_upload_dir = wp_upload_dir();

			return self::$wp_upload_dir[$field];
	}


	/**
	 * Remove useless data
	 */
	protected function get($id)
	{
		$metadata = wp_get_attachment_metadata($id);
		$post = get_post($id, ARRAY_A);
		$post_meta = get_post_meta($id);

		if( !$post || is_wp_error($post) )
			return false;

		if( empty($metadata) || !isset($metadata['file'], $metadata['image_meta']) )
			return false;


		$metadata['src']  = $this->uploadDir('baseurl').'/'.$metadata['file'];
		$metadata['src']  = str_replace(WP_FOLDER.'/..', '', $metadata['src']);

		$metadata['file'] = $this->uploadDir('baseurl').'/'.$metadata['file'];
		$metadata['meta'] = $metadata['image_meta'];
		$metadata['alt']  = trim(strip_tags(get_post_meta($id, '_wp_attachment_image_alt', true)));

		foreach($post_meta as $key=>$value)
		{
			if( in_array($key, ['_wp_attached_file', '_wp_attachment_metadata']) )
				continue;

			if($key == '_wp_attachment_image_alt')
			{
				$post['alt'] = trim($value[0]);
			}
			else
			{
				$value = (is_array($value) and count($value)==1) ? $value[0] : $value;
				$unserialized = @unserialize($value);
				$post[$key] = $unserialized?$unserialized:$value;
			}
		}

		if( !empty($metadata) )
			unset($metadata['sizes'], $metadata['image_meta']);

		unset($post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);

		if( is_array($metadata) )
			return array_merge($post, $metadata);
		else
			return $post;
	}
}
