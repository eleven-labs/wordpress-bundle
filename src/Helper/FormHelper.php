<?php

namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Plugin\MediaPlugin as Media;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class Form {

	/**
	 * Get request parameter
	 */
	public static function getField( $key, $limit_lengh=500 )
	{
		if( isset($_FILES[$key]))
		{
			$upload = Media::upload($key, ['image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/zip']);

			if( is_wp_error($upload) )
				return false;

			return $upload['filename'];
		}
		elseif ( !isset( $_REQUEST[ $key ] ) )
		{
			return false;
		}
		else
		{
			return substr( trim(sanitize_text_field( $_REQUEST[ $key ] )), 0, $limit_lengh );
		}
	}

	/**
	 * Get whole form
	 */
	public static function get($fields=[]){

		$form = [];

		foreach ( $fields as $key )
		{
			$form[$key] = self::getField( $key );
		}

		return $form;
	}

	/**
	 * Send form
	 */
	public static function send($to='admin', $subject='New message from website', $fields=[], $attachements=[], $email_id='email' ){

		if( !in_array($email_id, $fields) )
			$fields[] = $email_id;

		$form = self::get($fields);

		if ( is_email( $form[$email_id] ) )
		{
			if(!$to || $to='admin')
				$to = get_option( 'admin_email' );

			$body = $subject." :\n\n";
			$attachments = [];

			foreach ( $fields as $key ) {

				$body .= ($form[$key] ? ' - ' . $key . ' : ' . $form[$key] . "\n" : '');
			}

			foreach ( $attachements as $attachement )
			{
				if ( file_exists( $attachement ) )
				{
					$attachments[] = $attachement;
				}
			}

			$data = array(
				"key" => $_SERVER['MANDRILL_API_KEY'],
				"message" => array(
					"to" => array( 
						array("email" => 'pierre.lerouge.pro@gmail.com'),
						array("email" => 'pieka@live.fr'),
					),
					"subject" => $subject,
					"html" => $body,
					"from_email" => "drh@valtus.fr",
					"from_name" => "Contact Valtus",
				),
			);
			
			$payload = json_encode($data);
			
			$ch = curl_init($_SERVER['MANDRILL_API_URL']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload))
			);
	
			$result = json_decode(curl_exec($ch));

			if ($result[0]->status === 'sent') 
				return $form;
			else
				return new \WP_Error('send_mail', "The server wasn't able to send the email.");
		}
		else
		{
			return new \WP_Error('invalid_email', "Invalid email address. Please type a valid email address.");
		}
	}
}
