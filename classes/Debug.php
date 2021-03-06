<?php
namespace Erum;

class Debug
{

    protected $points;
    protected $pointsMap;
    protected $pointId;

    static protected $instance;

    static public function instance()
    {
        if ( is_null( self::$instance ) )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    static public function coverPrint( $var, $message = '', $return = true )
    {
        $output = highlight_string( "<?php\n" . str_replace( "=> \n", '=>', trim( var_export( $var, true ), "'" ) ), true );
        $output = str_ireplace( '<span style="color: #0000BB">&lt;?php<br /></span>', '', $output );
        $output = '<div style="padding:2px;background-color:999999;margin:1px;">
			<h4 style="width:100%;background-color:999999;margin:0px;">
			<a href="#" style="color:FFFFFF" onclick="
			var el = document.getElementById(\'' . md5( print_r( $var, true ) ) . '\');
			if(el.style.display!=\'none\'){el.style.display=\'none\';}else{el.style.display=\'\';}">'
                . $message . '</a></h4><div id="' . md5( print_r( $var, true ) ) . '"
			style="display:block;padding-left: 10px;background-color:#F6F6F6"><pre style="margin:0px;">'
                . $output . '</pre></div></div>';

        if ( $return )
        {
            return $output;
        }
        else
        {
            echo $output;
        }
    }

    public function enterPoint( $point )
    {
        if ( !isset( $this->pointsMap[$point] ) )
        {
            ++$this->pointId;

            $this->pointsMap[$point] = $this->pointId;

            $this->points[$this->pointId] = $this->getPointState();
            $this->points[$this->pointId]['name'] = $point;
            $this->points[$this->pointId]['executed'] = 1;
        }
        else
        {
            $this->points[$this->pointsMap[$point]]['executed']++;
        }
    }

    public function exitPoint( $point )
    {
        if ( $this->points[$this->pointsMap[$point]]['executed'] == 1 )
        {
            $this->points[$this->pointsMap[$point]]
                    = array_merge_recursive( $this->points[$this->pointsMap[$point]], $this->getPointState() );
        }
    }

    public function echoDump()
    {
        $object['childs'] = $this->points;

        foreach ( $object['childs'] as &$point )
        {
            $point['time'] = round( $point['time'][1] - $point['time'][0], 6 );
            $point['mem'] = $point['mem'][1] - $point['mem'][0];
            $point['files'] = $point['files'][1] - $point['files'][0];
        }

        echo self::coverPrint( $object, __METHOD__ );
    }

    private function __construct()
    {
        
    }

    private function getPointState()
    {
        $pointState = array(
            'time' => round( microtime( 1 ), 6 ),
            'mem' => memory_get_usage(),
            'files' => sizeof( get_included_files() ),
        );

        return $pointState;
    }

}