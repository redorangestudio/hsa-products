<?php get_header(); ?>
<?php
$getPostLayout = get_post_meta(get_the_ID(), 'pm_post_layout_meta', true);
$postLayout = $getPostLayout !== '' ? $getPostLayout : 'no-sidebar';
$postStatus = get_post_meta(get_the_ID(), 'pm_post_visibility', true);
?>

<div class="container pm-containerPadding60 pm-single-post-container" style="padding-bottom:90px;">
    <div class="row">

        <div class="col-lg-9 col-md-9 col-sm-9">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <?php get_template_part('content', 'singlepost'); ?>
                <?php endwhile;
            else:
                ?>
                <p><?php _e('No post was found.', 'quantumtheme'); ?></p>
            <?php endif; ?> 
            <?php if ($postStatus === 'public') { ?>
                <?php comments_template('', true); ?>
        <?php } ?>
                
        </div>
<?php get_sidebar(); ?>

    </div>
</div>


<?php get_footer(); ?>