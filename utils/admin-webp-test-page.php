<?php
/**
 * WordPress Admin Test Page for WebP Optimization
 * 
 * Add this to your plugin's main file to create an admin page for testing
 */

namespace Toolkit\utils;

// Prevent direct access
defined('ABSPATH') or exit;

class WebPTestPage
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    public static function add_admin_menu()
    {
        add_management_page(
            'WebP Optimization Test',
            'WebP Test',
            'manage_options',
            'webp-optimization-test',
            [__CLASS__, 'render_test_page']
        );
    }

    public static function render_test_page()
    {
        // Handle rebuild action
        $rebuild_message = '';
        if (isset($_POST['rebuild_webp']) && check_admin_referer('rebuild_webp_action', 'rebuild_webp_nonce')) {
            $size_instance = Size::get_instance();
            $force_rebuild = isset($_POST['force_rebuild']) && $_POST['force_rebuild'] === '1';
            
            $queued_count = $size_instance->rebuild_all_webp_images($force_rebuild);
            
            if ($queued_count > 0) {
                $rebuild_message = '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> ' . $queued_count . ' images queued for WebP regeneration. Processing will happen in the background.</p></div>';
            } else {
                $rebuild_message = '<div class="notice notice-warning"><p><strong>Notice:</strong> No images found to queue.</p></div>';
            }
        }
        
        // Handle clear queue action
        if (isset($_POST['clear_queue']) && check_admin_referer('clear_queue_action', 'clear_queue_nonce')) {
            $size_instance = Size::get_instance();
            $size_instance->clear_queue();
            $rebuild_message = '<div class="notice notice-info is-dismissible"><p><strong>Queue cleared.</strong> All pending WebP generation tasks have been removed.</p></div>';
        }
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                .webp-test-wrap {
                    margin: 20px 20px 0 0;
                }
                .webp-test-wrap h1 {
                    color: #1d2327;
                    border-bottom: 3px solid #2271b1;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .test-section {
                    background: white;
                    padding: 20px;
                    margin: 20px 0;
                    border: 1px solid #c3c4c7;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                .stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                }
                .stat-box {
                    background: #f6f7f7;
                    padding: 15px;
                    border-radius: 4px;
                    border-left: 4px solid #2271b1;
                }
                .stat-label {
                    font-size: 11px;
                    color: #646970;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                    font-weight: 600;
                }
                .stat-value {
                    font-size: 32px;
                    font-weight: 600;
                    color: #1d2327;
                    line-height: 1.2;
                }
                .stat-box.success {
                    border-left-color: #00a32a;
                }
                .stat-box.warning {
                    border-left-color: #dba617;
                }
                .stat-box.error {
                    border-left-color: #d63638;
                }
                .widefat th {
                    font-weight: 600;
                }
                .size-comparison {
                    color: #00a32a;
                    font-weight: 600;
                }
                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .badge-webp {
                    background: #d5e8d4;
                    color: #2d7a2d;
                }
                .badge-png {
                    background: #fff3cd;
                    color: #856404;
                }
                .quality-indicator {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: 600;
                    background: #e7f5ff;
                    color: #0c5aa6;
                }
                .rebuild-controls {
                    background: #f0f6fc;
                    border: 2px solid #0969da;
                    padding: 20px;
                    border-radius: 6px;
                    margin: 20px 0;
                }
                .rebuild-controls h3 {
                    margin-top: 0;
                    color: #0969da;
                }
                .button-group {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    margin-top: 15px;
                }
                .checkbox-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="wrap webp-test-wrap">
                <h1>🎨 WebP Optimization Test Results</h1>

                <?php 
                echo $rebuild_message;
                
                // Get queue status
                $size_instance = Size::get_instance();
                $queue_status = $size_instance->get_queue_status();
                ?>

                <div class="rebuild-controls">
                    <h3>🔄 Rebuild WebP Images</h3>
                    <p>Use this tool to regenerate all WebP images in the background. The process will queue all image attachments and process them via cron.</p>
                    
                    <?php if ($queue_status['count'] > 0): ?>
                        <div class="notice notice-info inline" style="margin: 10px 0; padding: 10px;">
                            <p><strong>⏳ Queue Status:</strong> <?php echo $queue_status['count']; ?> images waiting to be processed.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" style="display: inline-block;">
                        <?php wp_nonce_field('rebuild_webp_action', 'rebuild_webp_nonce'); ?>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="force_rebuild" name="force_rebuild" value="1">
                            <label for="force_rebuild">
                                <strong>Force rebuild</strong> (deletes existing WebP files before regenerating)
                            </label>
                        </div>
                        <div class="button-group">
                            <button type="submit" name="rebuild_webp" class="button button-primary">
                                🚀 Queue All Images for Rebuild
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($queue_status['count'] > 0): ?>
                        <form method="post" style="display: inline-block; margin-left: 10px;">
                            <?php wp_nonce_field('clear_queue_action', 'clear_queue_nonce'); ?>
                            <button type="submit" name="clear_queue" class="button" onclick="return confirm('Are you sure you want to clear the queue? This will not delete existing WebP files.');">
                                🗑️ Clear Queue
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <p style="margin-top: 15px; font-size: 12px; color: #646970;">
                        <strong>Note:</strong> Images are processed in batches every minute via WordPress cron. Large libraries may take some time to complete.
                    </p>
                </div>

                <?php
                // Get recent images
                $images = get_posts([
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'posts_per_page' => 20,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);

                if (empty($images)) {
                    echo '<div class="notice notice-warning"><p><strong>No images found.</strong> Please upload some images first.</p></div>';
                    echo '<p><a href="' . admin_url('upload.php') . '" class="button button-primary">Go to Media Library</a></p>';
                    return;
                }

                // Initialize Size class
                $size_instance = Size::get_instance();
                
                // Get fly directory
                $fly_base_dir = $size_instance->get_fly_dir();
                
                $total_original_size = 0;
                $total_webp_size = 0;
                $total_png_size = 0;
                $webp_count = 0;
                $png_count = 0;
                $results = [];

                foreach ($images as $image) {
                    $attachment_id = $image->ID;
                    $original_path = get_attached_file($attachment_id);
                    
                    if (!file_exists($original_path)) continue;
                    
                    $extension = strtolower(pathinfo($original_path, PATHINFO_EXTENSION));
                    if (in_array($extension, ['svg', 'avif', 'heic', 'heif'])) continue;
                    
                    $original_size = filesize($original_path);
                    $total_original_size += $original_size;
                    
                    // Check fly directory for this attachment
                    $fly_dir = $fly_base_dir . DIRECTORY_SEPARATOR . $attachment_id;
                    
                    if (!is_dir($fly_dir)) continue;
                    
                    $files = glob($fly_dir . DIRECTORY_SEPARATOR . '*');
                    
                    foreach ($files as $file) {
                        $file_size = filesize($file);
                        $file_name = basename($file);
                        $file_ext = pathinfo($file, PATHINFO_EXTENSION);
                        $is_webp = $file_ext === 'webp';
                        
                        if ($is_webp) {
                            $total_webp_size += $file_size;
                            $webp_count++;
                        } else {
                            $total_png_size += $file_size;
                            $png_count++;
                        }
                        
                        // Extract dimensions from filename (format: name-WIDTHxHEIGHT.ext)
                        preg_match('/(\d+)x(\d+)/', $file_name, $matches);
                        $width = isset($matches[1]) ? intval($matches[1]) : 0;
                        $height = isset($matches[2]) ? intval($matches[2]) : 0;
                        
                        // Determine quality based on width (same logic as code)
                        $expected_quality = 75;
                        if ($width >= 1920) {
                            $expected_quality = 60;
                        } elseif ($width >= 1280) {
                            $expected_quality = 65;
                        } elseif ($width >= 640) {
                            $expected_quality = 70;
                        }
                        
                        $savings = $original_size - $file_size;
                        $savings_percent = $original_size > 0 ? ($savings / $original_size) * 100 : 0;
                        
                        $results[] = [
                            'attachment_id' => $attachment_id,
                            'filename' => $file_name,
                            'type' => $is_webp ? 'WebP' : strtoupper($file_ext),
                            'dimensions' => $width . 'x' . $height,
                            'width' => $width,
                            'size' => $file_size,
                            'expected_quality' => $expected_quality,
                            'savings' => $savings,
                            'savings_percent' => $savings_percent,
                            'is_webp' => $is_webp
                        ];
                    }
                }

                // Calculate overall stats
                $total_savings = $total_original_size - $total_webp_size;
                $savings_percent = $total_original_size > 0 ? ($total_savings / $total_original_size) * 100 : 0;
                
                $avg_webp_size = $webp_count > 0 ? $total_webp_size / $webp_count : 0;
                $avg_png_size = $png_count > 0 ? $total_png_size / $png_count : 0;
                ?>

                <div class="test-section">
                    <h2>📊 Overall Statistics</h2>
                    <div class="stats">
                        <div class="stat-box success">
                            <div class="stat-label">WebP Files Created</div>
                            <div class="stat-value"><?php echo number_format($webp_count); ?></div>
                        </div>
                        <div class="stat-box <?php echo $png_count > 0 ? 'warning' : ''; ?>">
                            <div class="stat-label">PNG/JPG Fallbacks</div>
                            <div class="stat-value"><?php echo number_format($png_count); ?></div>
                        </div>
                        <div class="stat-box <?php echo $queue_status['count'] > 0 ? 'warning' : 'success'; ?>">
                            <div class="stat-label">Queue Status</div>
                            <div class="stat-value"><?php echo number_format($queue_status['count']); ?></div>
                        </div>
                        <div class="stat-box <?php echo $savings_percent > 30 ? 'success' : ($savings_percent > 20 ? 'warning' : 'error'); ?>">
                            <div class="stat-label">Space Saved</div>
                            <div class="stat-value"><?php echo number_format($savings_percent, 1); ?>%</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Total Saved</div>
                            <div class="stat-value"><?php echo size_format($total_savings); ?></div>
                        </div>
                    </div>

                    <?php if ($avg_webp_size > 0 && $avg_png_size > 0): ?>
                    <p>
                        <strong>Average file size:</strong><br>
                        WebP: <?php echo size_format($avg_webp_size); ?> | 
                        PNG/JPG: <?php echo size_format($avg_png_size); ?> |
                        <span style="color: #00a32a; font-weight: 600;">
                            Difference: <?php echo number_format((1 - $avg_webp_size / $avg_png_size) * 100, 1); ?>% smaller
                        </span>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="test-section">
                    <h2>🔍 WebP Support Check</h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong>imagewebp function</strong></td>
                                <td><?php echo function_exists('imagewebp') ? '✅ Available' : '❌ Not available'; ?></td>
                            </tr>
                            <?php if (function_exists('gd_info')): 
                                $gd_info = gd_info();
                            ?>
                            <tr>
                                <td><strong>GD Version</strong></td>
                                <td><?php echo $gd_info['GD Version']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>WebP Support</strong></td>
                                <td><?php echo (isset($gd_info['WebP Support']) && $gd_info['WebP Support']) ? '✅ Yes' : '❌ No'; ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Fly Images Directory</strong></td>
                                <td><code><?php echo esc_html($fly_base_dir); ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="test-section">
                    <h2>📝 Adaptive Quality Settings</h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Image Width</th>
                                <th>WebP Quality</th>
                                <th>Reason</th>
                                <th>Files Using This Quality</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $quality_counts = [
                                60 => 0,
                                65 => 0,
                                70 => 0,
                                75 => 0
                            ];
                            
                            foreach ($results as $result) {
                                if ($result['is_webp'] && isset($quality_counts[$result['expected_quality']])) {
                                    $quality_counts[$result['expected_quality']]++;
                                }
                            }
                            ?>
                            <tr>
                                <td>≥ 1920px</td>
                                <td><span class="quality-indicator">60%</span></td>
                                <td>Very large images tolerate higher compression</td>
                                <td><strong><?php echo $quality_counts[60]; ?> files</strong></td>
                            </tr>
                            <tr>
                                <td>≥ 1280px</td>
                                <td><span class="quality-indicator">65%</span></td>
                                <td>Large images, good balance</td>
                                <td><strong><?php echo $quality_counts[65]; ?> files</strong></td>
                            </tr>
                            <tr>
                                <td>≥ 640px</td>
                                <td><span class="quality-indicator">70%</span></td>
                                <td>Medium images</td>
                                <td><strong><?php echo $quality_counts[70]; ?> files</strong></td>
                            </tr>
                            <tr>
                                <td>&lt; 640px</td>
                                <td><span class="quality-indicator">75%</span></td>
                                <td>Small images need better quality</td>
                                <td><strong><?php echo $quality_counts[75]; ?> files</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="test-section">
                    <h2>📁 Generated Files Detail</h2>
                    <?php if (empty($results)): ?>
                        <div class="notice notice-info">
                            <p><strong>No fly images found.</strong> Try uploading a new image or wait for the queue to process.</p>
                        </div>
                        <p><a href="<?php echo admin_url('upload.php'); ?>" class="button button-primary">Upload New Image</a></p>
                    <?php else: ?>
                        <p>Showing <?php echo count($results); ?> generated files from <?php echo count($images); ?> recent uploads.</p>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Dimensions</th>
                                    <th>Quality</th>
                                    <th>File Size</th>
                                    <th>Savings vs Original</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><code><?php echo esc_html($result['filename']); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo $result['is_webp'] ? 'badge-webp' : 'badge-png'; ?>">
                                                <?php echo $result['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $result['dimensions']; ?></td>
                                        <td><span class="quality-indicator"><?php echo $result['expected_quality']; ?>%</span></td>
                                        <td><?php echo size_format($result['size']); ?></td>
                                        <td class="size-comparison">
                                            ↓ <?php echo number_format($result['savings_percent'], 1); ?>% 
                                            (<?php echo size_format($result['savings']); ?>)
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="test-section">
                    <h2>🚀 Next Steps</h2>
                    <ul>
                        <li>✅ <strong>Upload a new image</strong> to test the optimization in real-time</li>
                        <li>✅ <strong>Check file sizes</strong> - WebP should be 20-35% smaller than originals</li>
                        <li>✅ <strong>Verify no PNG/JPG fallbacks</strong> are created (unless WebP is unsupported)</li>
                        <li>✅ <strong>Monitor cron job</strong> <code>fly_images_process_queue</code> for queue processing</li>
                    </ul>
                    <p>
                        <a href="<?php echo admin_url('upload.php'); ?>" class="button button-primary">Upload New Image</a>
                        <a href="<?php echo admin_url('tools.php'); ?>" class="button">View Tools</a>
                        <a href="<?php echo admin_url('admin.php?page=webp-optimization-test'); ?>" class="button">Refresh Test</a>
                    </p>
                </div>

            </div>
        </body>
        </html>
        <?php
    }
}

// Initialize the admin page
WebPTestPage::init();