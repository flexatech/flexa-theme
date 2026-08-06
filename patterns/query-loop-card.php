<?php
/**
 * Title: Query Loop Card
 * Slug: flexa/query-loop-card
 * Categories: flexa
 */
?>
<!-- wp:group {"tagName":"article","className":"post-card","layout":{"type":"constrained"}} -->
<article class="wp-block-group post-card">

	<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->

	<!-- wp:group {"className":"post-card__body","layout":{"type":"constrained"}} -->
	<div class="wp-block-group post-card__body">

		<!-- wp:post-terms {"term":"category"} /-->

		<!-- wp:post-title {"isLink":true,"level":2} /-->

		<!-- wp:group {"className":"post-meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group post-meta">
			<!-- wp:post-date /-->
			<!-- wp:post-author {"showAvatar":false} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:post-excerpt {"moreText":"","showMoreOnNewLine":false} /-->

	</div>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
