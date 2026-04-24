<?php
/**
 * Email footer — MyCreatief brand override.
 */
defined('ABSPATH') || exit;

$footer_text = get_option('woocommerce_email_footer_text', '');
$site_url    = get_site_url();
?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td id="template_footer" align="center"
                        style="background-color:#f5f0eb; border-top:1px solid #ddd5c8;
                               border-radius:0 0 6px 6px; padding:24px 40px;">
                        <p style="margin:0 0 6px; font-family:Arial,Helvetica,sans-serif;
                                  font-size:13px; color:#7a6f66;">
                            Met liefde handgemaakt in Nederland &mdash;
                            <a href="<?php echo esc_url($site_url); ?>"
                               style="color:#446084; text-decoration:none;">mycreatief.nl</a>
                        </p>
                        <?php if ($footer_text) : ?>
                        <p style="margin:6px 0 0; font-family:Arial,Helvetica,sans-serif;
                                  font-size:11px; color:#aaa;">
                            <?php echo wp_kses_post(wpautop(wptexturize($footer_text))); ?>
                        </p>
                        <?php endif; ?>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
