<?php
get_header();
?>
<div class="content">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <?php
                    if(have_posts()){
                        while(have_posts()){
                            the_post(); ?>

                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> 
                            <?php
                            the_content();
                        }
                    }
                    ?>
                </div>
                <div class="col-md-4">
                    <?php dynamic_sidebar('main-sidebar'); ?>
                </div>
            </div>
        </div>
    </div>
<?php
get_footer();
?>

