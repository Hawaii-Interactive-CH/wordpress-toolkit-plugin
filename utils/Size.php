<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

class Size
{
    /**
     * Properties.
     */
    private static $_instance = null;
    private $_image_sizes = [];
    private $_fly_dir = "";
    private $_capability = "manage_options";

    /**
     * Get current instance.
     *
     * @return object
     */
    public static function get_instance()
    {
        if (!self::$_instance) {
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }

    /**
     * Initialize plugin.
     */
    public function init()
    {
        $this->_fly_dir = apply_filters("fly_dir_path", $this->get_fly_dir());
        $this->_capability = apply_filters(
            "fly_images_user_capability",
            $this->_capability
        );

        $this->check_fly_dir();

        add_action('admin_menu', array($this, 'admin_menu_item'));
        add_filter('media_row_actions', array($this, 'media_row_action'), 10, 2);
        add_action('delete_attachment', array($this, 'delete_attachment_fly_images'));

        add_action('switch_blog', array($this, 'blog_switched'));
    }

    public static function add(
        $size_name = "",
        $width = 0,
        $height = 0,
        $crop = false
    ) {
        $fly_images = self::get_instance();
        return $fly_images->add_image_size($size_name, $width, $height, $crop);
    }

    public static function src($attachment_id = 0, $size = "", $crop = null)
    {
        $fly_images = self::get_instance();
        return $fly_images->get_attachment_image_src(
            $attachment_id,
            $size,
            $crop
        );
    }

    /**
     * Get the path to the directory where all Fly images are stored.
     *
     * @param  string $path
     * @return string
     */
    public function get_fly_dir($path = "")
    {
        if (empty($this->_fly_dir)) {
            $wp_upload_dir = wp_upload_dir();
            return $wp_upload_dir["basedir"] .
                DIRECTORY_SEPARATOR .
                "fly-images" .
                ("" !== $path ? DIRECTORY_SEPARATOR . $path : "");
        }

        return $this->_fly_dir .
            ("" !== $path ? DIRECTORY_SEPARATOR . $path : "");
    }

    /**
     * Create fly images directory if it doesn't already exist.
     */
    function check_fly_dir()
    {
        if (!is_dir($this->_fly_dir)) {
            wp_mkdir_p($this->_fly_dir);
        }
    }

    /**
     * Check if the Fly images folder exists and is writeable.
     *
     * @return boolean
     */
    public function fly_dir_writable()
    {
        return is_dir($this->_fly_dir) && wp_is_writable($this->_fly_dir);
    }

    /**
     * Add admin menu item.
     */
    public function admin_menu_item()
    {
        add_management_page(
            __('Fly Images', 'fly-images'),
            __('Fly Images', 'fly-images'),
            $this->_capability,
            'fly-images',
            array($this, 'options_page')
        );
    }

    /**
     * Add a new row action to media library items.
     *
     * @param  array $actions
     * @param  object $post
     * @return array
     */
    public function media_row_action($actions, $post)
    {
        if (
            "image/" !== substr($post->post_mime_type, 0, 6) ||
            !current_user_can($this->_capability)
        ) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url(
                "tools.php?page=fly-images&delete-fly-image&ids=" . $post->ID
            ),
            "delete_fly_image",
            "fly_nonce"
        );
        $actions["fly-image-delete"] =
            '<a href="' .
            esc_url($url) .
            '" title="' .
            esc_attr(
                __("Delete all cached image sizes for this image", "fly-images")
            ) .
            '">' .
            __("Delete Fly Images", "fly-images") .
            "</a>";

        return $actions;
    }

    /**
     * Delete all fly images for an attachment.
     *
     * @param  integer $attachment_id
     * @return boolean
     */
    public function delete_attachment_fly_images($attachment_id = 0)
    {
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem->rmdir($this->get_fly_dir($attachment_id), true);
    }

    /**
     * Delete all the fly images.
     *
     * @return boolean
     */
    public function delete_all_fly_images()
    {
        WP_Filesystem();
        global $wp_filesystem;

        if ($wp_filesystem->rmdir($this->get_fly_dir(), true)) {
            $this->check_fly_dir();
            return true;
        }

        return false;
    }

