<?php
namespace MPHBR;

use MPHBR\Views\ReviewView;
use MPHB\Utils\ValidateUtils;

class Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_mphbr_load_more', [$this, 'mphbr_load_more']);
        add_action('wp_ajax_nopriv_mphbr_load_more', [$this, 'mphbr_load_more']);
    }

    public function mphbr_load_more()
    {
        $input = $_GET;

        // Verify nonce
        $nonce = isset($input['nonce']) ? $input['nonce'] : '';

        if (!wp_verify_nonce($nonce, __FUNCTION__)) {
            // It's a technical message, no need to translate it - nobody will see it
            wp_send_json_error(['message' => 'Request did not pass security verification.']);
        }

        // Number or "all" for shortcode (in some cases)
        $accommodation = mphb_clean($input['accommodation_id']);

        // Get and validate the inputs
        $roomTypeId = $accommodation !== 'all' ? (int)ValidateUtils::validateInt($accommodation) : 0;
        $offset = (int)ValidateUtils::validateInt($input['offset']);
        $count = (int)ValidateUtils::validateInt($input['per_page']);

        $items = '';
        $itemsCount = 0;
        $hasMore = false;

        if (($roomTypeId > 0 || $accommodation === 'all') && $offset > 0 && $count > 0) {
            // Query reviews
            $queryArgs = MPHBR()->getReviewRepository()->getCommentsQueryArgs([
                'post_id' => $roomTypeId,
                'count'   => $count,
                'offset'  => $offset
            ]);

            if ($accommodation === 'all') {
                // Get rid of 0 or current post ID
                unset($queryArgs['post_id']);
            }

            $query      = new \WP_Comment_Query();
            $comments   = $query->query($queryArgs);
            $itemsCount = count($comments);
            $hasMore    = ($offset + $itemsCount) < $query->found_comments;

            // Render reviews
            ob_start();
            ReviewView::displayComments($comments);
            $items = ob_get_clean();
        }

        wp_send_json_success([
            'items'   => $items,
            'count'   => $itemsCount,
            'hasMore' => $hasMore
        ]);
    }
}
