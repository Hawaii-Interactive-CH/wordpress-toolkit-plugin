<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

class Size
{
    private static $_instance = null;
    private $_image_sizes = [];
    private $_fly_dir = "";

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

    public function init()
    {
        $this->_fly_dir = $this->get_fly_dir();
        $this->check_fly_dir();

        add_action('delete_attachment', array($this, 'delete_attachment_fly_images'));
        add_action('wp_generate_attachment_metadata', array($this, 'queue_image_for_processing'), 10, 2);
        add_action('fly_images_process_queue', array($this, 'process_image_queue'));
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        
        if (!wp_next_scheduled('fly_images_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'fly_images_process_queue');
        }
    }

    public function add_cron_interval($schedules)
    {
        $schedules['every_minute'] = array('interval' => 60, 'display' => 'Every Minute');
        return $schedules;
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('fly_images_process_queue');
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

    public function delete_attachment_fly_images($attachment_id = 0)
    {
        $fly_dir = $this->get_fly_dir($attachment_id);
        if (is_dir($fly_dir)) {
            array_map('unlink', glob($fly_dir . DIRECTORY_SEPARATOR . '*'));
            @rmdir($fly_dir);
        }
    }

    public function add_image_size($size_name, $width = 0, $height = 0, $crop = false)
    {
        if (empty($size_name) || !$width || !$height) return false;

        $this->_image_sizes[$size_name] = ["size" => [$width, $height], "crop" => $crop];
        return true;
    }

    public function get_image_size($size_name = "")
    {
        return isset($this->_image_sizes[$size_name]) ? $this->_image_sizes[$size_name] : [];
    }

    public function queue_image_for_processing($metadata, $attachment_id)
    {
        if (empty($metadata) || empty($attachment_id)) {
            return $metadata;
        }

        $extension = strtolower(pathinfo($metadata['file'], PATHINFO_EXTENSION));
        if (in_array($extension, ["svg", "avif", "heic", "heif"])) {
            return $metadata;
        }

        $queue = get_option('fly_images_queue', []);
        if (!in_array($attachment_id, $queue)) {
            $queue[] = $attachment_id;
            update_option('fly_images_queue', $queue, false);
        }

        return $metadata;
    }

    public function process_image_queue()
    {
        $queue = get_option('fly_images_queue', []);
        if (empty($queue)) return;

        $start_time = time();
        $processed = [];
        
        foreach ($queue as $attachment_id) {
            if ((time() - $start_time) >= 30) break;
            $this->generate_fly_images_for_attachment($attachment_id);
            $processed[] = $attachment_id;
        }

        $queue = array_diff($queue, $processed);
        update_option('fly_images_queue', array_values($queue), false);
    }

    private function generate_fly_images_for_attachment($attachment_id)
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (empty($metadata)) return;

        $image_path = get_attached_file($attachment_id);
        if (!file_exists($image_path)) return;

        $extension = pathinfo($image_path, PATHINFO_EXTENSION);
        if (in_array($extension, ["svg", "avif", "heic", "heif"])) return;

        $fly_dir = $this->get_fly_dir($attachment_id);
        if (!is_dir($fly_dir)) wp_mkdir_p($fly_dir);

        foreach ($this->_image_sizes as $size_data) {
            $width = $size_data['size'][0];
            $height = $size_data['size'][1];
            $crop = $size_data['crop'];

            if ($width > 2560) {
                $width = 2560;
                $height = intval($height * (2560 / $size_data['size'][0]));
            }

            $fly_file_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(basename($metadata["file"]), $width, $height, $crop);
            $fly_webp_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(basename($metadata["file"]), $width, $height, $crop, true);

            if (file_exists($fly_webp_path)) continue;

            $editor = wp_get_image_editor($image_path);
            if (is_wp_error($editor)) continue;

            $editor->set_quality(70);
            if (is_wp_error($editor->resize($width, $height, $crop))) continue;

            // Essai WebP d'abord
            if (function_exists('imagewebp') && $editor->supports_mime_type('image/webp')) {
                // Créer PNG temporaire pour fallback
                $editor->save($fly_file_path);
                // Créer WebP
                if (!is_wp_error($editor->save($fly_webp_path, 'image/webp', ['quality' => 75]))) {
                    // Supprimer le PNG une fois le WebP créé
                    if (file_exists($fly_file_path)) {
                        @unlink($fly_file_path);
                    }
                }
            } else {
                // Fallback PNG si WebP non supporté
                $editor->save($fly_file_path);
            }
        }
    }

    public function get_attachment_image_src($attachment_id = 0, $size = "", $crop = null)
    {
        if ($attachment_id < 1 || empty($size) || "full" === $size) {
            return wp_get_attachment_image_src($attachment_id, "full");
        }

        $extension = pathinfo(get_attached_file($attachment_id), PATHINFO_EXTENSION);
        if (in_array($extension, ["svg", "avif", "heic", "heif"])) {
            return wp_get_attachment_image_src($attachment_id, "full");
        }

        $image = wp_get_attachment_metadata($attachment_id);
        if (empty($image)) return [];

        // Determine width and height
        if (is_string($size)) {
            $image_size = $this->get_image_size($size);
            if (empty($image_size)) return [];
            $width = $image_size["size"][0];
            $height = $image_size["size"][1];
            $crop = isset($crop) ? $crop : $image_size["crop"];
        } elseif (is_array($size)) {
            $width = $size[0];
            $height = $size[1];
        } else {
            return [];
        }

        $fly_dir = $this->get_fly_dir($attachment_id);
        $fly_webp_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(basename($image["file"]), $width, $height, $crop, true);
        $fly_file_path = $fly_dir . DIRECTORY_SEPARATOR . $this->get_fly_file_name(basename($image["file"]), $width, $height, $crop);

        // Priority: WebP
        if (file_exists($fly_webp_path)) {
            return ["src" => $this->get_fly_path($fly_webp_path), "width" => $width, "height" => $height];
        }

        // Fallback: PNG/JPG
        if (file_exists($fly_file_path)) {
            return ["src" => $this->get_fly_path($fly_file_path), "width" => $width, "height" => $height];
        }

        // Not generated yet: return original
        $original_src = wp_get_attachment_image_src($attachment_id, "full");
        if ($original_src) {
            return ["src" => $original_src[0], "width" => $original_src[1], "height" => $original_src[2]];
        }

        return [];
    }

    public function get_fly_file_name($file_name, $width, $height, $crop, $webp = false)
    {
        $file_name_only = pathinfo($file_name, PATHINFO_FILENAME);
        $file_extension = $webp ? "webp" : pathinfo($file_name, PATHINFO_EXTENSION);

        $crop_extension = "";
        if (true === $crop) {
            $crop_extension = "-c";
        } elseif (is_array($crop)) {
            $crop_extension = "-" . implode("", array_map(function ($position) {
                return $position[0];
            }, $crop));
        }

        return $file_name_only . "-" . intval($width) . "x" . intval($height) . $crop_extension . "." . $file_extension;
    }

    public function get_fly_path($absolute_path = "")
    {
        $wp_upload_dir = wp_upload_dir();
        return str_replace(DIRECTORY_SEPARATOR, "/", $wp_upload_dir["baseurl"] . str_replace($wp_upload_dir["basedir"], "", $absolute_path));
    }
}
