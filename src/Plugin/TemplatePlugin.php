<?php

namespace Metabolism\WordpressBundle\Plugin;


class TemplatePlugin {


	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates, $config;

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	public function __construct($config) {

		if( !is_admin() )
			return;

		$this->config = $config;

		$this->templates = $this->config->get('template', []);


		// Add a filter to the wp 4.7 version attributes metabox
		foreach ($this->templates as $post_type=>$templates)
		{
			add_filter( 'theme_'.$post_type.'_templates', function($post_templates) use($templates){

				return array_merge($post_templates, $templates);
			});
		}
	}
}
