<?php

/**
 * Plugin Name: Company Directory Plugin
 * Plugin URI: 
 * Description: Examine and contrast open banking and open finance standards around the world.
 * Version: 1.0.0
 * Author: Naushad A.
 * Author URI: https://www.linkedin.com/in/naushad-ali-2a091725a/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: company-directory
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class Company_Directory_Plugin {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'admin_menu', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_company_meta' ] );
        add_action( 'admin_menu', [ $this, 'add_categories_submenu' ] );
		add_action( 'admin_menu', [$this, 'add_support_page'] );
        add_filter( 'post_row_actions', [ $this, 'add_duplicate_link' ], 10, 2 );
        add_action( 'admin_action_duplicate_company', [ $this, 'duplicate_company' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action('wp_ajax_filter_companies', [$this, 'ajax_filter_companies']);
		add_action('wp_ajax_nopriv_filter_companies', [$this, 'ajax_filter_companies']);
        $this->maybe_insert_default_terms();
    }

    public function register_post_type() {
        register_post_type('company', [
            'labels' => [
                'name' => 'Directory',
                'singular_name' => 'Directory',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Directory',
                'edit_item' => 'Edit Directory',
                'menu_name' => 'Directory',
            ],
            'public' => true,
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'thumbnail'],
            'has_archive' => true,
            'show_in_rest' => true,
        ]);
    }

	public function register_taxonomies() {
		register_taxonomy( 'cost', 'company', [
			'labels' => [
				'name'          => 'Cost',
				'singular_name' => 'Cost',
			],
			'public'            => true,
			'hierarchical'      => true, 
			'show_ui'           => true,
			'show_in_menu'      => false, 
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => null, 
		]);

		register_taxonomy( 'topic', 'company', [
			'labels' => [
				'name'          => 'Topic',
				'singular_name' => 'Topic',
			],
			'public'            => true,
			'hierarchical'      => true, 
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => null,
		]);

		register_taxonomy( 'approach', 'company', [
			'labels' => [
				'name'          => 'Approach',
				'singular_name' => 'Approach',
			],
			'public'            => true,
			'hierarchical'      => true, 
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => null,
		]);
	}

	public function activate_plugin() {
		$this->register_post_type();
		$this->register_taxonomies();
		$this->maybe_insert_default_terms();
		flush_rewrite_rules();
	}

	public function deactivate_plugin() {
		flush_rewrite_rules();
	}

	public function maybe_insert_default_terms() {
		$defaults = [
			'cost' => ['Free', 'Paid'],
			'topic' => ['Open Banking', 'Open Finance', 'Open Data'],
			'approach' => ['Hybrid', 'Market Driven', 'Regulated']
		];

		foreach ( $defaults as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! term_exists( $term, $taxonomy ) ) {
					wp_insert_term( $term, $taxonomy );
				}
			}
		}
	}

    public function add_meta_boxes() {
        add_meta_box('company_meta_box', 'Company Details', [ $this, 'render_meta_box' ], 'company', 'normal', 'high');
    }

    public function enqueue_admin_scripts($hook) {
		global $post_type;
		if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'company') {
			wp_enqueue_script('jquery-ui-tabs');
			wp_add_inline_script('jquery-ui-tabs', 'jQuery(function($){$("#company-tabs").tabs();});');
			wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
			wp_enqueue_style(
				'directory-plugin-admin',
				plugin_dir_url(__FILE__) . 'assets/css/directory-plugin-admin.css',
				array('jquery-ui-css'),
				'1.0.0'
			);
		}
	}

    public function render_meta_box($post) {
        $fields = [
            'General Info' => ['Description','Owner','Region','Scope','Principles','Products'],
            'Technical' => ['Data format','Approach','Access','Mandated/Premium','Key Features','Trust Framework','Security Model','Consent','Payment Initiation','Guidelines','Account Information','Developer Resources'],
            'Compliance' => ['History','Certification','Compliance','Governance','Associated Legislation','Related Standards']
        ];

        echo '<div id="company-tabs"><ul>';
        foreach ($fields as $tab => $f) {
            echo '<li><a href="#tab-' . sanitize_title($tab) . '">' . esc_html($tab) . '</a></li>';
        }
        echo '</ul>';

        foreach ($fields as $tab => $f) {
            echo '<div id="tab-' . sanitize_title($tab) . '">';
            foreach ($f as $field) {
                $key = sanitize_title($field);
                $value = get_post_meta($post->ID, $key, true);
                echo '<p><label><strong>' . esc_html($field) . ':</strong></label>';
                wp_editor($value, $key, ['textarea_name' => $key, 'media_buttons' => false, 'teeny' => true]);
                echo '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
    }


    public function save_company_meta($post_id) {
        if (get_post_type($post_id) !== 'company') return;
        $fields = [
			'Description','Owner','Region','Scope','Principles','Products','Data format','Approach','Access','Mandated/Premium','Key Features','Trust Framework','Security Model','Consent','Payment Initiation','Guidelines','Account Information','Developer Resources','History','Certification','Compliance','Governance','Associated Legislation','Related Standards'
		];
        foreach ($fields as $field) {
            $key = sanitize_title($field);
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, wp_kses_post($_POST[$key]));
            }
        }
    }


    public function add_duplicate_link($actions, $post) {
        if ($post->post_type == 'company') {
            $url = wp_nonce_url('admin.php?action=duplicate_company&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce');
            $actions['duplicate'] = '<a href="' . esc_url($url) . '" title="Duplicate this company">Duplicate</a>';
        }
        return $actions;
    }


    public function duplicate_company() {
        if (empty($_GET['post']) || !isset($_GET['duplicate_nonce'])) return;
        if (!wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__))) return;
        $post_id = intval($_GET['post']);
        $post = get_post($post_id);
        if (!$post) return;

        $new_post_id = wp_insert_post([
            'post_title' => $post->post_title . ' (Copy)',
            'post_type' => 'company',
            'post_status' => 'draft',
        ]);

        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $value) {
            update_post_meta($new_post_id, $key, maybe_unserialize($value[0]));
        }

        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    }


    public function add_categories_submenu() {
        add_submenu_page(
            'edit.php?post_type=company',
            'Company Categories',
            'Categories',
            'manage_options',
            'company-categories',
            [ $this, 'render_categories_page' ]
        );
    }

    public function render_categories_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['new_term'], $_POST['taxonomy']) && check_admin_referer('add_term_action', 'add_term_nonce')) {
            wp_insert_term(sanitize_text_field($_POST['new_term']), sanitize_text_field($_POST['taxonomy']));
            echo '<div class="updated"><p>New term added successfully.</p></div>';
        }

        if (isset($_GET['delete_term'], $_GET['taxonomy'])) {
            wp_delete_term(intval($_GET['delete_term']), sanitize_text_field($_GET['taxonomy']));
            echo '<div class="updated"><p>Term deleted successfully.</p></div>';
        }

        if (isset($_POST['edit_term'], $_POST['term_id'], $_POST['taxonomy']) && check_admin_referer('edit_term_action', 'edit_term_nonce')) {
            wp_update_term(intval($_POST['term_id']), sanitize_text_field($_POST['taxonomy']), [
                'name' => sanitize_text_field($_POST['edit_term'])
            ]);
            echo '<div class="updated"><p>Term updated successfully.</p></div>';
        }

        $taxonomies = [
            'cost' => 'Cost',
            'topic' => 'Topic',
            'approach' => 'Approach'
        ];

        echo '<div class="wrap"><h1>Company Categories</h1>';

        foreach ($taxonomies as $slug => $name) {
            echo '<h2>' . esc_html($name) . '</h2>';
            echo '<table class="widefat" style="max-width:600px;margin-bottom:20px;">';
            echo '<thead><tr><th>Name</th><th style="width:140px;">Action</th></tr></thead><tbody>';

            $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
            if ($terms) {
                foreach ($terms as $term) {
                    $delete_url = add_query_arg([
                        'page' => 'company-categories',
                        'taxonomy' => $slug,
                        'delete_term' => $term->term_id
                    ]);

                    echo '<tr>';
                    echo '<td id="term-name-' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</td>';
                    echo '<td>
                        <a href="#" class="edit-term" data-id="' . esc_attr($term->term_id) . '" data-tax="' . esc_attr($slug) . '">Edit</a> | 
                        <a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Delete this term?\');">Delete</a>
                    </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="2"><em>No terms found.</em></td></tr>';
            }
            echo '</tbody></table>';

            echo '<form method="post" style="margin-bottom:40px;">';
            wp_nonce_field('add_term_action', 'add_term_nonce');
            echo '<input type="hidden" name="taxonomy" value="' . esc_attr($slug) . '">';
            echo '<input type="text" name="new_term" placeholder="Add new ' . esc_attr($name) . '" required>';
            submit_button('Add ' . esc_html($name), 'secondary', '', false);
            echo '</form>';
        }

        echo '
        <div id="inline-edit-form" style="display:none;">
            <form method="post" id="edit-term-form">
                ' . wp_nonce_field('edit_term_action', 'edit_term_nonce', true, false) . '
                <input type="hidden" name="taxonomy" id="edit-taxonomy">
                <input type="hidden" name="term_id" id="edit-term-id">
                <input type="text" name="edit_term" id="edit-term-name" style="width:300px;">
                ' . get_submit_button('Update', 'primary', '', false) . '
                <button type="button" class="button cancel-edit">Cancel</button>
            </form>
        </div>';

        echo '</div>';

        ?>
        <script>
        jQuery(function($){
            let currentRow = null;
            $('.edit-term').on('click', function(e){
                e.preventDefault();
                const termID = $(this).data('id');
                const tax = $(this).data('tax');
                const termName = $('#term-name-'+termID).text();

                currentRow = $('#term-name-'+termID);
                $('#edit-term-id').val(termID);
                $('#edit-taxonomy').val(tax);
                $('#edit-term-name').val(termName);

                const formHTML = $('#inline-edit-form').html();
                currentRow.html(formHTML);
                currentRow.find('#edit-term-name').focus();
            });

            $(document).on('click', '.cancel-edit', function(e){
                e.preventDefault();
                location.reload();
            });
        });
        </script>
        <?php
    }

    public function ajax_filter_companies() {
        $args = [
            'post_type' => 'company',
            'posts_per_page' => -1,
        ];

        if (!empty($_POST['s'])) {
            $args['s'] = sanitize_text_field($_POST['s']);
        }

        $tax_query = [];
        $taxonomies = ['company_cost', 'company_topic', 'company_approach'];
        foreach ($taxonomies as $tax) {
            if (!empty($_POST[$tax])) {
                $tax_query[] = [
                    'taxonomy' => $tax,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_POST[$tax])
                ];
            }
        }

        if ($tax_query) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <div class="company-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large'); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(plugins_url('no-image.png', dirname(__FILE__))); ?>" alt="No image">
                    <?php endif; ?>

                    <div class="company-card-content">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p><?php echo wp_trim_words(get_the_content(), 25, '...'); ?></p>
                        <?php
                        $region = get_post_meta(get_the_ID(), 'region', true);
                        $owner = get_post_meta(get_the_ID(), 'owner', true);
                        if ($region || $owner) :
                            echo '<div class="company-meta">';
                            if ($owner) echo '<strong>Owner:</strong> ' . esc_html($owner) . '<br>';
                            if ($region) echo '<strong>Region:</strong> ' . esc_html($region);
                            echo '</div>';
                        endif;
                        ?>
                        <a href="<?php the_permalink(); ?>" class="view-details">View Details</a>
                    </div>
                </div>
                <?php
            }
            wp_reset_postdata();
            echo ob_get_clean();
        } else {
            echo '<p>No companies found matching your filters.</p>';
        }

        wp_die();
    }

	public function add_support_page() {
		add_submenu_page(
			'edit.php?post_type=company', 
			'Support Company Directory',  
			'Support',                    
			'manage_options',             
			'company-support',            
			[$this, 'render_support_page'] 
		);
	}

	public function render_support_page() {
	?>
		<div class="wrap company-support-page">
			<div id="company-support-container" style="max-width: 850px; margin: 20px auto 40px; font-family: 'Segoe UI', Tahoma, sans-serif;">
				<div style="background: #fff; padding: 25px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 25px;">
					<div style="text-align: center;">
						<img src="<?php echo esc_url( plugins_url( 'assets/images/icon.png', __FILE__ ) ); ?>" alt="Plugin Logo" style="max-height: 90px; margin-bottom: 15px;">
						<h1 style="color: #0073aa;">Thank You for Using Company Directory!</h1>
						<p style="font-size: 16px; color: #333; margin-top: 10px; line-height: 1.7;">
							We have built <strong>Company Directory</strong> with dedication to help WordPress users easily manage and showcase company listings beautifully and efficiently.<br>
							Your support helps us continue improving this plugin - adding more features, optimizing performance, and providing free updates to the community.
						</p>
					</div>

					<hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">

					<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-bottom: 30px;">
						<a href="https://wordpress.org/support/plugin/company-directory/reviews/#new-post" target="_blank"
							style="background: #fbbc05; color: #000; text-decoration: none; padding: 14px 22px; border-radius: 8px; font-weight: 600;">
							Leave a 5-Star Review
						</a>
					</div>
				</div>
				<div style="background: #fff; padding: 25px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 25px;">
					<h2 style="color: #0073aa; margin-bottom: 10px;">About This Plugin</h2>
					<p style="font-size: 15px; line-height: 1.7; color: #444;">
						<strong>Company Directory</strong> is a feature-rich plugin that lets you manage and display company listings,
						complete with categories, filters, search, AJAX filtering, and flexible HTML descriptions.
					</p>
					<ul style="list-style: disc; margin-left: 25px; line-height: 1.8; color: #333;">
						<li>Add, Edit, and Categorize Companies with ease</li>
						<li>AJAX-based search & filtering on the frontend</li>
						<li>Custom fields for details like owner, region, and company type</li>
						<li>WYSIWYG editor support for rich HTML formatting</li>
						<li>Categories by Cost, Topic, and Approach</li>
						<li>Quick Duplicate feature to clone companies</li>
					</ul>
				</div>

				<div style="margin-top: 40px; text-align: center;">
					<p style="font-size: 15px; color: #555;">Developed by <strong>Naushad A.</strong>.</p>
					<p>
						<a href="mailto:naushadali.rj@gmail.com" style="color: #0073aa; text-decoration: none;">Contact Support</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

}

$company_directory_plugin = new Company_Directory_Plugin();

add_filter('template_include', function($template) {
	if (is_post_type_archive('company')) {
		$plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-company.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}
	if (is_singular('company')) {
		$plugin_template = plugin_dir_path(__FILE__) . 'templates/single-company.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}
	return $template;
});

function cd_enqueue_frontend_styles() {
    if ( is_post_type_archive('company') || is_singular('company') ) {
        wp_enqueue_style(
            'company-directory-frontend',
            plugin_dir_url(__FILE__) . 'assets/css/company-directory-frontend.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'cd_enqueue_frontend_styles');

register_activation_hook(__FILE__, [ $company_directory_plugin, 'activate_plugin' ]);
register_deactivation_hook(__FILE__, [ $company_directory_plugin, 'deactivate_plugin' ]);
