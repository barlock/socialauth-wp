<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

require_once(realpath( dirname( __FILE__ ) ) ."/../../../../../wp-load.php");

/**
 * HybridAuth storage manager
 */
class Hybrid_Storage
{
    private static $HA_SESSION = "HA::SESSION";
    private $EXPIRES; //10 hours

    function __construct()
    {
        if ( ! session_id() ){
            if( ! session_start() ){
                throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled.", 1 );
            }
        }

        $this->EXPIRES = time() + 10 * 60 * 60; //10 hours

        if(!isset($_COOKIE[self::$HA_SESSION])) {
            $id = uniqid();
            setcookie(self::$HA_SESSION, $id, $this->EXPIRES, "/", $_SERVER["HTTP_HOST"], true, true);
            $_COOKIE[self::$HA_SESSION] = $id;
        }

        $this->config( "php_session_id", session_id() );
        $this->config( "version", Hybrid_Auth::$version );
    }

    private function saveSession($session) {
        set_transient($_COOKIE[self::$HA_SESSION], $session, $this->EXPIRES);
    }

    private function getSession() {
        $session = get_transient($_COOKIE[self::$HA_SESSION]);
        if($session) {
            return $session;
        }
        return ARRAY();
    }

    public function config($key, $value=null)
    {
        $session = $this->getSession();

        $key = strtolower( $key );

        if( $value ){
            $session["HA::CONFIG"][$key] = $value;
        }
        elseif( isset( $session["HA::CONFIG"][$key] ) ){
            return $session["HA::CONFIG"][$key];
        }

        $this->saveSession($session);
        return NULL;
    }

    public function get($key)
    {
        $session = $this->getSession();
        $key = strtolower( $key );

        if( isset( $session["HA::STORE"], $session["HA::STORE"][$key] ) ){
            return $session["HA::STORE"][$key];
        }

        return NULL;
    }

    public function set( $key, $value )
    {
        $session = $this->getSession();
        $key = strtolower( $key );

        $session["HA::STORE"][$key] = $value;

        $this->saveSession($session);
    }

    function clear()
    {
        $session = $this->getSession();
        $session["HA::STORE"] = ARRAY();
        $this->saveSession($session);
    }

    function delete($key)
    {
        $session = $this->getSession();
        $key = strtolower( $key );

        if( isset( $session["HA::STORE"], $session["HA::STORE"][$key] ) ){
            unset( $session["HA::STORE"][$key] );
            $this->saveSession($session);
        }
    }

    function deleteMatch($key)
    {
        $session = $this->getSession();
        $key = strtolower( $key );

        if( isset( $session["HA::STORE"] ) && count( $session["HA::STORE"] ) ) {
            foreach( $session["HA::STORE"] as $k => $v ){
                if( strstr( $k, $key ) ){
                    unset( $session["HA::STORE"][ $k ] );
                }
            }
        }

        $this->saveSession($session);
    }

    function getSessionData()
    {
        $session = $this->getSession();
        if( isset( $session["HA::STORE"] ) ){
            return serialize($session["HA::STORE"]);
        }

        return NULL;
    }

    function restoreSessionData( $sessiondata = NULL )
    {
        $session = $this->getSession();
        $session["HA::STORE"] = unserialize( $sessiondata );
        $this->saveSession($session);
    }
}
