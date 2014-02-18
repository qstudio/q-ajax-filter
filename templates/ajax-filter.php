<?php

/**
 * AJAX Filter Template
 *
 * @since 1.7.0
 */

// globalize post object - we'll need this ##
global $post;
#pr( $post ); // check what's in the post object ##

?>
<article class="ajax-loaded">
    <h2><a href="<?php the_permalink(); ?>" title="<?php the_title();?>"><?php the_title();?></a></h2>
    <p>
        <a href="<?php the_permalink(); ?>" title="<?php the_title();?>">
            <?php the_post_thumbnail( array( 300, 300 ), array('class' => 'alignleft') ); ?>
        </a>
        <?php the_excerpt(); ?>
    </p>
</article>