<?php
get_header();
?>

<div class="company-archive">
    <h1 class="archive-title">Company Directory</h1>
    <p class="archive-para">Examine and contrast open banking and open finance standards around the world.</p>

    <form id="company-filter-form" method="get">
        <input type="text" name="s" placeholder="Search companies..." value="<?php echo esc_attr(get_search_query()); ?>">

        <?php
        $taxonomies = [ 'cost' => 'Cost', 'topic' => 'Topic', 'approach' => 'Approach' ];

        foreach ($taxonomies as $tax => $label) {
            $terms = get_terms([ 'taxonomy' => $tax, 'hide_empty' => false ]);
            if (!is_wp_error($terms)) {
                echo '<select name="'.esc_attr($tax).'">';
                echo '<option value="">'.esc_html($label).'</option>';
                foreach ($terms as $term) {
                    echo '<option value="'.esc_attr($term->slug).'">'.esc_html($term->name).'</option>';
                }
                echo '</select>';
            }
        }
        ?>

        <button type="submit" class="filter-btn">Search</button>
    </form>

    <?php
    // Taxonomy filter
    $tax_query = [];
    foreach ($taxonomies as $taxonomy => $label) {
        if (!empty($_GET[$taxonomy])) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET[$taxonomy]),
            ];
        }
    }

    // Query
    $args = [
        'post_type'      => 'company',
        'posts_per_page' => 9,
        'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
        's'              => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
    ];

    if ($tax_query) $args['tax_query'] = $tax_query;

    $company_query = new WP_Query($args);
    ?>

    <?php if ($company_query->have_posts()) : ?>
        <div class="company-list">

            <?php while ($company_query->have_posts()) : $company_query->the_post(); ?>

                <div class="company-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large'); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(plugins_url('no-image.png', dirname(__FILE__))); ?>" alt="No image">
                    <?php endif; ?>

                    <div class="company-card-content">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                        <p>
                            <?php
                            // FIXED
                            $description = wp_strip_all_tags( get_post_meta(get_the_ID(), 'description', true) );
                            $short_desc  = wp_trim_words($description, 25, '...');
                            echo esc_html($short_desc);
                            ?>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="read-more" style="color:#144796;"> Read More</a>
                        </p>

                        <?php
                        // FIXED
                        $owner  = trim( wp_strip_all_tags( get_post_meta(get_the_ID(), 'owner', true) ) );
                        $region = trim( wp_strip_all_tags( get_post_meta(get_the_ID(), 'region', true) ) );

                        if ($owner || $region) :
                            echo '<div class="company-meta">';
                            if ($owner)  echo '<strong>Owner:</strong> '  . esc_html($owner)  . '<br>';
                            if ($region) echo '<strong>Region:</strong> ' . esc_html($region);
                            echo '</div>';
                        endif;
                        ?>
                    </div>
                </div>

            <?php endwhile; ?>

        </div>

        <div class="pagination" style="margin-top:30px;text-align:center;">
            <?php echo paginate_links([
                'total'   => $company_query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
            ]); ?>
        </div>

    <?php else : ?>
        <p style="text-align:center;">No companies found.</p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
