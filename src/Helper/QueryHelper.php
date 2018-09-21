<?php

namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Helper\ACF;

use Metabolism\WordpressBundle\Entity\Term,
	Metabolism\WordpressBundle\Entity\Post;

/**
 * Class Query
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Query
{
	public static function get_fields($id)
	{
		$post = new ACF($id);
		return $post->get();
	}


	public static function get_terms($args=[])
	{
		$args['fields'] = 'ids';

		$terms = get_terms( $args );

		foreach ($terms as &$term)
		{
			$term = new Term( $term );
		}

		return $terms;
	}

	public static function get_adjacent_posts($post_id, $args=[], $loop=false)
	{
		$default_args = [
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'fields' => 'ids'
		];

		$args = array_merge($default_args, $args);

		$query = new \WP_Query($args);

		$next_id = $prev_id = false;

		foreach($query->posts as $key => $_post_id) {
			if($_post_id == $post_id){
				$next_id = isset($query->posts[$key + 1]) ? $query->posts[$key + 1] : false;
				$prev_id = isset($query->posts[$key - 1]) ? $query->posts[$key - 1] : false;
				break;
			}
		}

		if( !$next_id && $loop )
			$next_id = $query->posts[0];

		if( !$prev_id && $loop )
			$prev_id = $query->posts[ count($query->posts) - 1 ];

		return [
			'next' => $next_id ? new Post($next_id) : false,
			'prev' => $prev_id ? new Post($prev_id) : false
		];
	}


	public static function get_term_by($field, $value, $taxonomy)
	{
		$term = get_term_by( $field, $value, $taxonomy );

		if( $term )
			return new Term( $term->term_id );
		else
			return false;
	}


	public static function get_post_terms($id, $primary=false)
	{
		$taxonomies = get_post_taxonomies( $id );
		$post_terms = [];

		foreach($taxonomies as $taxonomy)
			$post_terms[$taxonomy] = self::get_post_term($id, $taxonomy, $primary);

		return $post_terms;
	}


	public static function get_post_term($id, $taxonomy, $primary=false)
	{
		if( $primary and class_exists('WPSEO_Primary_Term') )
		{
			$wpseo_primary_term = new \WPSEO_Primary_Term( $taxonomy, $id );

			if( $wpseo_primary_term and $wpseo_primary_term->get_primary_term() )
				return new Term( $wpseo_primary_term->get_primary_term() );
		}

		$terms = wp_get_post_terms($id, $taxonomy, ['fields' => 'ids']);

		if( $primary ){

			if( !empty($terms) )
				return new Term($terms[0]);
			else
				return false;
		}
		else{

			$post_terms = [];

			foreach($terms as $term)
				$post_terms[$taxonomy][] = new Term($term);

			return $post_terms;
		}
	}


	public static function get_post($args=[])
	{
		if( empty($args) )
			return new Post();

		if( !is_array($args) )
			return new Post($args);

		$args['posts_per_page'] = 1;

		$posts = self::get_posts($args);

		if( count($posts) )
			return $posts[0];

		return $posts;
	}


	public static function get_posts($args=[])
	{
		$query = self::wp_query($args);

		return $query->posts;
	}


	public static function wp_query($args=[])
	{
		global $wp_query;

		if( empty($args) )
		{
			$query = $wp_query;
		}
		else
		{
			if( !isset($args['post_type']) )
				$args = array_merge($wp_query->query, $args);

			if( !isset($args['posts_per_page']) and !isset($args['numberposts']))
				$args['posts_per_page'] = get_option( 'posts_per_page' );

			$args['fields'] = 'ids';
			$query = new \WP_Query( $args );
		}

		if( !isset($query->posts) || !is_array($query->posts) )
			return false;

		foreach ($query->posts as &$post)
		{
			$post = new Post( $post );
		}

		return $query;
	}
}
