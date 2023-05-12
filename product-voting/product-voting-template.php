<!-- <div class="product-voting">
    <button class="product-upvote">Upvote</button>
    <button class="product-downvote">Downvote</button>
    <span class="product-votes">
        Upvotes: <?php echo get_post_meta(get_the_ID(), 'product_upvotes', true); ?> |
        Downvotes: <?php echo get_post_meta(get_the_ID(), 'product_downvotes', true); ?>
    </span>
</div> -->

<div id="product-voting-container"></div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . 'product-voting-script.js'; ?>"></script>