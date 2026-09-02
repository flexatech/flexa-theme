<?php
/**
 * Title: Search Content
 * Slug: flexa/search-content
 * Categories: flexa
 */
?>
<!-- wp:group {"className":"main-content","layout":{"type":"constrained"}} -->
<div class="wp-block-group main-content">

	<!-- wp:query-title {"type":"search"} /-->

	<!-- wp:query {"queryId":10,"query":{"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"relevance","author":"","search":"","exclude":[],"sticky":"","inherit":true,"taxQuery":null,"parents":[],"format":[]}} -->
	<div class="wp-block-query">

		<!-- wp:post-template {"layout":{"type":"default"}} -->
			<!-- wp:pattern {"slug":"flexa/query-loop"} /-->
		<!-- /wp:post-template -->

		<!-- wp:pattern {"slug":"flexa/query-pagination"} /-->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'No results found. Try a different search term.', 'flexa' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:spacer {"height":"var(--wp--preset--spacing--s)"} -->
			<div style="height:var(--wp--preset--spacing--s)" aria-hidden="true" class="wp-block-spacer"></div>
			<!-- /wp:spacer -->

			<!-- wp:search {"label":"<?php esc_attr_e( 'Search again', 'flexa' ); ?>","showLabel":false,"buttonText":"<?php esc_attr_e( 'Search', 'flexa' ); ?>"} /-->
		<!-- /wp:query-no-results -->

	</div>
	<!-- /wp:query -->

</div>
<!-- /wp:group -->
