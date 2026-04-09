<?php

namespace Toolkit\views;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

?>

<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php esc_html_e( 'Site Under Maintenance', 'wordpress-toolkit-plugin' ); ?></title>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <main id="maintenance">
        <div class="row">
            <h1><?php esc_html_e( 'Site Under Maintenance', 'wordpress-toolkit-plugin' ); ?></h1>
            <p><?php esc_html_e( 'We are currently updating the site and will be back very soon!', 'wordpress-toolkit-plugin' ); ?></p>
        </div>
    </main>
    <?php wp_footer(); ?>
</body>

</html>