<?php
/**
 * Template: single-company.php
 */
get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
?>

<div class="company-single-wrapper">
<div class="company-single">

    <h1><?php the_title(); ?></h1>

	<div class="company-subheader">
		<?php
		$owner  = trim( wp_strip_all_tags( get_post_meta(get_the_ID(), 'owner', true) ) );
		$region = trim( wp_strip_all_tags( get_post_meta(get_the_ID(), 'region', true) ) );

		if ($owner || $region) {
			echo esc_html($owner . ($owner && $region ? ' • ' : '') . $region);
		}
		?>
	</div>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="company-thumb"><?php the_post_thumbnail('medium'); ?></div>
    <?php endif; ?>

    <div class="company-description">
        <?php
            // FIXED
            $description = get_post_meta(get_the_ID(), 'description', true);

            if (!empty($description)) {
                echo wpautop(wp_kses_post($description));
            } else {
                echo '<p>No description available.</p>';
            }
        ?>
    </div>

    <div class="company-taxonomies">
        <?php
        $taxonomies = [
            'cost'     => 'Cost',
            'topic'    => 'Topic',
            'approach' => 'Approach'
        ];
        foreach ($taxonomies as $taxonomy => $label) {
            $terms = wp_get_post_terms(get_the_ID(), $taxonomy, ['fields' => 'names']);
            if (!empty($terms)) {
                echo '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html(implode(', ', $terms)) . '</p>';
            }
        }
        ?>
    </div>

    <?php
    // Your section structure (unchanged)
    $sections = [
        'General Info' => [ 'owner','region','scope','principles','products' ],
        'Technical Details' => [ 'data_format','approach','access','mandated_premium','key_features','trust_framework','security_model','consent','payment_initiation','guidelines','account_information','developer_resources' ],
        'Compliance & Governance' => [ 'history','certification','compliance','governance','associated_legislation','related_standards' ],
    ];

    foreach ($sections as $section_title => $fields) :
        echo '<div class="company-meta-section">';
        echo '<h2>' . esc_html($section_title) . '</h2>';

        foreach ($fields as $field):

			// Convert field underscores to DB hyphens
			$meta_key = str_replace('_', '-', $field);

			$label = ucwords(str_replace('_', ' ', $field));
			$value = get_post_meta(get_the_ID(), $meta_key, true);

			if (!empty($value)):
				echo '<div class="meta-field">';
				echo '<h3>' . esc_html($label) . '</h3>';
				echo '<div class="meta-value">' . wpautop(wp_kses_post($value)) . '</div>';
				echo '</div>';
			endif;

		endforeach;

        echo '</div>';
    endforeach;
    ?>

    <a href="<?php echo esc_url(get_post_type_archive_link('company')); ?>" class="back-link">← Back to Directory</a>

</div>
</div>

<?php
    endwhile;
endif;
get_footer();
