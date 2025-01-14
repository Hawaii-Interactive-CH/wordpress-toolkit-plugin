<?php

namespace Toolkit\utils;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Toolkit\utils\parsedown\ParsedownToc;

class DocService {

    public static function register() {
        add_action('admin_menu', [self::class, 'add_plugin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function enqueue_scripts() {
        wp_enqueue_style('highlightjs-default-style', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css');

        // Enqueue the Highlight.js script
        wp_enqueue_script('highlightjs', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js', array(), null, true);

        // Optionally, enqueue additional language support
        wp_enqueue_script('highlightjs-lang-go', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/go.min.js', array('highlightjs'), null, true);

        // Enqueue a script to initialize Highlight.js on the page
        wp_add_inline_script('highlightjs', 'hljs.highlightAll();');

    }

    public static function add_plugin_menu() {
        add_submenu_page(
            'toolkit',
            __('Docs', 'toolkit'),
            __('Docs', 'toolkit'),
            'edit_theme_options',
            'toolkit-docs',
            [self::class, 'display_markdown_docs']
        );
    }

    public static function display_markdown_docs() {
        $requested_file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : 'index.html';
        $base_dir = WP_TOOLKIT_DIR . 'docs/';
        $file_path = realpath($base_dir . $requested_file);

        // Security check to prevent path traversal attacks
        if (strpos($file_path, realpath($base_dir)) !== 0 || !file_exists($file_path)) {
            echo '<p>' . esc_html__('File not found or access denied.', 'toolkit') . '</p>';
            return;
        }

        if ($requested_file === 'index.html') {
            self::generate_index($base_dir);
        }

        $markdown_content = file_get_contents($file_path);
        $html_content = $requested_file === 'index.html' ? $markdown_content : self::parse_markdown($markdown_content);

        echo '<div id="toolkit-docs" class="wrap">' . $html_content . '</div>';

        // Add the bottom navigation links
        $index_url = menu_page_url('toolkit-docs', false);
        
        if ($requested_file !== 'index.html') {
            echo '<p><a href="' . $index_url . '">' . esc_html__('Retour à la table des matières', 'toolkit') . '</a></p>';
        }
    }

    private static function parse_markdown($markdown) {
        $Parsedown = new ParsedownToc();
        $body = $Parsedown->body($markdown);
        $toc  = $Parsedown->contentsList();
        return "<div class='grid grid-cols-5-1'><div class='content'>" . $body . "</div><div class='toc'><div class='fix'>" . $toc . "</div></div></div>";
    }

    private static function generate_index($dir) {
        $directoryIterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $iterator->setMaxDepth(1); // Adjust depth as needed
    
        $entries = [];
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'index.html') {
                continue;
            }
            $entries[] = $file->getPathname();
        }
    
        // Sort the entries
        sort($entries, SORT_NATURAL | SORT_FLAG_CASE);

        $toc = "<div id='table-of-contents'><h1>Table des matières</h1>";
        $currentDir = '';
        $sectionNumber = 0;
    
        foreach ($entries as $filePath) {
            $relativePath = substr($filePath, strlen($dir));
        
            // Check if we are in a new directory
            $dirPath = dirname($relativePath);
            if ($dirPath !== '.' && $dirPath !== $currentDir) {
                // Close the previous section if not the first
                if ($currentDir !== '') {
                    $toc .= "</div>\n"; // Close the previous section div
                }
                
                // Update current directory
                $currentDir = $dirPath;
                // Format directory name
                $dirName = str_replace(['/', '-', '_'], ' ', $dirPath);
                $dirName = ucwords(strtolower($dirName));
                // Remove the first number if it exists
                $dirName = preg_replace('/^\d+\s*/', '', $dirName);
                $sectionNumber++;
                // Add the section with a toggle link
                $toc .= "<div class='toggle-section' data-toggle-id='section-$sectionNumber'\" data-section-id='section-$sectionNumber'\" class='section-header'>
                            <h2>$dirName</h2>
                            <svg height=\"20px\" width=\"20px\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\">
                                <path d=\"M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z\"/>
                            </svg>
                        </div>\n";
                $toc .= "<div id='section-$sectionNumber' class='section flex flex-col'>\n";
            }
        
            // Only process files, not directories
            if (is_file($filePath)) {
                $filenameWithoutExtension = pathinfo($filePath, PATHINFO_FILENAME);
                $formattedName = str_replace(['-', '_'], ' ', $filenameWithoutExtension);
                $formattedName = ucfirst(strtolower($formattedName));
        
                $url = menu_page_url('toolkit-docs', false) . '&file=' . urlencode($relativePath);
                $toc .= "<a href=\"$url\">$formattedName</a>\n";
            }
        }
        
        // Close the last section div if there was at least one file
        if (!empty($entries)) {
            $toc .= "</div>\n"; // Close the last section div
        }

        // Close table of contents div
        $toc .= "</div>\n";

        // Add JavaScript function for toggling sections
        $toc .= "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n"; // Include jQuery
        $toc .= "<script>\n";
        $toc .= "$(document).ready(function() {\n";
        $toc .= "  $('.toggle-section').click(function() {\n"; // Bind click event to elements with class 'toggle-section'
        $toc .= "    var id = $(this).data('section-id');\n"; // Get the data-section-id attribute
        $toc .= "    // remove hide class from the section with the id\n";
        $toc .= "    $('#' + id).toggleClass('hide').toggleClass('flex flex-col');\n";
        $toc .= "    // toggle the svg rotation\n";
        $toc .= "    $(this).find('svg').toggleClass('rotate-180');\n";
        $toc .= "  });\n";
        $toc .= "});\n";
        $toc .= "</script>\n";  
        
        file_put_contents($dir . '/index.html', $toc);
    }
      
}
