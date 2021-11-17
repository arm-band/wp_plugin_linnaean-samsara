<?php

namespace LinnaeanSamsara\app\src;

/**
 * InsertUpdate: 追加・更新処理
 *
 */
class InsertUpdate
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
     * insertUpdate
     *
     * desc: 投稿からタクソノミーを生成・更新する
     */
    public function insertUpdate( $post_ID )
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
            sanitize_title($prefix . '-' . $post_ID . '-' . $post->post_name),
            $taxonomy,
            OBJECT
        );
        if( $term !== false ) {
            // タームが存在する場合
            $termUpdated = wp_update_term(
                $term->term_id,
                $taxonomy,
                [
                    'name'        => $post->post_title,
                    'description' => $post_ID . ': ' . $post->post_name,
                    'slug'        => sanitize_title($prefix . '-' . $post_ID . '-' . $post->post_name),
                ]
            );
        }
        else {
            // 新規登録
            $term = wp_insert_term(
                $post->post_title, // the term
                $taxonomy,         // taxonomy
                [
                    'description' => $post_ID . ': ' . $post->post_name,
                    'slug'        => sanitize_title($prefix . '-' . $post_ID . '-' . $post->post_name),
                ]
            );
        }
        return true;
    }
    /**
     * main
     *
     * desc: 記事公開時にタームを追加・更新する
     */
    public function main( $post_ID )
    {
        if( $this->check() ) {
            return $this->insertUpdate( $post_ID );
        }
        return false;
    }
}
