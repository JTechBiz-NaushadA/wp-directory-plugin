<?php
/**
 * Company Meta Fields (meta-fields.php)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add meta box
 */
function cd_add_company_meta_box() {
    add_meta_box(
        'company_meta_box',
        __('Company Details', 'company-directory'),
        'cd_company_meta_box_callback',
        'company',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cd_add_company_meta_box');


/**
 * Meta box callback
 */
function cd_company_meta_box_callback($post) {

    // Nonce for security
    wp_nonce_field('cd_company_save_meta', 'cd_company_meta_nonce');

    // fields we manage (all become saved as _cd_<field>)
    $fields = [
        // General
        'description','owner','region','scope','principles','products',
        // Technical
        'data_format','approach','access','mandated_premium',
        'key_features','trust_framework','security_model','consent',
        'payment_initiation','guidelines','account_information','developer_resources',
        // Compliance
        'history','certification','compliance','governance',
        'associated_legislation','related_standards'
    ];

    // Load current values
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = get_post_meta($post->ID, "_cd_$field", true);
    }
    ?>

    <style>
    /* small inline styles scoped to meta box to keep layout tidy */
    #company-tabs { margin-top:10px; }
    #company-tabs .cd-tab-nav { display:flex; gap:8px; margin-bottom:12px; list-style:none; padding-left:0; }
    #company-tabs .cd-tab-nav li { cursor:pointer; padding:8px 12px; background:#f1f1f1; border-radius:4px; font-weight:600; }
    #company-tabs .cd-tab-nav li.active { background:#fff; border:1px solid #ddd; }
    #company-tabs .cd-tab-content { display:none; padding:8px 0; }
    #company-tabs .cd-tab-content.active { display:block; }
    #company-tabs label { display:block; font-weight:600; margin:8px 0 6px; }
    #company-tabs textarea { width:100%; box-sizing:border-box; min-height:80px; padding:6px; border:1px solid #ddd; border-radius:4px; }
    </style>

    <div id="company-tabs">
        <ul class="cd-tab-nav" role="tablist">
            <li class="active" data-tab="general">General Info</li>
            <li data-tab="technical">Technical Details</li>
            <li data-tab="compliance">Compliance & Governance</li>
        </ul>

        <div id="tab-general" class="cd-tab-content active" role="tabpanel" aria-hidden="false">
            <?php
            $general = ['description','owner','region','scope','principles','products'];
            foreach ($general as $field) :
                $label = ucwords(str_replace('_',' ',$field));
                ?>
                <label for="cd_<?php echo esc_attr($field); ?>"><strong><?php echo esc_html($label); ?></strong></label>
                <textarea id="cd_<?php echo esc_attr($field); ?>" name="cd_<?php echo esc_attr($field); ?>"><?php echo esc_textarea($values[$field]); ?></textarea>
            <?php endforeach; ?>
        </div>

        <div id="tab-technical" class="cd-tab-content" role="tabpanel" aria-hidden="true">
            <?php
            $technical = [
                'data_format','approach','access','mandated_premium',
				'key_features','trust_framework','security_model','consent',
				'payment_initiation','guidelines','account_information','developer_resources'
            ];
            foreach ($technical as $field) :
                $label = ucwords(str_replace('_',' ',$field));
                ?>
                <label for="cd_<?php echo esc_attr($field); ?>"><strong><?php echo esc_html($label); ?></strong></label>
                <textarea id="cd_<?php echo esc_attr($field); ?>" name="cd_<?php echo esc_attr($field); ?>"><?php echo esc_textarea($values[$field]); ?></textarea>
            <?php endforeach; ?>
        </div>

        <div id="tab-compliance" class="cd-tab-content" role="tabpanel" aria-hidden="true">
            <?php
            $compliance = [
                'history','certification','compliance','governance',
				'associated_legislation','related_standards'
            ];
            foreach ($compliance as $field) :
                $label = ucwords(str_replace('_',' ',$field));
                ?>
                <label for="cd_<?php echo esc_attr($field); ?>"><strong><?php echo esc_html($label); ?></strong></label>
                <textarea id="cd_<?php echo esc_attr($field); ?>" name="cd_<?php echo esc_attr($field); ?>"><?php echo esc_textarea($values[$field]); ?></textarea>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    (function(){
        // tab switching
        const nav = document.querySelectorAll('#company-tabs .cd-tab-nav li');
        nav.forEach(item => item.addEventListener('click', function(){
            nav.forEach(n => n.classList.remove('active'));
            this.classList.add('active');
            const tab = this.dataset.tab;
            document.querySelectorAll('#company-tabs .cd-tab-content').forEach(c => {
                c.classList.remove('active');
                c.setAttribute('aria-hidden', 'true');
            });
            const active = document.getElementById('tab-' + tab);
            if (active) { active.classList.add('active'); active.setAttribute('aria-hidden', 'false'); }
        }));

        // ensure hidden fields are submitted
        document.addEventListener('submit', function() {
            document.querySelectorAll('#company-tabs textarea').forEach(function(field){
                field.disabled = false;
            });
        });
    })();
    </script>

    <?php
}

/**
 * Save meta fields
 */
function cd_save_company_meta($post_id) {
    // only for our CPT
    if ( get_post_type($post_id) !== 'company' ) return;

    // verify nonce from meta box
    if ( ! isset( $_POST['cd_company_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cd_company_meta_nonce'], 'cd_company_save_meta' ) ) {
        return;
    }

    // autosave / permissions / revisions checks
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = [
        'description','owner','region','scope','principles','products','data_format','approach',
		'access','mandated_premium','key_features','trust_framework','security_model','consent',
        'payment_initiation','guidelines','account_information','developer_resources','history',
		'certification','compliance','governance','associated_legislation','related_standards'
    ];

    foreach ( $fields as $field ) {
        $post_key = 'cd_' . $field;
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta( $post_id, '_cd_' . $field, wp_kses_post( wp_unslash( $_POST[ $post_key ] ) ) );
        } else {
            // If you want to remove meta when empty, uncomment:
            // delete_post_meta($post_id, '_cd_' . $field);
        }
    }
}
add_action('save_post', 'cd_save_company_meta');