    /**
     * Options page.
     */
    public function options_page()
    {
        // Check for actions
        if (
            isset($_POST["fly_nonce"]) && // Input var okay.
            wp_verify_nonce(
                sanitize_key($_POST["fly_nonce"]),
                "delete_all_fly_images"
            ) // Input var okay.
        ) {
            // Delete all fly images.
            $this->delete_all_fly_images();
            echo '<div class="updated"><p>' .
                esc_html__(
                    "All cached images created on the fly have been deleted.",
                    "fly-images"
                ) .
                "</p></div>";
        } elseif (
            isset(
                $_GET["delete-fly-image"],
                $_GET["ids"],
                $_GET["fly_nonce"]
            ) && // Input var okay.
            wp_verify_nonce(
                sanitize_key($_GET["fly_nonce"]),
                "delete_fly_image"
            ) // Input var okay.
        ) {
            // Delete all fly images for certain attachments.
            $ids = array_map(
                "intval",
                array_map("trim", explode(",", sanitize_key($_GET["ids"])))
            ); // Input var okay.
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $this->delete_attachment_fly_images($id);
                }
                echo '<div class="updated"><p>' .
                    esc_html__(
                        "Deleted all fly images for this image.",
                        "fly-images"
                    ) .
                    "</p></div>";
            }
        }

        // Show the template
        load_template(JB_FLY_PLUGIN_PATH . "/admin/options.php");
    }

    /**
     * Add image sizes to be created on the fly.
     *
     * @param  string   $size_name
     * @param  integer  $width
     * @param  integer  $height
     * @param  boolean  $crop
     * @return boolean
     */
    public function add_image_size(
        $size_name,
        $width = 0,
        $height = 0,
        $crop = false
    ) {
        if (empty($size_name) || !$width || !$height) {
            return false;
        }

        $this->_image_sizes[$size_name] = [
            "size" => [$width, $height],
            "crop" => $crop,
        ];

        return true;
    }

    /**
     * Gets a previously declared image size.
     *
     * @param  string $size_name
     * @return array
     */
    public function get_image_size($size_name = "")
    {
        if (empty($size_name) || !isset($this->_image_sizes[$size_name])) {
            return [];
        }

        return $this->_image_sizes[$size_name];
    }

    public function get_image_extension($file_name = "")
    {
        return pathinfo($file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get all declared images sizes.
     *
     * @return array
     */
    public function get_all_image_sizes()
    {
        return $this->_image_sizes;
    }

    /**
     * Gets a dynamically generated image URL from the Fly_Images class.
     *
     * @param  integer  $attachment_id
     * @param  mixed    $size
     * @param  boolean  $crop
     * @return array
     */
    public function get_attachment_image_src(
        $attachment_id = 0,
        $size = "",
        $crop = null
    ) {
        if ($attachment_id < 1 || empty($size)) {
            return [];
        }
    
        // If size is 'full', we don't need a fly image
        if ("full" === $size) {
            return wp_get_attachment_image_src($attachment_id, "full");
        }

        // Check if image extension is supported
        $non_supported_extensions = ["svg", "avif", "heic", "heif"];
        $image_extension = $this->get_image_extension(
            get_attached_file($attachment_id)
        );

        // if not supported, return the original image
        if (in_array($image_extension, $non_supported_extensions)) {
            return wp_get_attachment_image_src($attachment_id, "full");
        }

    
        // Get the attachment image
        $image = wp_get_attachment_metadata($attachment_id);
    
        if (false !== $image && $image) {
            // Determine width and height based on size
            switch (gettype($size)) {
                case "string":
                    $image_size = $this->get_image_size($size);
                    if (empty($image_size)) {
                        return [];
                    }
                    $width = $image_size["size"][0];
                    $height = $image_size["size"][1];
                    $crop = isset($crop) ? $crop : $image_size["crop"];
                    break;
                case "array":
                    $width = $size[0];
                    $height = $size[1];
                    break;
                default:
                    return [];
            }
    
            // Get file paths
            $fly_dir = $this->get_fly_dir($attachment_id);

            $fly_file_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(
                basename($image["file"]),
                $width,
                $height,
                $crop
            );
            $fly_webp_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(
                basename($image["file"]),
                $width,
                $height,
                $crop,
                true // Check for .webp
            );
    
            // Check if WebP file exists
            if (file_exists($fly_webp_path)) {
                $image_size = getimagesize($fly_webp_path);
                if (!empty($image_size)) {
                    return [
                        "src" => $this->get_fly_path($fly_webp_path),
                        "width" => $image_size[0],
                        "height" => $image_size[1],
                    ];
                }
            }
    
            // Check if original file exists
            if (file_exists($fly_file_path)) {
                $image_size = getimagesize($fly_file_path);
                if (!empty($image_size)) {
                    return [
                        "src" => $this->get_fly_path($fly_file_path),
                        "width" => $image_size[0],
                        "height" => $image_size[1],
                    ];
                }
            }
    
            // Check if images directory is writable
            if (!$this->fly_dir_writable()) {
                return [];
            }
    
            // File does not exist, let's check if directory exists
            $this->check_fly_dir();
    
            // Get WP Image Editor Instance
            $image_path = get_attached_file($attachment_id);
            $image_editor = wp_get_image_editor($image_path);
            if (!is_wp_error($image_editor)) {
                // Create new image
                $image_editor->resize($width, $height, $crop);
                $image_editor->save($fly_file_path);
    
                // Check if WebP format is supported
                if (function_exists('imagewebp') && $image_editor->supports_mime_type('image/webp')) {
                    $image_editor->save($fly_webp_path, 'image/webp', ['quality' => 75, 'strip_metadata' => true]);
                }
    
                // Trigger action
                do_action("fly_image_created", $attachment_id, $fly_file_path);
    
                // Return WebP if it exists, otherwise return original
                if (file_exists($fly_webp_path)) {
                    $image_dimensions = $image_editor->get_size();
                    return [
                        "src" => $this->get_fly_path($fly_webp_path),
                        "width" => $image_dimensions["width"],
                        "height" => $image_dimensions["height"],
                    ];
                } else {
                    $image_dimensions = $image_editor->get_size();
                    return [
                        "src" => $this->get_fly_path($fly_file_path),
                        "width" => $image_dimensions["width"],
                        "height" => $image_dimensions["height"],
                    ];
                }
            }
        }
    
        // Something went wrong
        return [];
    }

    /**
     * Get a dynamically generated image HTML from the Fly_Images class.
     *
     * Based on /wp-includes/media.php -> wp_get_attachment_image()
     *
     * @param  integer  $attachment_id
     * @param  mixed    $size
     * @param  boolean  $crop
     * @param  array    $attr
     * @return string
     */
    public function get_attachment_image(
        $attachment_id = 0,
        $size = "",
        $crop = null,
        $attr = []
    ) {
        if ($attachment_id < 1 || empty($size)) {
            return "";
        }

        // If size is 'full', we don't need a fly image
        if ("full" === $size) {
            return wp_get_attachment_image($attachment_id, $size, $attr);
        }

        $html = "";
        $image = $this->get_attachment_image_src($attachment_id, $size, $crop);
        if ($image) {
            $hwstring = image_hwstring($image["width"], $image["height"]);
            $size_class = $size;
            if (is_array($size_class)) {
                $size_class = join("x", $size);
            }
            $attachment = get_post($attachment_id);
            $default_attr = [
                "src" => $image["src"],
                "class" => "attachment-$size_class",
                "alt" => trim(
                    strip_tags(
                        get_post_meta(
                            $attachment_id,
                            "_wp_attachment_image_alt",
                            true
                        )
                    )
                ),
            ];
            if (empty($default_attr["alt"])) {
                $default_attr["alt"] = trim(
                    strip_tags($attachment->post_excerpt)
                );
            }
            if (empty($default_attr["alt"])) {
                $default_attr["alt"] = trim(
                    strip_tags($attachment->post_title)
                );
            }

            $attr = wp_parse_args($attr, $default_attr);
            $attr = apply_filters(
                "fly_get_attachment_image_attributes",
                $attr,
                $attachment,
                $size
            );
            $attr = array_map("esc_attr", $attr);
            $html = rtrim("<img $hwstring");
            foreach ($attr as $name => $value) {
                $html .= " $name=" . '"' . $value . '"';
            }
            $html .= " />";
        }

        return $html;
    }

    /**
     * Get a file name based on parameters.
     *
     * @param  string  $file_name
     * @param  string  $width
     * @param  string  $height
     * @param  boolean $crop
     * @return string
     */
    public function get_fly_file_name($file_name, $width, $height, $crop, $webp = false)
    {
        $file_name_only = pathinfo($file_name, PATHINFO_FILENAME);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        $crop_extension = "";
        if (true === $crop) {
            $crop_extension = "-c";
        } elseif (is_array($crop)) {
            $crop_extension =
                "-" .
                implode(
                    "",
                    array_map(function ($position) {
                        return $position[0];
                    }, $crop)
                );
        }

        // If WebP is requested, change the extension to .webp
        if ($webp) {
            $file_extension = "webp";
        }

        // Construct the final file name without appending the original extension twice
        return $file_name_only .
            "-" .
            intval($width) .
            "x" .
            intval($height) .
            $crop_extension .
            "." .
            $file_extension;
    }

    /**
     * Get the full path of an image based on it's absolute path.
     *
     * @param  string $absolute_path
     * @return string
     */
    public function get_fly_path($absolute_path = "")
    {
        $wp_upload_dir = wp_upload_dir();
        $path =
            $wp_upload_dir["baseurl"] .
            str_replace($wp_upload_dir["basedir"], "", $absolute_path);
        return str_replace(DIRECTORY_SEPARATOR, "/", $path);
    }

    /**
     * Get the absolute path of an image based on it's full path.
     *
     * @param  string $path
     * @return string
     */
    public function get_fly_absolute_path($path = "")
    {
        $wp_upload_dir = wp_upload_dir();
        return $wp_upload_dir["basedir"] .
            str_replace($wp_upload_dir["baseurl"], "", $path);
    }

    /**
     * Update Fly Dir when a blog is switched.
     *
     * @return void
     */
    public function blog_switched()
    {
        $this->_fly_dir = "";
        $this->_fly_dir = apply_filters("fly_dir_path", $this->get_fly_dir());
    }
}
