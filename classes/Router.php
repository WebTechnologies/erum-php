<?php
namespace Erum;

/**
 * Handles route routine
 * 
 * @package Erum
 * @subpackage Core
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * 
 * @property \Erum\Request $request
 * @property string $section
 * @property string $controller Current controller full class name
 * @property string $action Current action name
 * @property array $requestRemains
 */
class Router
{
    /**
     * Current request object
     * 
     * @var \Erum\Request
     */
    protected $request;
    protected $section = '';
    protected $controller;
    protected $action;
    protected $requestRemains = array( );
    
    protected static $headerByCode = array(
        403 => 'HTTP/1.1 403 Forbidden',
        404 => 'HTTP/1.1 404 Not Found',
    );

    public function __construct( Request $request )
    {
        $this->request = $request;
    }

    /**
     * Performs a request by given Request object data
     */
    public function performRequest()
    {
        if ( isset( \Erum::config()->routes[$this->request->uri] ) )
        {
            self::redirect( '/' . trim( \Erum::config()->routes[$this->request->uri], '/' ), false, 200 );
        }

        if( false === ( @list( $this->controller, $this->action ) = self::getController( $this->request->uri, $this->requestRemains ) ) )
        {
            throw new \Erum\Exception( 'Could not find controller class name!' );
        }
        
        /* @var $controller ControllerAbstract */        
        $controller = new $this->controller( $this );
        
        if( null === $this->action )
        {
            $this->action = $controller->getDefaultAction();
        }
                
       $controller->execute( $this->action, $this->requestRemains );

        unset( $controller );
    }
    
    /**
     * Find valid controller class name and action from uri.
     * Returns array ( 0 => Controller class, 1=> action name )
     * 
     * @param type $uri
     * @param array $remains
     * @return array | false
     */
    public static function getController( $uri, array &$remains = null )
    {
        $remains = array();
        $controller = null;
        $action = null;
        
        list( $uri ) = explode( '?', $uri );
        
        $requestArr = array_filter( explode( '/', trim( $uri, '/' ) ) );
        
        if( !sizeof( $requestArr ) ) $requestArr[] = 'index';

        $namespace = '\\' . \Erum::config()->application['namespace'] . '\\';
        
        while ( $chunk = array_shift( $requestArr ) )
        {
            if ( class_exists( $namespace . ucfirst( $chunk ) . 'Controller' ) )
            {
                $controller = $namespace . ucfirst( $chunk ) . 'Controller';
            }
            elseif( class_exists( $namespace . ucfirst( $chunk ) . '\\IndexController' ) )
            {
                $controller = $namespace . ucfirst( $chunk ) . '\\IndexController';
            }
            elseif ( null !== $controller )
            {
                $remains[] = $chunk;
                $remains = array_merge( $remains, $requestArr );
                break;
            }
            else
            {
                $remains[] = $chunk;
                
                if( !sizeof( $requestArr ) && 1 === sizeof( $remains ) )
                {
                    $controller = $namespace . 'IndexController';
                }
            }
            
            $namespace .= ucfirst( $chunk ) . '\\';
        }
        
        if( $controller )
        {
            if ( isset( $remains[0] ) )
            {
                $action = array_shift( $remains );
            }

            return array( $controller, $action );
        }
        
        return false;
    }
    
    /**
     * Build correct uri path by controller
     * 
     * @param ControllerAbstract || string $controller - may be controller instance, or name
     * @param string $action
     * @param array $args 
     * @return string
     */
    public static function getPath( $controller, $action = null, array $args = null )
    {
        if( is_object( $controller ) )
        {
            if( ! $controller instanceof \Erum\ControllerAbstract )
                throw new \Erum\Exception( 'Controller must be string or \Erum\ControllerAbstract instance.');
        }
        elseif( !is_string( $controller ) )
        {
            throw new \Erum\Exception( 'Controller must be string or \Erum\ControllerAbstract instance, ' . gettype( $controller ) . ' given.'  );
        }
        else
        {
            $controller = new $controller( new self( \Erum\Request::current() ) );
        }
        
        $pathArray = array_filter( explode( '\\', strtolower( get_class( $controller ) ) ) );
        
        // if given controller not from current application namespace - ignoring.
        // @TODO review this part
        if( strtolower( \Erum::config()->application['namespace'] ) != array_shift( $pathArray ) )
        {
            return false;
        }
        
        $controllerName = str_ireplace( 'Controller', '', array_pop( $pathArray ) );
        
        if( strtolower( $controllerName ) != 'index' )
        {
            array_push( $pathArray, $controllerName );
        }
        
        if( null !==$action && $action != $controller->getDefaultAction() )
        {
            array_push( $pathArray, $action );
        }
        
        if( null !== $args )
        {
            $pathArray += $args;
        }
        
        return '/' . implode( '/', $pathArray );
        
    }

    /**
     * Redirect.
     * 
     * @param string $url
     * @param boolean $isExternal
     * @param int $statusCode 
     */
    public static function redirect( $url, $isExternal = false, $statusCode = 200 )
    {
        if ( array_key_exists( $statusCode, self::$headerByCode ) )
        {
            header( self::$headerByCode[$statusCode] );
        }

        if ( $isExternal )
        {
            header( 'Location: ' . $url );
            exit();
        }

        // Cycling check
        if( Request::initial() !== Request::current() && $url === Request::current()->rawUrl )
        {
            throw new \Erum\Exception( 'Infinity loop detected.' );
        }
        
        $router = new self( \Erum\Request::factory( $url, Request::current()->method ) );
        
        try
        {
            $router->performRequest();
        }
        catch ( \Exception $e )
        {
            throw new \Exception( $e->getMessage()
                    . ' on line ' . $e->getLine()
                    . ' in file "' . $e->getFile() . '"', (int)$e->getCode(), $e );
        }

        exit( 0 );
    }

    public function __get( $var )
    {
        if ( isset( $this->$var ) )
        {
            return $this->$var;
        }
        else
        {
            throw new \Exception( 'Requested variable "' . $var . '" not exist!' );
        }
    }

}
