<?php

namespace Toolkit\utils;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

class Upscale
{
    public static function resize_crop($orig_w, $orig_h, $new_w, $new_h)
    {
        $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

        $crop_w = round($new_w / $size_ratio);
        $crop_h = round($new_h / $size_ratio);

        $s_x = floor(($orig_w - $crop_w) / 2);
        $s_y = floor(($orig_h - $crop_h) / 2);

        return [
            0,
            0,
            (int) $s_x,
            (int) $s_y,
            (int) $new_w,
            (int) $new_h,
            (int) $crop_w,
            (int) $crop_h,
        ];
    }

    public static function resize_keep_ratio($orig_w, $orig_h, $new_w, $new_h)
    {
        // Allow upscaling for retina displays
        // Calculate ratio - if requested size is larger than original, ratio will be > 1
        $width_ratio = $new_w / $orig_w;
        $height_ratio = $new_h / $orig_h;
        
        // Use the smaller ratio to maintain aspect ratio
        // This may result in upscaling if both ratios are > 1
        $size_ratio = min($width_ratio, $height_ratio);

        if ($size_ratio === 0) {
            return null;
        }

        // Calculate new dimensions (may be larger than original for retina)
        $new_w = round($orig_w * $size_ratio);
        $new_h = round($orig_h * $size_ratio);

        return [
            0,
            0,
            0,
            0,
            (int) $new_w,
            (int) $new_h,
            (int) $orig_w,
            (int) $orig_h,
        ];
    }

    public static function resize(
        $default,
        $orig_w,
        $orig_h,
        $new_w,
        $new_h,
        $crop
    ) {
        if ($crop) {
            return self::resize_crop($orig_w, $orig_h, $new_w, $new_h);
        }

        return self::resize_keep_ratio($orig_w, $orig_h, $new_w, $new_h);
    }
}
