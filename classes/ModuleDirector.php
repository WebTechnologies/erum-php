<?php
namespace Erum;

/**
 * Module registry
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
final class ModuleDirector
{  
    private static $registered = array();

    public static function init( $moduleName )
    {
        $modulePath = \Erum::config()->application->modulesRoot . DS . strtolower( $moduleName );

        if( !file_exists( $modulePath ) ) throw new \Exception( 'Directory "' . $modulePath . '" for module "' . $moduleName . ' was not found.' );

        // Trying to load module bootstrap file ( init.php )
        if( file_exists( $modulePath . DS . 'init.php' ) && !isset( self::$registered[ $moduleName ] ) )
        {
            include_once $modulePath . DS . 'init.php';
        }
        
        \Erum::addIncludePath( $modulePath . DS . 'classes' );

        self::$registered[ strtolower( $moduleName ) ] = array();
    }

    public static function get( $moduleName, $configAlias = 'default' )
    {
        $moduleClass = '\\' . $moduleName;
        $moduleAlias = strtolower( $moduleName );

        if( !isset( self::$registered[ $moduleAlias ] ) )
        {
            self::init( $moduleName );
        }

        if( !isset( self::$registered[ $moduleAlias ][ $configAlias ] ) )
        {
            if( \Erum::config()->get( 'modules' )->get( $moduleAlias, true ) )
            {
                $config = \Erum::config()->get( 'modules' )->get( $moduleAlias );
                
                $currentConfig = false;
                
                if( is_array( current( $config ) ) )
                {
                    if( isset( $config[ $configAlias ] ) && is_array( $config[ $configAlias ] ) )
                    {
                        $currentConfig = $config[ $configAlias ];
                    }
                }
                elseif( !$configAlias || $configAlias == 'default' )
                {
                    
                    $currentConfig = $config;
                }

                if( !$currentConfig || !is_array( $currentConfig ) )
                {
                    throw new \Exception('Configuration "' . $configAlias . '" for module ' . $moduleName . ' is not exist or broken. ');
                }
            }
            else
            {
                $currentConfig = array();
            }

            self::$registered[ $moduleAlias ][ $configAlias ] = new $moduleClass( $currentConfig );

            unset( $moduleClass, $currentConfig, $config );
        }

        return self::$registered[ $moduleAlias ][ $configAlias ];
    }
    
    /**
     * Not implemented
     *
     * @param string $moduleName
     */
    public static function isExist( $moduleName )
    {

    }
    
    /**
     * Provides registered module names list
     * 
     * @return array
     */
    public static function getRegistered()
    {
        return array_keys( self::$registered );
    }
}
