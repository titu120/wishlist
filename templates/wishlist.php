        <?php if ( ! empty( $wishlist_items ) ) : ?>
            <div class="aww-wishlist-items">
                <?php foreach ( $wishlist_items as $item ) : ?>
                    <?php include AWW_PLUGIN_DIR . 'templates/wishlist-item.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="aww-pagination">
                    <?php echo paginate_links( array(
                        'base' => add_query_arg( 'paged', '%#%' ),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => __( '&laquo; Previous', 'advanced-wc-wishlist' ),
                        'next_text' => __( 'Next &raquo;', 'advanced-wc-wishlist' ),
                    ) ); ?>
                </div>
            <?php endif; ?>

            <?php 
            // Add social sharing buttons
            echo AWW()->core->render_sharing_buttons( $wishlist_id, get_bloginfo( 'name' ) . ' Wishlist' );
            ?>

        <?php else : ?>
            <div class="aww-empty-wishlist">
                <p><?php esc_html_e( 'Your wishlist is empty.', 'advanced-wc-wishlist' ); ?></p>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button">
                    <?php esc_html_e( 'Continue Shopping', 'advanced-wc-wishlist' ); ?>
                </a>
            </div>
        <?php endif; ?> 