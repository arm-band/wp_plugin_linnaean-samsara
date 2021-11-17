<?php

namespace LinnaeanSamsara\app\src;

/**
 * Delete: 削除処理
 *
 */
class Delete
{
    /**
     * var
     */
    protected $c;
    protected $initialize;
    /**
     * コンストラクタ
     */
    function __construct( $c, $i )
    {
        $this->c = $c;
        $this->initialize = $i;
    }

    /**
     * check
     *
     * desc: パラメータチェック
     */
    public function check()
    {
        $postType = $this->initialize->returnPostType();
        $taxonomy = $this->initialize->returnTaxonomy();
        $prefix   = $this->initialize->returnPrefix();
        if ( mb_strlen( $postType, $this->c['ENCODING'] ) === 0 ) {
            return false;
        }
        if ( mb_strlen( $taxonomy, $this->c['ENCODING'] ) === 0 ) {
            return false;
        }
        if ( mb_strlen( $prefix, $this->c['ENCODING'] ) === 0 ) {
            return false;
        }
        return true;
    }
    /**
     * delete
     *
     * desc: 投稿からタクソノミーを削除する
     */
    public function delete( $post_ID )
    {
        $postType = $this->initialize->returnPostType();
        $taxonomy = $this->initialize->returnTaxonomy();
        $prefix   = $this->initialize->returnPrefix();
        $post = get_post( $post_ID, OBJECT );
        // get_term_by( $field, $value, $taxonomy, $output, $filter )
        //  $field: 'id', 'slug', 'name', 'term_taxonomy_id'
        //  $value: この値を検索
        //  $taxonomy: タクソノミー名
        //  $output: 定数 OBJECT, ARRAY_A, ARRAY_N
        //  $filter: デフォルトは raw で、その場合 WordPress の既定のフィルターはどれも適用されない
        $term = get_term_by(
            'slug',
            preg_replace('/(__trashed)$/i', '', sanitize_title($prefix . '-' . $post_ID . '-' . $post->post_name)), // ゴミ箱へ移動するとスラッグの末尾に「～～__trashed」と付いてしまうのでそれを削除
            $taxonomy,
            OBJECT
        );
        if( $term !== false ) {
            // タームが存在する場合
            if ( $term->count > 0 ) {
                // タームを参照している記事が存在する場合はタームを削除しない
                return false;
            }
            $flag = wp_delete_term(
                $term->term_id,
                $taxonomy
            );
            return true;
        }
        return false;
    }
    /**
     * main
     *
     * desc: 記事削除時にタームを削除する
     */
    public function main( $post_ID )
    {
        if( $this->check() ) {
            return $this->delete( $post_ID );
        }
        return false;
    }
}
