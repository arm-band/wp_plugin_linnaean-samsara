<?php
/*
  Plugin Name: Linnaean Samsara
  Plugin URI:
  Description: (カスタム)投稿と(カスタム)タクソノミーを同期させ、別の(カスタム)投稿と紐づけることを可能にするプラグイン。
  Version: 0.0.1
  Author: アルム＝バンド
  Author URI:
  License: MIT
*/

namespace LinnaeanSamsara;

date_default_timezone_set('Asia/Tokyo');
mb_language('ja');
mb_internal_encoding('UTF-8');

/**
 * 古杣
 *
 * desc: メイン処理
 */
class LinnaeanSamsara
{
    /**
     * var
     */
    protected $c;
    protected $instance;
    protected $initialize;
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        try {
            if( !require_once( __DIR__ . '/app/init.php' ) ) {
                throw new \Exception( '初期化ファイル読み込みに失敗しました: init.php' );
            }
        } catch ( \Exception $e ) {
            echo $e->getMessage();
        }

        $this->initialize = new \LinnaeanSamsara\app\initialize();
        $this->c = $this->initialize->getConstant();
        $this->instance = $this->initialize->getInstance( $this->initialize );
    }
    /**
     * 管理者画面にメニューと設定画面を追加、プラグインの機能有効化
     */
    public function initialize()
    {
        $posttype = $this->initialize->returnPostType();

        // メニューを追加
        add_action( 'admin_menu', [ $this, 'linnaeansamsara_create_menu' ] );
        // 独自関数をコールバック関数とする
        add_action( 'admin_init', [ $this, 'register_linnaeansamsara_settings' ] );
        // 一括生成ページのアクションフック
        add_action( 'admin_head-' . sanitize_title( $this->c['LINNAEANSAMSARA_SETTINGS'] ) . '_page_' . $this->c['LINNAEANSAMSARA'] . '_bulkadd', [ $this->instance['Bulkadd'], 'main' ] );
        // 記事を投稿(公開)・更新時のアクションフック
        add_action( 'publish_' . $posttype, [ $this->instance['InsertUpdate'], 'main' ] );
        // 記事削除 (ゴミ箱移動) 時のアクションフック
        add_action( 'trash_' . $posttype, [ $this->instance['Delete'], 'main' ] );
    }
    /**
     * メニュー追加
     */
    public function linnaeansamsara_create_menu()
    {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        //  $page_title : 設定ページの `title` 部分
        //  $menu_title : メニュー名
        //  $capability : 権限 ( 'manage_options' や 'administrator' など)
        //  $menu_slug  : メニューのslug
        //  $function   : 設定ページの出力を行う関数
        //  $icon_url   : メニューに表示するアイコン
        //  $position   : メニューの位置 ( 1 や 99 など )
        add_menu_page(
            $this->c['LINNAEANSAMSARA_SETTINGS'],
            $this->c['LINNAEANSAMSARA_SETTINGS'],
            'administrator',
            $this->c['LINNAEANSAMSARA'],
            [ $this, $this->c['LINNAEANSAMSARA'] . '_settings_page' ],
            'dashicons-update'
        );
        // add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
        //  $parent_slug : 親スラグ
        //  $page_title  : 設定ページの `title` 部分
        //  $menu_title  : メニュー名
        //  $capability  : 権限 ( 'manage_options' や 'administrator' など)
        //  $menu_slug   : メニューのslug
        //  $function    : 設定ページの出力を行う関数
        //  $position    : メニューの位置 ( 1 や 99 など )
        add_submenu_page(
            $this->c['LINNAEANSAMSARA'],
            $this->c['LINNAEANSAMSARA_SUB_SETTINGS'],
            $this->c['LINNAEANSAMSARA_SUB_SETTINGS'],
            'administrator',
            $this->c['LINNAEANSAMSARA'] . '_bulkadd',
            [ $this, $this->c['LINNAEANSAMSARA'] . '_bulkadd_page' ],
        );
    }
    /**
     * コールバック
     */
    public function register_linnaeansamsara_settings()
    {
        // register_setting( $option_group, $option_name, $sanitize_callback )
        //  $option_group      : 設定のグループ名
        //  $option_name       : 設定項目名(DBに保存する名前)
        //  $sanitize_callback : 入力値調整をする際に呼ばれる関数
        register_setting(
            $this->c['LINNAEANSAMSARA_SETTINGS_EN'],
            $this->c['LINNAEANSAMSARA_POSTS_RADIOS'],
            [ $this, $this->c['LINNAEANSAMSARA_POSTS_VALIDATION'] ]
        );
        register_setting(
            $this->c['LINNAEANSAMSARA_SETTINGS_EN'],
            $this->c['LINNAEANSAMSARA_TAXONOMIES_RADIOS'],
            [ $this, $this->c['LINNAEANSAMSARA_TAXONOMIES_VALIDATION'] ]
        );
        register_setting(
            $this->c['LINNAEANSAMSARA_SETTINGS_EN'],
            $this->c['LINNAEANSAMSARA_PREFIX'],
            [ $this, $this->c['LINNAEANSAMSARA_PREFIX_VALIDATION'] ]
        );
    }
    /**
     * 投稿のバリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function linnaeansamsara_posts_validation( $newInput )
    {
        // nonce check
        check_admin_referer( $this->c['LINNAEANSAMSARA'] . '_options', 'name_of_nonce_field' );

        // validation
        if( preg_match( '/^[\w\d\-_]+$/i', $newInput ) ) {
            return $newInput;
        }
        else {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['LINNAEANSAMSARA'],
                $this->c['LINNAEANSAMSARA'] . '_posts-validation_error',
                __(
                    '選択した投稿タイプが不正です。',
                    $this->c['LINNAEANSAMSARA']
                ),
                'error'
            );

            return $this->initialize->returnPostType();
        }
    }
    /**
     * タクソノミーのバリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function linnaeansamsara_taxonomies_validation( $newInput )
    {
        // nonce check
        check_admin_referer( $this->c['LINNAEANSAMSARA'] . '_options', 'name_of_nonce_field' );

        // validation
        if( preg_match( '/^[\w\d\-_]+$/i', $newInput ) ) {
            return $newInput;
        }
        else {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['LINNAEANSAMSARA'],
                $this->c['LINNAEANSAMSARA'] . '_taxonomies-validation_error',
                __(
                    '選択したタクソノミーが不正です。',
                    $this->c['LINNAEANSAMSARA']
                ),
                'error'
            );

            return $this->initialize->returnTaxonomy();
        }
    }
    /**
     * プレフィックスのバリデーション。コールバックから呼ばれる
     *
     * @param array $newInput 設定画面で入力されたパラメータ
     *
     * @return string $newInput / $ANONYMOUS バリデーションに成功した場合は $newInput そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     */
    public function linnaeansamsara_prefix_validation( $newInput )
    {
        // nonce check
        check_admin_referer( $this->c['LINNAEANSAMSARA'] . '_options', 'name_of_nonce_field' );

        // validation
        if( preg_match( '/^[\w\d\-_]+$/i', $newInput ) ) {
            return $newInput;
        }
        else {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['LINNAEANSAMSARA'],
                $this->c['LINNAEANSAMSARA'] . '_prefix-validation_error',
                __(
                    '入力したプレフィックスが不正です。',
                    $this->c['LINNAEANSAMSARA']
                ),
                'error'
            );

            return $this->initialize->returnPrefix();
        }
    }
    /**
     * 設定画面ページの生成
     */
    public function linnaeansamsara_settings_page()
    {
        if( get_settings_errors( $this->c['LINNAEANSAMSARA'] ) ) {
            // エラーがあった場合はエラーを表示
            settings_errors( $this->c['LINNAEANSAMSARA'] );
        }
        else if( true == $_GET['settings-updated'] ) {
            //設定変更時にメッセージ表示
?>
            <div id="settings_updated" class="updated notice is-dismissible"><p><strong>設定を保存しました。</strong></p></div>
<?php
        }
        $currentPostType = $this->initialize->returnPostType();
        $currentTaxonomy = $this->initialize->returnTaxonomy();
?>

        <div class="wrap">
            <h1><?= esc_html( $this->c['LINNAEANSAMSARA_SETTINGS'] ); ?></h1>
            <h2>投稿タイプとタクソノミーの紐付け</h2>
            <p>以下の投稿タイプとタクソノミーから、紐付けたい項目にチェックを入れてください。</p>
            <form method="post" action="options.php">
<?php settings_fields( $this->c['LINNAEANSAMSARA_SETTINGS_EN'] ); ?>
<?php do_settings_sections( $this->c['LINNAEANSAMSARA_SETTINGS_EN'] ); ?>
                <table class="form-table" id="<?= esc_attr( $this->c['LINNAEANSAMSARA_POSTS_RADIOS'] ); ?>-table">
                    <tr>
                        <th>投稿タイプ</th>
                        <td>
<?php
        $postTypes = get_post_types(
            [
                'public' => true, // システム的な投稿の使い方をしている投稿タイプは除外
            ],
            'objects'
        );
        foreach ( $postTypes as $postType ) {
            $checkedFlag = '';
            if ( $postType->name === $currentPostType ) {
                $checkedFlag = ' checked="checked"';
            }
?>
                            <label
                                for="<?= esc_attr( $this->c['LINNAEANSAMSARA_POSTS_RADIOS'] ); ?>-<?= esc_attr( $postType->name ); ?>"
                            >
                                <input
                                    type="radio"
                                    name="<?= esc_attr( $this->c['LINNAEANSAMSARA_POSTS_RADIOS'] ); ?>"
                                    id="<?= esc_attr( $this->c['LINNAEANSAMSARA_POSTS_RADIOS'] ); ?>-<?= esc_attr( $postType->name ); ?>"
                                    value="<?= esc_attr( $postType->name ); ?>"
                                    <?= esc_html( $checkedFlag ); ?>
                                >
                                <?= esc_html( $postType->label ); ?>
                            </label>
<?php
        }
?>
                        </td>
                    </tr>
                </table>
                <table class="form-table" id="<?= esc_attr( $this->c['LINNAEANSAMSARA_TAXONOMIES_RADIOS'] ); ?>-table">
                    <tr>
                        <th>タクソノミー</th>
                        <td>
<?php
        // $postTypes は使い回し
        foreach ( $postTypes as $postType ) {
            $taxonomiesObjs = get_object_taxonomies(
                $postType->name,
                'objects'
            );
            foreach ( $taxonomiesObjs as $taxonomiesObj ) {
                // $taxonomies が空配列ではなく、階層型であるならば
                if( is_taxonomy_hierarchical( $taxonomiesObj->name ) || $taxonomiesObj->name === 'category' ) {
                    $checkedFlag = '';
                    if ( $taxonomiesObj->name === $currentTaxonomy ) {
                        $checkedFlag = ' checked="checked"';
                    }
?>
                            <label
                                for="<?= esc_attr( $this->c['LINNAEANSAMSARA_TAXONOMIES_RADIOS'] ); ?>-<?= esc_attr( $taxonomiesObj->name ); ?>"
                            >
                                <input
                                    type="radio"
                                    name="<?= esc_attr( $this->c['LINNAEANSAMSARA_TAXONOMIES_RADIOS'] ); ?>"
                                    id="<?= esc_attr( $this->c['LINNAEANSAMSARA_TAXONOMIES_RADIOS'] ); ?>-<?= esc_attr( $taxonomiesObj->name ); ?>"
                                    value="<?= esc_attr( $taxonomiesObj->name ); ?>"
                                    <?= esc_html( $checkedFlag ); ?>
                                >
                                <?= esc_html( $taxonomiesObj->label ); ?>(<?= esc_html( $postType->label ); ?>)
                            </label>

<?php
                }
            }
        }
?>
                        </td>
                    </tr>
                </table>
                <h2>プレフィックス</h2>
                <p>タクソノミーのスラッグに付けるプレフィックスの設定です。使用できる文字列は小文字の半角英数字とハイフンのみ、空文字は不可です。初期値: <code>linnaean</code></p>
                <table class="form-table" id="<?= esc_attr( $this->c['LINNAEANSAMSARA_PREFIX'] ); ?>-table">
                    <tr>
                        <th></th>
                        <td>
                            <input type="text" name="<?= esc_attr( $this->c['LINNAEANSAMSARA_PREFIX'] ); ?>" id="<?= esc_attr( $this->c['LINNAEANSAMSARA_PREFIX'] ); ?>" value="<?= esc_attr( $this->initialize->returnPrefix() ); ?>" required="required">
                        </td>
                    </tr>
                </table>
<?php wp_nonce_field( $this->c['LINNAEANSAMSARA'] . '_options', 'name_of_nonce_field' ); ?>
<?php submit_button( '設定を保存', 'primary large', 'submit', true, [ 'tabindex' => '1' ] ); ?>
            </form>
        </div>

<?php
    }
    /**
     * 一括生成ページの生成
     */
    public function linnaeansamsara_bulkadd_page()
    {
        if( get_settings_errors( $this->c['LINNAEANSAMSARA'] ) ) {
            // エラーがあった場合はエラーを表示
            settings_errors( $this->c['LINNAEANSAMSARA'] );
        }
        else if( true == $_GET['settings-updated'] ) {
            //設定変更時にメッセージ表示
?>
            <div id="settings_updated" class="updated notice is-dismissible"><p><strong>一括生成が完了しました。</strong></p></div>
<?php
        }
?>

        <div class="wrap">
            <h1>リンネ 一括生成</h1>
            <p>紐付けた投稿タイプからタクソノミーを一括生成したい場合は以下のボタンをクリックしてください。</p>
            <form method="post" action="admin.php?page=<?= esc_attr( $this->c['LINNAEANSAMSARA'] . '_bulkadd' ); ?>">
                <table class="form-table" id="<?= esc_attr( $this->c['LINNAEANSAMSARA_SUB_SETTINGS_EN'] ); ?>-table">
                    <tr>
                        <th></th>
                        <td>
                            <label
                                for="<?= esc_attr( $this->c['LINNAEANSAMSARA'] . '_bulkadd' ); ?>"
                            >
                                <input
                                    type="checkbox"
                                    name="<?= esc_attr( $this->c['LINNAEANSAMSARA'] . '_bulkadd' ); ?>"
                                    id="<?= esc_attr( $this->c['LINNAEANSAMSARA'] . '_bulkadd' ); ?>"
                                    value="<?= esc_attr( $this->c['LINNAEANSAMSARA'] . '_bulkadd' ); ?>"
                                    required="required"
                                >
                                <?= esc_html( $this->c['LINNAEANSAMSARA_SUB_SETTINGS'] ); ?>する
                            </label>
                        </td>
                    </tr>
                </table>
<?php settings_fields( $this->c['LINNAEANSAMSARA_SUB_SETTINGS_EN'] ); ?>

<?php wp_nonce_field( $this->c['LINNAEANSAMSARA_SUB_SETTINGS_EN'] . '_options', 'name_of_nonce_field' ); ?>
<?php submit_button( '実行', 'primary large', 'submit', true, [ 'tabindex' => '1' ] ); ?>
            </form>
        </div>

<?php
    }
}

// 処理
$wp_ab_linnaeansamsara = new LinnaeanSamsara();

if( is_admin() ) {
    // 管理者画面を表示している場合のみ実行
    $wp_ab_linnaeansamsara->initialize();
}
