<?php

namespace MPHBR;

class Importer
{
    /**
     * @var array|null
     * @since 1.2.3
     */
    protected $importedTerms = null;

    public function __construct()
    {
        add_action('import_start', [$this, 'startImport']);

        // OCDI - add term meta "_mphb_import_id"
        add_action('wxr_importer.processed.term', [$this, 'addTermImportId'], 10, 2);
        // WPI - add term meta "_mphb_import_id"
        add_filter('wp_import_term_meta', [$this, 'filterTermMeta'], 10, 3);

        // WPI & OCDI - replace rating IDs in comment meta
        add_filter('wp_import_post_comments', [$this, 'filterCommentMeta']);

        // WPI & OCDI - replace reting IDs in port meta
        add_filter('import_post_meta_key', [$this, 'filterPostMetaKey'], 10, 3);

        // Use later priority than the main plugin (20) to check did_action("mphb_import_end")
        add_action('import_end', [$this, 'endImport'], 25);
    }

    /**
     * @param bool $force Optional. FALSE by default.
     * @return array [Old term ID => New term ID]
     *
     * @since 1.2.3
     */
    public function getImportedTerms($force = false)
    {
        if (is_null($this->importedTerms) || $force) {
            $this->importedTerms = [];

            $ratingTypes = MPHBR()->getRatingTypeTaxonomy()->getAll();

            foreach ($ratingTypes as $ratingId) {
                $importId = get_term_meta($ratingId, '_mphb_import_id', true);

                if (is_numeric($importId)) {
                    $this->importedTerms[(int)$importId] = $ratingId;
                }
            }
        }

        return $this->importedTerms;
    }

    /**
     * @since 1.2.3
     */
    public function startImport()
    {
        // Disable updating average ratings while import
        add_filter('mphbr_update_average_ratings_on_save_review', '__return_false');
    }

    /**
     * @param int $newTermId
     * @param array $term
     *
     * @since 1.2.3
     */
    public function addTermImportId($newTermId, $term)
    {
        add_term_meta($newTermId, '_mphb_import_id', (int)$term['id']);
    }

    /**
     * @param array $termmeta Array of [key, value].
     * @param int $newTermId
     * @param array $term
     * @return array
     *
     * @since 1.2.3
     */
    public function filterTermMeta($termmeta, $newTermId, $term)
    {
        if (isset($term['term_id'], $term['term_taxonomy']) && $term['term_taxonomy'] == 'mphbr_ratings') {
            $termmeta[] = [
                'key'   => '_mphb_import_id',
                'value' => (int)$term['term_id']
            ];
        }

        return $termmeta;
    }

    /**
     * @param array $comments
     * @return array
     *
     * @since 1.2.3
     */
    public function filterCommentMeta($comments)
    {
        $importedTerms = $this->getImportedTerms();

        foreach ($comments as &$comment) {
            if (!isset($comment['comment_id'], $comment['comment_type'], $comment['commentmeta'])) {
                continue;
            }

            if ($comment['comment_type'] != 'mphbr_review') {
                continue;
            }

            // Replace old rating type IDs with the new ones
            foreach ($comment['commentmeta'] as &$commentmeta) {
                $isRating = preg_match('/^mphbr_rating_(?<id>\d+)$/', $commentmeta['key'], $matches);
                $oldTermId = $isRating ? (int)$matches['id'] : 0;

                if (isset($importedTerms[$oldTermId])) {
                    $newTermId = $importedTerms[$oldTermId];
                    $commentmeta['key'] = 'mphbr_rating_' . $newTermId;
                }
            }

            unset($commentmeta);
        }

        unset($comment);

        return $comments;
    }

    /**
     * @param string $metaKey
     * @param int $newPostId
     * @param array $post
     * @return string
     *
     * @since 1.2.3
     */
    public function filterPostMetaKey($metaKey, $newPostId, $post)
    {
        if (isset($post['post_type']) && $post['post_type'] == MPHB()->postTypes()->roomType()->getPostType()) {
            $importedTerms = $this->getImportedTerms();

            $isRating = (bool)preg_match('/^mphbr_rating_(?<id>\d+)(?<count>_count)?$/', $metaKey, $matches);
            $oldTermId = $isRating ? (int)$matches['id'] : 0;
            $suffix = isset($matches['count']) ? $matches['count'] : '';

            if (isset($importedTerms[$oldTermId])) {
                $newTermId = $importedTerms[$oldTermId];
                $metaKey = 'mphbr_rating_' . $newTermId . $suffix;
            }
        }

        return $metaKey;
    }

    /**
     * @since 1.2.3
     */
    public function endImport()
    {
        if (!did_action('mphb_import_end')) {
            do_action('mphb_import_end');
        }

        remove_filter('mphbr_update_average_ratings_on_save_review', '__return_false');
    }
}
