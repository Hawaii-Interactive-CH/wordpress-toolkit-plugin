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
    <title><?= __('Site Under Maintenance', 'wp-theme-toolkit') ?></title>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <main id="maintenance">
        <div class="row">
            <h1><?= __('Site Under Maintenance', 'wp-theme-toolkit') ?></h1>
            <p><?= __('We are currently updating the site and will be back very soon!', 'wp-theme-toolkit') ?></p>
        </div>
    </main>
    <?php wp_footer(); ?>
</body>

</html>