<?php
/**
 * Template Name: Brand Discovery
 * Template Post Type: page
 *
 * @package SUED_Studio
 */

defined('ABSPATH') || exit;

// Enqueue styles & scripts
wp_enqueue_style(
    'tabler-icons',
    'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css',
    [],
    '2.47.0'
);
wp_enqueue_style('sued-brand-discovery-css', SUED_URI . '/assets/css/brand-discovery.css', ['tabler-icons'], SUED_VERSION);
wp_enqueue_script('sued-brand-discovery-js',  SUED_URI . '/assets/js/brand-discovery.js', [], SUED_VERSION, ['in_footer' => true]);

wp_localize_script('sued-brand-discovery-js', 'SUED_BD_CONFIG', [
    'restUrl'    => esc_url_raw(rest_url('sued/v1/brand-discovery-submit')),
    'nonce'      => wp_create_nonce('wp_rest'),
    'siteUrl'    => home_url('/'),
    'agencyName' => 'SUED Studio',
]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Brand Discovery — SUED Studio', 'sued-studio'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('sued-brand-discovery-page'); ?>>
<?php wp_body_open(); ?>

<!-- Header Quiz Part (Clean, Logo Only) -->
<header class="wp-block-group sued-site-header sued-header-quiz">
  <div class="wp-block-group sued-container" style="padding-bottom:2rem">
    <figure class="wp-block-image size-large is-resized">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="<?php echo esc_url(SUED_URI . '/assets/images/SUED%20Studio%20Logo.svg'); ?>" alt="SUED Studio Logo" style="width:140px"/>
      </a>
    </figure>
  </div>
</header>

<main class="wp-block-group">
  <?php echo do_shortcode('[sued_brand_discovery]'); ?>
</main>

<?php
// Include footer part if available or block
block_template_part('footer');
wp_footer();
?>
</body>
</html>

