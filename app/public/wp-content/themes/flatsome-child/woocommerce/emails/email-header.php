<?php
/**
 * Email header — MyCreatief brand override.
 *
 * @var string $email_heading
 */
defined('ABSPATH') || exit;

$logo_id  = (int) get_option('site_logo', 0);
$logo_src = $logo_id > 0
    ? wp_get_attachment_image_url($logo_id, array(360, 120))
    : get_site_url() . '/wp-content/uploads/2025/12/MyCreatief-logo.png';
$site_url = get_site_url();
$site_name = get_bloginfo('name');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo esc_html($site_name); ?></title>
    <?php do_action('woocommerce_email_styles', $email ?? null); ?>
</head>
<body>
<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="wrapper"
       style="background-color:#f5f0eb; padding:20px 0;">
    <tr>
        <td align="center" valign="top">
            <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container"
                   style="background-color:#ffffff; border:1px solid #ddd5c8; border-radius:6px;">

                <!-- Logo header -->
                <tr>
                    <td id="template_header" align="center"
                        style="background-color:#446084; border-radius:6px 6px 0 0; padding:28px 40px;">
                        <a href="<?php echo esc_url($site_url); ?>" style="text-decoration:none;">
                            <?php if ($logo_src) : ?>
                                <img src="<?php echo esc_url($logo_src); ?>"
                                     alt="<?php echo esc_attr($site_name); ?>"
                                     style="max-width:180px; height:auto; display:block; margin:0 auto;">
                            <?php else : ?>
                                <span style="color:#ffffff; font-size:26px; font-weight:bold;
                                             font-family:Georgia,serif; letter-spacing:1px;">
                                    <?php echo esc_html($site_name); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </td>
                </tr>

                <?php if (!empty($email_heading)) : ?>
                <!-- Heading -->
                <tr>
                    <td align="left" style="padding:32px 48px 0;">
                        <h1 style="color:#446084; font-family:Georgia,serif; font-size:22px;
                                   font-weight:normal; margin:0 0 16px;">
                            <?php echo esc_html($email_heading); ?>
                        </h1>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- Body start -->
                <tr>
                    <td id="template_body" valign="top">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td valign="top" id="body_content"
                                    style="padding:8px 48px 32px; font-family:Arial,Helvetica,sans-serif;
                                           font-size:14px; color:#4a4a4a; line-height:1.65;">
