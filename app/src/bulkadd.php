<?php

namespace LinnaeanSamsara\app\src;

/**
 * Bulkadd: 一括生成処理
 *
 */
class Bulkadd
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
?>
            <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け元の投稿タイプが選択されていません。</strong></p></div>
<?php
            return false;
        }
        else {
            $posts = get_posts(
                [
                    'post_type'      => $postType,
                    'posts_per_page' => -1,
                ]
            );
            if( is_wp_error( $posts ) ) {
?>
                <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け元の投稿タイプが存在しません。</strong></p></div>
<?php
                return false;
            }
            if( count( $posts ) === 0 ) {
?>
                <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け元の投稿タイプに記事が存在していません。</strong></p></div>
<?php
                return false;
            }
        }
        if ( mb_strlen( $taxonomy, $this->c['ENCODING'] ) === 0 ) {
?>
            <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け先のタクソノミーが選択されていません。</strong></p></div>
<?php
            return false;
        }
        else {
            $terms = get_terms(
                [
                    'taxonomy' => $taxonomy,
                ]
            );
            if( is_wp_error( $terms ) ) {
?>
            <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け先のタクソノミーが存在しません。</strong></p></div>
<?php
                return false;
            }
            if( count( $terms ) > 0 ) {
?>
            <div id="settings_error" class="error notice is-dismissible"><p><strong>紐付け先のタクソノミーに既にタームが存在しています。</strong></p></div>
<?php
                return false;
            }
        }
        if ( mb_strlen( $prefix, $this->c['ENCODING'] ) === 0 ) {
?>
            <div id="settings_error" class="error notice is-dismissible"><p><strong>プレフィックスが設定されていません。</strong></p></div>
<?php
            return false;
        }
        return true;
    }
    /**
     * generate
     *
     * desc: 投稿からタクソノミーを一括生成する
     */
    public function generate()
    {
        $postType = $this->initialize->returnPostType();
        $taxonomy = $this->initialize->returnTaxonomy();
        $prefix   = $this->initialize->returnPrefix();
        $posts = get_posts(
            [
                'post_type' => $postType,
                'posts_per_page' => -1,
            ]
        );
        $errPost = [];
        foreach ( $posts as $key => $val ) {
            $termArray = wp_insert_term(
                $val->post_title, // the term
                $taxonomy,        // taxonomy
                [
                    'description' => $val->ID . ': ' . $val->post_name,
                    'slug'        => sanitize_title($prefix . '-' . $val->ID . '-' . $val->post_name),
                ]
            );
            if( is_wp_error( $termArray ) ) {
                $errPost[] = $val->post_title;
            }
        }
        if( count( $errPost ) > 0 ) {
?>
                <div id="settings_error" class="error notice is-dismissible"><p><strong>以下の投稿は該当タクソノミーに追加する際にエラーが発生しました。</strong></p></div>
                <div class="wrap">
                    <ul>
<?php
            for ( $i = 0; $i < count( $errPost ); $i++ ) {
?>
                        <li><?= esc_html( $errPost[$i] ); ?></li>
<?php
            }
?>
                    </ul>
                </div>
<?php
            return false;
        }
        return true;
    }
    /**
     * main
     *
     * desc: パラメータチェックの後、一括生成の処理を実行する
     */
    public function main()
    {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST') {
            if ( isset( $_POST[$this->c['LINNAEANSAMSARA'] . '_bulkadd'] ) && $_POST[$this->c['LINNAEANSAMSARA'] . '_bulkadd'] === $this->c['LINNAEANSAMSARA'] . '_bulkadd' ) {
                if( $this->check() ) {
                    if( $this->generate() ) {
?>
                <div id="settings_updated" class="updated notice is-dismissible"><p><strong>処理が完了しました。</strong></p></div>
<?php
                    }
                }
            }
            else {
?>
                <div id="settings_error" class="error notice is-dismissible"><p><strong>確認のチェックボックスにチェックが入っていません。</strong></p></div>
<?php
            }
        }
        // GET の場合はデフォルトなので何もしない
    }
}
