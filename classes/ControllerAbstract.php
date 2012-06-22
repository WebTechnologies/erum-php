<?php
namespace Erum;

/**
 * Abstract controller implementation.
 *
 * @package Erum
 * @subpackage Core
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
abstract class ControllerAbstract
{
    /**
     * Current request object
     *
     * @var \Erum\Request
     */
    protected $request;
    
    /**
     * Current router object
     *
     * @var \Erum\Router
     */
    protected $router;

    /**
     * constructor
     * 
     * @param \Erum\Router $router 
     */
    final public function __construct( \Erum\Router $router )
    {
        $this->request = $router->request;
        $this->router = $router;
    }

    /**
     * Defines what action will be fired by default 
     * 
     * @return string
     */
    public function getDefaultAction()
    {
        return 'index';
    }

    public function onBeforeAction()
    {
        
    }

    public function onAfterAction()
    {
        
    }

    public function onBeforeAsync()
    {
        
    }

    public function onAfterAsync()
    {
        
    }
    
    final public function execute( $action, array $args = null )
    {
        if( null === $args ) $args = array();
        
        $actionType = $this->request->async ? 'Async' : 'Action';
        
        if ( !method_exists( $this, $action . $actionType ) )
            throw new \Exception( 'Action ' . $action . $actionType . ' not found in ' . get_class( $this ) );
        
        $reflection = new \ReflectionMethod( $this, $action . $actionType );
        
        $this->{'onBefore' . $actionType}( $action );
        
        $result = $reflection->invokeArgs( $this, $args );
        
        $this->{'onAfter' . $actionType}( $result );
        
    }

}
