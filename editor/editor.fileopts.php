<?php
/**
 *
 *
 *  PageLines dat file handling Class
 *
 *
 */
class EditorFileOpts {

	var $configfile = 'pl-config.json';
	
	function __construct() {

		if( isset( $_GET['pl_exp'] ) ) {
			$this->data = new stdClass;
			
			if( isset( $_GET['export_types'] ) )
				$this->data->export_types = 1;
		
			if( isset( $_GET['export_global'] ) )
				$this->data->export_global = 1;
				
			if( isset( $_GET['templates'] ) ) {
				
				$t = explode( '|', $_GET['templates'] );
						
				foreach( $t as $k =>$template)
					$this->data->templates[$template] = 'on';
			}
			$this->make_download();
		}

		// setup some vars...			
		$this->child_dir = trailingslashit( get_stylesheet_directory() );
		$uploads = wp_upload_dir();
		$this->uploads_dir = trailingslashit( trailingslashit( $uploads['basedir'] ) . 'pagelines' );
	}

	function get_file_headers() {
		
		if( is_child_theme() ) {
			$data = sprintf( "\n/*\nTheme: %s v%s\nChild: %s v%s\nExported: %s\n*/\n", PL_THEMENAME, PL_CORE_VERSION, PL_CHILDTHEMENAME, PL_CHILD_VERSION, date( 'l jS \of F Y h:i:s A' ) );	
		} else {
			$data = sprintf( "\n/*\nTheme: %s v%s\nExported: %s\n*/\n", PL_THEMENAME, PL_CORE_VERSION, date( 'l jS \of F Y h:i:s A' ) );		
		}	
		return $data;
	}

	function strip_header( $data ) {
		return strtok($data, "\n");
	}

	function init( $data ) {

		$this->data = json_decode( $data );
				
		if( isset( $this->data->publish_config ) )
		 	$res = $this->dump( 'child' );
		else
			$res = 'download';
			
		return $res;
	}

	function make_download(){

		header('Cache-Control: public, must-revalidate');
		header('Pragma: hack');
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename=' . $this->configfile );
		echo $this->getopts();		
		exit();	
	}

	function dump($type) {

		add_filter( 'request_filesystem_credentials', '__return_true' );

		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		
		if ( is_writable( $folder ) ){
			
			$creds = request_filesystem_credentials( $url, $method, false, false, null );
			if ( ! WP_Filesystem($creds) )
				return false;
		}
		
		global $wp_filesystem;
		
		if( is_object( $wp_filesystem ) ) {
			$wp_filesystem->put_contents( $this->child_dir . $this->configfile, $this->getopts(), FS_CHMOD_FILE);
		}
		return 'child';
	}


	function import( $file ) {

		$parsed = array( 'nothing' );
		$file_contents = pl_file_get_contents( $file ) ;
		
		$file_data = $this->strip_header( $file_contents ); 

		$file_data = json_decode( $file_data );
		$file_data = json_decode( json_encode( $file_data ), true);
			
		if( is_array( $file_data ) )
			$parsed[] = 'arr!';

		if( is_object( $file_data ) )
			$parsed[] = 'ob';
		
		// IMPORT MAIN
		if( isset( $file_data[PL_SETTINGS] ) ) {
			update_option( PL_SETTINGS, $file_data[PL_SETTINGS] );
			$parsed[] = 'globals';
		}
		
		// IMPORT MAP
		// if( isset( $file_data['pl-template-map'] ) ) {
		// 	update_option( 'pl-template-map', $file_data['pl-template-map'] );
		// 	$parsed[] = 'main-map';
		// }
		
		// IMPORT USER MAPS
		if( isset( $file_data['pl-user-templates'] ) ) {
			update_option( 'pl-user-templates', $file_data['pl-user-templates'] );
			$parsed[] = 'user_templates';
		}
		
		// IMPORT AWESOMENESS
		if( isset( $file_data['post_meta'] ) ) {
			
			foreach( $file_data['post_meta'] as $key => $data ) {
				update_post_meta( $key, 'pl-settings', $data[0] );
			}
			$parsed[] = 'meta-data';
		}
	
		return json_encode( pl_arrays_to_objects( $parsed ) );
	}

	function getopts() {

		$option = array();


		// do globals
		if( isset( $this->data->export_global ) )
			$option['pl-settings'] = get_option( PL_SETTINGS, array( 'draft' => array(), 'live' => array() ) );

		// grab the map
		// $option['pl-template_map'] = get_option( 'pl-template-map', array() );
			
		// grab user templates
		if( isset( $this->data->templates ) ) {
			
			$templates =  get_option( 'pl-user-templates', array() );
			
			foreach( $this->data->templates as $t => $s ) {
				if( isset( $templates[$t] ) )
					$option['pl-user-templates'][$t] = $templates[$t];
			}		
		}

		if( isset( $this->data->export_types ) ) {


			$lookup_array = array(
				'blog',
				'category',
				'search',
				'tag',
				'author',
				'archive',
				'page',
				'post',
				'404_page'
			);
			
			$args = array(
			    'public'                => true,
			    'exclude_from_search'   => false,
			    '_builtin'              => true
			); 
			$output = 'names'; // names or objects, note names is the default
			$operator = 'and'; // 'and' or 'or'
			$post_types = array_unique( get_post_types($args,$output,$operator));
			$meta = array();
			$master = array_merge( $post_types, $lookup_array );

			foreach( $master as $t => $type ) {
				$key = pl_create_int_from_string( $type ) + 70000000;
				$meta[$key] = get_post_meta( $key, 'pl-settings' );
				if( empty( $meta[$key] ) )
					unset( $meta[$key] );
			}

			$option['post_meta'] = $meta;
		}
		
		$contents = json_encode( $option );
		$contents .= $this->get_file_headers();
		return $contents;
	}
	
	function file_exists() {
		
		if( is_file( trailingslashit( $this->child_dir ) . $this->configfile ) )
			return trailingslashit( $this->child_dir ) . $this->configfile;
			
	}
}