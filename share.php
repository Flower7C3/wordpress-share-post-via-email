<?php
/*
Plugin Name: Share post via email
Version: 1.0
Text Domain: post-2-mail
Author: Kwiatek.pro
Author URI: https://kwiatek.pro
License: GPL2

Copyright 2021 Kwiatek.pro

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
add_action('admin_menu', static function () {
    add_submenu_page(null, _x('Share', 'page meta title', 'post-2-mail'), null, 'edit_pages', 'share', static function () {
        $post_id = (int)$_GET['post'];
        if (!$post_id) {
            $url = admin_url();
            if ($_GET['post_type']) {
                $url = admin_url('edit.php') . '?post_type=' . $_GET['post_type'];
            }
            echo("<script>location.href = '" . $url . "'</script>");
            exit;
        }
        $post = get_post($post_id);
        $taxonomy = get_post_type_object($post->post_type);
        $heading = sprintf(
            sprintf(
                _x('Share %s &#8220;%s&#8221;', 'page heading title', 'post-2-mail'), mb_strtolower($taxonomy->labels->singular_name), '%s'),
            sprintf(
                '<a href="%1$s">%2$s</a>',
                get_edit_post_link($post_id),
                wp_html_excerpt(_draft_or_post_title($post_id), 50, '&hellip;'))
        );
        $email_subject = sprintf(_x('%1$s "%2$s" at %3$s', 'share email subject', 'post-2-mail'), $taxonomy->labels->view_item, $post->post_title, get_bloginfo('name'));
        if ($post->post_password) {
            $email_message = sprintf(_x('<p>You can see %1$s <strong>%2$s</strong> page at <a href="%3$s">%3$s</a> url protected with <code>%4$s</code> password.</p>', 'share email message', 'post-2-mail'), $taxonomy->labels->singular_name, $post->post_title, get_permalink($post->ID), $post->post_password);
        } else {
            $email_message = sprintf(_x('<p>You can see %1$s <strong>%2$s</strong> page at <a href="%3$s">%3$s</a> url.</p>', 'share email message', 'post-2-mail'), $taxonomy->labels->singular_name, $post->post_title, get_permalink($post->ID));
        }

        $status = null;
        if ('POST' === $_SERVER['REQUEST_METHOD'] && check_ajax_referer('share', '_wpnonce', FALSE) && isset($_POST['email']) && !empty($_POST['email'])) {
            $headers = array('Content-Type: text/html; charset=UTF-8');
            if (wp_mail($_POST['email'], $email_subject, $email_message, $headers)) {
                $status = '<p class="notice notice-success">' . __('Email sent.') . '</p>';
            } else {
                $status = '<p class="notice notice-error">' . __('Email could not be sent.') . '</p>';
            }
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $heading ?></h1>
            <form method="post" class="form-wrap" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>" enctype="multipart/form-data">
                <fieldset style="border: 1px solid;margin: 10px 0;padding: 10px;">
                    <div>
                        <span class="title"><?php _e('Email'); ?></span>
                        <span>
                            <input type="email" name="email" required/>
                        </span>
                    </div>
                    <div>
                        <span class="title"><?php _e('Subject'); ?></span>
                        <blockquote><?php echo $email_subject; ?></blockquote>
                    </div>
                    <div>
                        <span class="title"><?php _e('Message'); ?></span>
                        <blockquote><?php echo $email_message; ?></blockquote>
                    </div>
                    <div class="submit">
                        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('share') ?>"/>
                        <input type="submit" value="<?php _e('Submit') ?>" class="button-primary"/>
                    </div>
                    <?php echo $status; ?>
                </fieldset>
            </form>
        </div>
        <?php
    });
});
function share_page($actions, $post)
{
    $link = admin_url('edit.php') . '?post_type=' . $post->post_type . '&post=' . $post->ID . '&page=share';
    $actions[] = '<a href="' . $link . '" class="share_action" data-nonce="' . wp_create_nonce('share') . '">' . _x('Share', 'post row action', 'post-2-mail') . '</a>';
    return $actions;
}

add_filter('post_row_actions', 'share_page', 10, 2);
add_filter('page_row_actions', 'share_page', 10, 2);
