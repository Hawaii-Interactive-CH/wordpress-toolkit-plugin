<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

use Toolkit\utils\Icon;

/**
 * Handles custom post type registration and related AJAX actions.
 *
 * @class Register
 */
class RegisterService
{

    /**
     * Registers actions for custom post types and AJAX.
     */
    public static function register()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('toolkit-admin-scripts', WP_TOOLKIT_URL . '/admin/assets/js/toolkit-admin-scripts.js', array('jquery'), null, true);
        });
        // Register AJAX actions.
        add_action("wp_ajax_create_cpt_models", [self::class, "create_model_action"]);
        add_action("wp_ajax_create_cpt_blocks", [self::class, "create_block_action"]);
    }

    /**
     * Render create model tab.
     */
    public static function render_create_model_tab()
    {
?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a class="nav-tab nav-tab-active" href="#tab1"><?= __('Model', 'toolkit'); ?></a>
                <a class="nav-tab" href="#tab2"><?= __('Block', 'toolkit'); ?></a>
            </h2>

            <?php
            // Enqueue scripts
            wp_enqueue_script('toolkit-ajax-scripts', WP_TOOLKIT_URL . '/admin/assets/js/toolkit-admin-ajax.js', array('jquery'), null, true);

            wp_localize_script('toolkit-ajax-scripts', 'cptwp_admin_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonces' => array(
                    'generate_template' => wp_create_nonce('generate_template_nonce'),
                ),
            ));
            ?>

            <div id="response-message"></div>
            <!-- Model content -->
            <div id="tab1" class="tab-content toolkit">
                <h3><?= __('Create New Model', 'toolkit'); ?></h3>

                <form id="create-model-form">
                    <div class="fields">
                        <?php
                        // Define the field data
                        $fields = array(
                            'model_name' => __("Name", "toolkit"),
                            'model_label' => __("Label", "toolkit"),
                            'model_singular_name' => __("Singular Name", "toolkit"),
                            'model_slug' => __("Slug", "toolkit"),
                            'model_menu_name' => __("Menu Name", "toolkit"),
                            'model_all_items' => __("All Items", "toolkit"),
                            'model_add_new' => __("Add New", "toolkit"),
                            'model_add_new_item' => __("Add new Item", "toolkit"),
                            'model_edit_item' => __("Edit Item", "toolkit"),
                            'model_new_item' => __("New Item", "toolkit"),
                            'model_view_item' => __("View Item", "toolkit"),
                            'model_view_items' => __("View Items", "toolkit"),
                            'model_search_items' => __("Search Items", "toolkit"),
                            'model_supports' => __("Supports", "toolkit"),
                        );

                        $placeholder = array(
                            'model_name' => __("Demo", "toolkit"),
                            'model_label' => __("Demos", "toolkit"),
                            'model_singular_name' => __("Demo", "toolkit"),
                            'model_slug' => __("demos", "toolkit"),
                            'model_menu_name' => __("Demos", "toolkit"),
                            'model_all_items' => __("All demos", "toolkit"),
                            'model_add_new' => __("Add new", "toolkit"),
                            'model_add_new_item' => __("Add new demo", "toolkit"),
                            'model_edit_item' => __("Edit demo", "toolkit"),
                            'model_new_item' => __("New demo", "toolkit"),
                            'model_view_item' => __("View demo", "toolkit"),
                            'model_view_items' => __("View demos", "toolkit"),
                            'model_search_items' => __("Search demo", "toolkit"),
                            'model_supports' => __("title, editor, thumbnail, excerpt", "toolkit"),
                        );

                        // HTML form fields
                        foreach ($fields as $field_name => $label) {
                        ?>
                            <div class="field">
                                <label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($label); ?>:</label>
                                <input value="<?php echo $placeholder[$field_name] ?>" type="text" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" required>
                            </div>
                        <?php
                        }
                        ?>

                        <!-- Icon Select Menu -->
                        <div class="field">
                            <label for="model_icon">Icon: <span id="icon_preview" class="icon-preview"></span></label>
                            <select id="model_icon" name="model_icon">
                                <?php foreach (Icon::ICONS as $icon_key => $icon_value) : ?>
                                    <option value="<?php echo esc_attr($icon_key); ?>">
                                        <?php echo esc_html($icon_value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Checkbox to create a category -->
                        <div class="field">
                            <label for="create_category">Create Category:</label>
                            <input type="checkbox" id="create_category" name="create_category" value="1">
                        </div>

                    </div>

                    <?php wp_nonce_field('create_model_nonce', 'create_model_nonce'); ?>

                    <div class="field field--submit">
                        <input class="button button-primary" type="submit" value="Create Model">
                    </div>
                </form>
            </div>

            <!-- Block content -->
            <div id="tab2" class="tab-content toolkit" style="display: none;">
                <h3><?= __('Create New Block', 'toolkit'); ?></h3>
                <form id="create-block-form">
                    <div class="fields">
                        <div class="field">
                            <label for="block_title"><?= __("Title", "toolkit"); ?>:</label>
                            <input type="text" id="block_title" name="block_title" required>
                        </div>
                        <div class="field">
                            <label for="block_description"><?= __("Description", "toolkit"); ?>:</label>
                            <input type="text" id="block_description" name="block_description" required>
                        </div>
                        <div class="field">
                            <label for="block_icon"><?= __("Icon", "toolkit"); ?>:</label>
                            <input type="text" id="block_icon" name="block_icon" value="block-default">
                        </div>
                        <div class="field">
                            <label for="block_keywords"><?= __("Keywords", "toolkit"); ?>:</label>
                            <input type="text" id="block_keywords" name="block_keywords" value="section, hi-block">
                        </div>
                    </div>
                    <?php wp_nonce_field('create_block_nonce', 'create_block_nonce'); ?>
                    <div class="field field--submit">
                        <input class="button button-primary" type="submit" value="Create Block">
                    </div>
                </form>
        </div>
    <?php
    }

    /**
     * Generates custom post type class file.
     */
    public static function create_model_action()
    {
        check_ajax_referer('create_model_nonce', 'security');
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('Unauthorized request.', 'toolkit'), 403);
        }

        $formData = self::sanitize_model_form_data($_POST['formData'] ?? []);
        if (empty($formData['model_name']) || empty($formData['model_slug'])) {
            wp_send_json_error(__('Invalid model payload.', 'toolkit'), 400);
        }

        $className = ucfirst($formData['model_name']);
        $filename = WP_TOOLKIT_THEME_PATH . '/models/custom/' . $className . '.php';

        if (file_exists($filename)) {
            wp_send_json_error(__("Model already exists.", 'toolkit'), 409);
        }

        // Generate class PHP file content
        $phpContent = '<?php' . PHP_EOL . PHP_EOL;
        $phpContent .= 'namespace Toolkit\models\custom;' . PHP_EOL . PHP_EOL;
        $phpContent .= 'use Toolkit\models\CustomPostType;' . PHP_EOL . PHP_EOL;
        $phpContent .= 'class ' . $className . ' extends CustomPostType implements \\JsonSerializable' . PHP_EOL;
        $phpContent .= '{' . PHP_EOL;
        $phpContent .= '    const TYPE = \'' . strtolower($formData['model_name']) . '\';' . PHP_EOL;
        $phpContent .= '    const SLUG = \'' . $formData['model_slug'] . '\';' . PHP_EOL . PHP_EOL;
        $phpContent .= '    public static function type_settings()' . PHP_EOL;
        $phpContent .= '    {' . PHP_EOL;
        $phpContent .= '        return [' . PHP_EOL;
        $phpContent .= '            "menu_position" => 2,' . PHP_EOL;
        $phpContent .= '            "label" => __("' . addslashes($formData['model_label']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '            "labels" => [' . PHP_EOL;
        $phpContent .= '                "name" => __("' . addslashes($formData['model_label']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "singular_name" => __("' . addslashes($formData['model_singular_name']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "menu_name" => __("' . addslashes($formData['model_menu_name']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "all_items" => __("' . addslashes($formData['model_all_items']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "add_new" => __("' . addslashes($formData['model_add_new']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "add_new_item" => __("' . addslashes($formData['model_add_new_item']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "edit_item" => __("' . addslashes($formData['model_edit_item']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "new_item" => __("' . addslashes($formData['model_new_item']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "view_item" => __("' . addslashes($formData['model_view_item']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "view_items" => __("' . addslashes($formData['model_view_items']) . '", "toolkit"),' . PHP_EOL;
        $phpContent .= '                "search_items" => __("' . addslashes($formData['model_search_items']) . '", "toolkit")' . PHP_EOL;
        $phpContent .= '            ],' . PHP_EOL;
        $phpContent .= '            "description" => "",' . PHP_EOL;
        $phpContent .= '            "public" => true,' . PHP_EOL;
        $phpContent .= '            "publicly_queryable" => true,' . PHP_EOL;
        $phpContent .= '            "show_ui" => true,' . PHP_EOL;
        $phpContent .= '            "show_in_rest" => true,' . PHP_EOL;
        $phpContent .= '            "show_in_nav_menus" => true,' . PHP_EOL;
        $phpContent .= '            "rest_base" => "",' . PHP_EOL;
        $phpContent .= '            "has_archive" => true,' . PHP_EOL;
        $phpContent .= '            "show_in_menu" => true,' . PHP_EOL;
        $phpContent .= '            "exclude_from_search" => false,' . PHP_EOL;
        $phpContent .= '            "capability_type" => "post",' . PHP_EOL;
        $phpContent .= '            "map_meta_cap" => true,' . PHP_EOL;
        $phpContent .= '            "hierarchical" => false,' . PHP_EOL;
        $phpContent .= '            "rewrite" => ["slug" => self::SLUG, "with_front" => false],' . PHP_EOL;
        $phpContent .= '            "query_var" => true,' . PHP_EOL;
        $phpContent .= '            "menu_icon" => "dashicons-icon-' . $formData['model_icon'] . '",' . PHP_EOL;
        $phpContent .= '            "supports" => ' . var_export(explode(", ", $formData['model_supports']), true) . ',' . PHP_EOL;
        $phpContent .= '        ];' . PHP_EOL;
        $phpContent .= '    }' . PHP_EOL . PHP_EOL;
        // Add the jsonSerialize method
        $phpContent .= '    public function jsonSerialize(): mixed' . PHP_EOL;
        $phpContent .= '    {' . PHP_EOL;
        $phpContent .= '        return [' . PHP_EOL;
        $phpContent .= '            "id" => $this->id(),' . PHP_EOL;
        $phpContent .= '            "title" => $this->title(),' . PHP_EOL;
        $phpContent .= '            "slug" => $this->slug(),' . PHP_EOL;
        $phpContent .= '            "link" => $this->link(),' . PHP_EOL;
        $phpContent .= '            "excerpt" => $this->excerpt(),' . PHP_EOL;
        $phpContent .= '            "content" => $this->content(),' . PHP_EOL;
        $phpContent .= '            "date" => $this->date(),' . PHP_EOL;
        $phpContent .= '        ];' . PHP_EOL;
        $phpContent .= '    }' . PHP_EOL;
        $phpContent .= '}' . PHP_EOL;

        // Save PHP file
        if (file_put_contents($filename, $phpContent) === false) {
            wp_send_json_error(__('Unable to create custom post type file.', 'toolkit'), 500);
        }

        if (empty($formData['create_category'])) {
            wp_send_json_success(__('Custom post type created successfully.', 'toolkit'));
        }

        // Generate category file
        $categoryFilename = WP_TOOLKIT_THEME_PATH . '/models/custom/' . $className . 'Category.php';

        if (file_exists($categoryFilename)) {
            wp_send_json_error(__("Category already exists.", 'toolkit'), 409);
        }

        // Generate class PHP file content
        $phpCategoryContent = '<?php' . PHP_EOL . PHP_EOL;
        $phpCategoryContent .= 'namespace Toolkit\models\custom;' . PHP_EOL . PHP_EOL;
        $phpCategoryContent .= 'use Toolkit\models\Taxonomy;' . PHP_EOL;
        $phpCategoryContent .= 'use Toolkit\models\custom\\' . $className . ';' . PHP_EOL . PHP_EOL;
        $phpCategoryContent .= 'class ' . $className . 'Category extends Taxonomy' . PHP_EOL;
        $phpCategoryContent .= '{' . PHP_EOL;
        $phpCategoryContent .= '    const TYPE = \'' . strtolower($formData['model_name']) . '_category\';' . PHP_EOL;
        $phpCategoryContent .= '    public static function register()' . PHP_EOL;
        $phpCategoryContent .= '    {' . PHP_EOL;
        $phpCategoryContent .= '        register_taxonomy(self::TYPE, ' . $className . '::TYPE, ' . var_export(self::prepare_category(), true) . ');' . PHP_EOL;
        $phpCategoryContent .= '    }' . PHP_EOL;
        $phpCategoryContent .= '}' . PHP_EOL;

        // Save PHP file
        if (file_put_contents($categoryFilename, $phpCategoryContent) === false) {
            wp_send_json_error(__('Unable to create custom post type category file.', 'toolkit'), 500);
        }

        wp_send_json_success(__('Custom post type and category created successfully.', 'toolkit'));
    }

    /**
     * Generates custom block file.
     */
    public static function create_block_action()
    {
        check_ajax_referer('create_block_nonce', 'security');
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('Unauthorized request.', 'toolkit'), 403);
        }

        $formData = self::sanitize_block_form_data($_POST['formData'] ?? []);
        if (empty($formData['block_title'])) {
            wp_send_json_error(__('Invalid block payload.', 'toolkit'), 400);
        }
        // Title to CamelCase for class name
        $camelTitle = str_replace(' ', '', ucwords($formData['block_title']));
        // Title to slug
        $slugTitle = sanitize_title($formData['block_title']);

        $filename = WP_TOOLKIT_THEME_PATH . '/models/custom/Block' . $camelTitle . '.php';

        if (file_exists($filename)) {
            wp_send_json_error(__("Block already exists.", 'toolkit'), 409);
        }

        // Generate class PHP file content
        $phpContent = '<?php' . PHP_EOL . PHP_EOL;
        $phpContent .= 'namespace Toolkit\models\custom;' . PHP_EOL . PHP_EOL;
        $phpContent .= 'use Toolkit\models\Block;' . PHP_EOL . PHP_EOL;
        $phpContent .= 'class Block' . $camelTitle . ' extends Block' . PHP_EOL;
        $phpContent .= '{' . PHP_EOL;
        $phpContent .= '    const TYPE = \''. 'block-' . $slugTitle . '\';' . PHP_EOL . PHP_EOL;
        $phpContent .= '    public static function settings()' . PHP_EOL;
        $phpContent .= '    {' . PHP_EOL;
        $phpContent .= '        return ' . var_export(self::prepare_block_settings($formData), true) . ';' . PHP_EOL;
        $phpContent .= '    }' . PHP_EOL . PHP_EOL;
        $phpContent .= '}' . PHP_EOL;

        // Save PHP file
        if (file_put_contents($filename, $phpContent) === false) {
            wp_send_json_error(__('Unable to create block file.', 'toolkit'), 500);
        }

        // Create block template
        $blockTemplate = WP_TOOLKIT_THEME_PATH . '/partials/blocks/block-' . $slugTitle . '.php';
        if (!file_exists($blockTemplate)) {
            $blockTemplateContent = '<?php' . PHP_EOL . PHP_EOL;
            $blockTemplateContent .= 'echo "Block template";' . PHP_EOL;
            if (file_put_contents($blockTemplate, $blockTemplateContent) === false) {
                wp_send_json_error(__('Unable to create block template file.', 'toolkit'), 500);
            }
        }

        wp_send_json_success(__('Block created successfully.', 'toolkit'));
    }

    public static function prepare_category()
    {
        return [
            'hierarchical' => true,
            'show_admin_column' => true,
            'publicly_queryable' => false,
            'show_in_rest' => true,
            'labels' => [
                'name'              => __('Catégories', ''),
                'singular_name'     => __('Catégorie', ''),
                'search_items'      => __('Rechercher une catégorie', ''),
                'all_items'         => __('Tout les catégories', ''),
                'parent_item'       => __('Catégorie parente', ''),
                'parent_item_colon' => __('Catégorie parente:', ''),
                'edit_item'         => __('Éditer la catégorie', ''),
                'update_item'       => __('Modifier la catégorie', ''),
                'add_new_item'      => __('Ajouter une nouvelle catégorie', ''),
                'new_item_name'     => __('Nouvelle catégorie', ''),
                'menu_name'         => __('Catégories', ''),
            ]
        ];
    }

    public static function prepare_block_settings(array $formData)
    {
        return [
            'title' => __($formData['block_title'], 'toolkit'),
            'mode' => 'auto',
            'description' => __($formData['block_description'], 'toolkit'),
            'icon' => $formData['block_icon'],
            'keywords' => explode(", ", $formData['block_keywords']),
        ];
    }

    private static function sanitize_model_form_data($rawFormData)
    {
        $safe = [];
        if (!is_array($rawFormData)) {
            return $safe;
        }

        $unslashed = wp_unslash($rawFormData);
        $formData = array_column($unslashed, 'value', 'name');

        $safe['model_name'] = sanitize_key($formData['model_name'] ?? '');
        $safe['model_slug'] = sanitize_title($formData['model_slug'] ?? '');
        $safe['model_label'] = sanitize_text_field($formData['model_label'] ?? '');
        $safe['model_singular_name'] = sanitize_text_field($formData['model_singular_name'] ?? '');
        $safe['model_menu_name'] = sanitize_text_field($formData['model_menu_name'] ?? '');
        $safe['model_all_items'] = sanitize_text_field($formData['model_all_items'] ?? '');
        $safe['model_add_new'] = sanitize_text_field($formData['model_add_new'] ?? '');
        $safe['model_add_new_item'] = sanitize_text_field($formData['model_add_new_item'] ?? '');
        $safe['model_edit_item'] = sanitize_text_field($formData['model_edit_item'] ?? '');
        $safe['model_new_item'] = sanitize_text_field($formData['model_new_item'] ?? '');
        $safe['model_view_item'] = sanitize_text_field($formData['model_view_item'] ?? '');
        $safe['model_view_items'] = sanitize_text_field($formData['model_view_items'] ?? '');
        $safe['model_search_items'] = sanitize_text_field($formData['model_search_items'] ?? '');
        $safe['model_icon'] = sanitize_key($formData['model_icon'] ?? '');
        $safe['model_supports'] = sanitize_text_field($formData['model_supports'] ?? '');
        $safe['create_category'] = !empty($formData['create_category']) ? 1 : 0;

        if (!array_key_exists($safe['model_icon'], Icon::ICONS)) {
            $safe['model_icon'] = array_key_first(Icon::ICONS);
        }

        return $safe;
    }

    private static function sanitize_block_form_data($rawFormData)
    {
        $safe = [];
        if (!is_array($rawFormData)) {
            return $safe;
        }

        $unslashed = wp_unslash($rawFormData);
        $formData = array_column($unslashed, 'value', 'name');

        $safe['block_title'] = sanitize_text_field($formData['block_title'] ?? '');
        $safe['block_description'] = sanitize_text_field($formData['block_description'] ?? '');
        $safe['block_icon'] = sanitize_key($formData['block_icon'] ?? 'block-default');
        $safe['block_keywords'] = sanitize_text_field($formData['block_keywords'] ?? '');

        return $safe;
    }
}
