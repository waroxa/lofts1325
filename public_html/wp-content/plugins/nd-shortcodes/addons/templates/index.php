<?php

$nd_options_archive_enable = get_option('nd_options_archive_enable');
$nd_options_comment_enable = get_option('nd_options_comment_enable');
$nd_options_search_enable = get_option('nd_options_search_enable');
$nd_options_page_enable = get_option('nd_options_page_enable');
$nd_options_post_enable = get_option('nd_options_post_enable');

// all files
if ( $nd_options_archive_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/archive/index.php';
	include realpath($nd_options_layout_selected);

}

if ( $nd_options_comment_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/comment/index.php';
	include realpath($nd_options_layout_selected);

}

if ( $nd_options_search_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/search/index.php';
	include realpath($nd_options_layout_selected);

}

if ( $nd_options_page_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/page/index.php';
	include realpath($nd_options_layout_selected);

}

if ( $nd_options_post_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/post/index.php';
	include realpath($nd_options_layout_selected);

}
