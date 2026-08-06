<?php
/**
 * Additional filters and accessibility fixes
 *
 * @package Flexa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure <ul> elements inside navigation and page-list blocks
 * contain only valid children (<li>, <script>, <template>).
 */
function flexa_fix_nav_list_structure( $block_content, $block ) {
	if ( empty( $block_content ) || false === strpos( $block_content, '<ul' ) ) {
		return $block_content;
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML(
		'<html><head><meta charset="UTF-8"></head><body>' . $block_content . '</body></html>',
		LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();

	$changed    = false;
	$valid_tags = array( 'li', 'script', 'template' );
	$uls        = $dom->getElementsByTagName( 'ul' );

	foreach ( $uls as $ul ) {
		$invalid = array();
		foreach ( $ul->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			if ( ! in_array( strtolower( $child->nodeName ), $valid_tags, true ) ) {
				$invalid[] = $child;
			}
		}

		foreach ( $invalid as $node ) {
			$li = $dom->createElement( 'li' );
			$li->setAttribute( 'class', 'flexa-nav-item-wrap' );
			$node->parentNode->replaceChild( $li, $node );
			$li->appendChild( $node );
			$changed = true;
		}
	}

	if ( ! $changed ) {
		return $block_content;
	}

	$body   = $dom->getElementsByTagName( 'body' )->item( 0 );
	$output = '';
	foreach ( $body->childNodes as $child ) {
		$output .= $dom->saveHTML( $child );
	}

	return $output;
}
add_filter( 'render_block_core/navigation', 'flexa_fix_nav_list_structure', 10, 2 );
add_filter( 'render_block_core/page-list', 'flexa_fix_nav_list_structure', 10, 2 );