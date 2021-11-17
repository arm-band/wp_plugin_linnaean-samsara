<?php

namespace LinnaeanSamsara\app;

/**
 * 初期化・準備
 */
class initialize
{
    /**
     * const
     */
    const LINNAEANSAMSARA                       = 'linnaeansamsara';
    const LINNAEANSAMSARA_SETTINGS              = 'リンネ 設定';
    const LINNAEANSAMSARA_SETTINGS_EN           = self::LINNAEANSAMSARA . '-settings';
    const LINNAEANSAMSARA_POSTS_RADIOS          = self::LINNAEANSAMSARA . '_posts_radios';
    const LINNAEANSAMSARA_TAXONOMIES_RADIOS     = self::LINNAEANSAMSARA . '_taxonomies_radios';
    const LINNAEANSAMSARA_PREFIX                = self::LINNAEANSAMSARA . '_prefix';
    const LINNAEANSAMSARA_POSTS_VALIDATION      = self::LINNAEANSAMSARA . '_posts_validation';
    const LINNAEANSAMSARA_TAXONOMIES_VALIDATION = self::LINNAEANSAMSARA . '_taxonomies_validation';
    const LINNAEANSAMSARA_PREFIX_VALIDATION     = self::LINNAEANSAMSARA . '_prefix_validation';
    const LINNAEANSAMSARA_SUB_SETTINGS          = '一括生成';
    const LINNAEANSAMSARA_SUB_SETTINGS_EN       = self::LINNAEANSAMSARA . '-bulkadd';
    const ENCODING                              = 'UTF-8';
    /**
     * var
     */
    protected $c;
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->c = [
            'LINNAEANSAMSARA'                       => self::LINNAEANSAMSARA,
            'LINNAEANSAMSARA_SETTINGS'              => self::LINNAEANSAMSARA_SETTINGS,
            'LINNAEANSAMSARA_SETTINGS_EN'           => self::LINNAEANSAMSARA_SETTINGS_EN,
            'LINNAEANSAMSARA_POSTS_RADIOS'          => self::LINNAEANSAMSARA_POSTS_RADIOS,
            'LINNAEANSAMSARA_TAXONOMIES_RADIOS'     => self::LINNAEANSAMSARA_TAXONOMIES_RADIOS,
            'LINNAEANSAMSARA_PREFIX'                => self::LINNAEANSAMSARA_PREFIX,
            'LINNAEANSAMSARA_POSTS_VALIDATION'      => self::LINNAEANSAMSARA_POSTS_VALIDATION,
            'LINNAEANSAMSARA_TAXONOMIES_VALIDATION' => self::LINNAEANSAMSARA_TAXONOMIES_VALIDATION,
            'LINNAEANSAMSARA_PREFIX_VALIDATION'     => self::LINNAEANSAMSARA_PREFIX_VALIDATION,
            'LINNAEANSAMSARA_SUB_SETTINGS'          => self::LINNAEANSAMSARA_SUB_SETTINGS,
            'LINNAEANSAMSARA_SUB_SETTINGS_EN'       => self::LINNAEANSAMSARA_SUB_SETTINGS_EN,
            'ENCODING'                              => self::ENCODING,
        ];
    }
    /**
     * 定数返し
     *
     * @return array $c クラス内で宣言した定数を出力する
     */
    public function getConstant()
    {
        return $this->c;
    }
    /**
     * htmlspecialchars のラッパー関数
     *
     * esc_html ではクォートもエスケープされてしまうため、JS処理時は不都合がある
     *
     * @param string $str 文字列
     *
     * @return string $ANONYMOUS $str を エスケープして返す(クォートを除く)
     */
    public function _h( $str )
    {
        return htmlspecialchars( $str, ENT_NOQUOTES, self::ENCODING );
    }
    /**
     * returnPostType: 投稿タイプを返す
     *
     * @return array $ANONYMOUS DBに保存された投稿タイプ、または空文字列
     */
    public function returnPostType()
    {
        return get_option( self::LINNAEANSAMSARA_POSTS_RADIOS ) ? get_option( self::LINNAEANSAMSARA_POSTS_RADIOS ) : '';
    }
    /**
     * returnTaxonomy: タクソノミーを返す
     *
     * @return array $ANONYMOUS DBに保存されたタクソノミー、または空文字列
     */
    public function returnTaxonomy()
    {
        return get_option( self::LINNAEANSAMSARA_TAXONOMIES_RADIOS ) ? get_option( self::LINNAEANSAMSARA_TAXONOMIES_RADIOS ) : '';
    }
    /**
     * returnPrefix: プレフィックスを返す
     *
     * @return array $ANONYMOUS DBに保存されたタクソノミー、または初期値 'linnaean'
     */
    public function returnPrefix()
    {
        return get_option( self::LINNAEANSAMSARA_PREFIX ) ? get_option( self::LINNAEANSAMSARA_PREFIX ) : 'linnaean';
    }
    /**
     * インスタンス返し
     *
     * @param  class $i        自分自身。インスタンス化されたInitialize
     *
     * @return array $instance コンストラクタで宣言した文字列の名前のファイルを探し、require_once して new してインスタンスを返す
     */
    public function getInstance( $i )
    {
        $instance = [];
        try {
            $c = self::getConstant();
            if( require_once( __DIR__ . '/src/bulkadd.php' ) ) {
                $instance['Bulkadd'] = new \LinnaeanSamsara\app\src\Bulkadd( $c, $i );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: bulkadd.php' );
            }
            if( require_once( __DIR__ . '/src/hook-insert-update.php' ) ) {
                $instance['InsertUpdate'] = new \LinnaeanSamsara\app\src\InsertUpdate( $c, $i );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: hook-insert-update.php' );
            }
            if( require_once( __DIR__ . '/src/hook-delete.php' ) ) {
                $instance['Delete'] = new \LinnaeanSamsara\app\src\Delete( $c, $i );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: hook-delete.php' );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $instance;
    }
}
