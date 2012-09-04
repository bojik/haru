<?php
require_once 'phing/Task.php';
require_once 'phing/tasks/system/ExecTask.php';

class BuildUpdateTask extends Task
{
    const TRUNK = 'trunk';
    
    protected $_types = array(
        'svn' => array(
            self::TRUNK => 'trunk'
            , 'check_file' => '.svn'
        )
        , 'hg' => array(
              self::TRUNK => 'default'
            , 'check_file' => '.hg'
        )
        , 'git' => array(
              self::TRUNK => 'master'
            , 'check_file' => '.git'
        )
    );
    
    protected $_branch = '';
    protected $_buildDir;
    
    public function main()
	{
		$this->log( 'Trying to update main build path' );
        $this->_initBuildDir();
        $type = $this->_getType();
        $method = sprintf('_update%sBuilds', ucfirst( $type ) );
        $this->$method();
        
        $this->log( 'Finish updating main build path successful' );
    }
    
    public function setBranch( $branch = '' )
	{
		$this->_branch = strval($branch);
	}
    
    public function setTrunk( $val )
    {
        $this->setBranch( self::TRUNK );
    }
    
    protected function _checkBranch( $type )
    {
        if ( $this->_branch == self::TRUNK )
        {
            $this->_branch = $this->_types[ $type ][ self::TRUNK ];
        }
        
    }
    
    protected function _initBuildDir()
    {
        $props = $this->project->getProperties();
        
        if ( !isset( $props['build.dir.root'] ) )
        {
            throw new BuildException( 'Build dir is undefined' );
        }
        
        $this->_buildDir = strval( $props['build.dir.root'] );
    }
    
    protected function _getType()
    {
        foreach( $this->_types as $type => $config)
        {
            $fname = sprintf( '%s/%s', $this->_buildDir, $config['check_file'] );
            
            $this->log($fname);
            
            if ( file_exists( $fname ) )
            {
                return $type;
            }
        }
        
        throw new BuildException( 'CVS type is unknown' );
    }
    
    protected function _updateSvnBuilds()
    {
        if ( empty( $this->_branch ) )
        {
            $command = 'svn up {build_dir};';
        }
        else
        {
            $command = 'svn switch {branch} {build_dir};';    
        }
        
        $this->_exec( $command );
    }
    
    protected function _exec( $command )
    {
        $replaces = array('{build_dir}' => $this->_buildDir, '{branch}' => $this->_branch);
        $command = str_replace( array_keys($replaces), array_values($replaces), $command );
        
        $this->log($command);
        
        $returnProp = 'build.update.return';
        $outputProp = 'build.update.output';
        $obj = new ExecTask();
        $obj->setProject( $this->project );
        $obj->setCommand( $command );
        $obj->setLogoutput( true );
        $obj->setReturnProperty( $returnProp );
        $obj->setOutputProperty( $outputProp );
        $obj->setPassthru( true );
        
        
        $obj->main();
    }
    
    protected function _updateHgBuilds()
    {
        $command = 'cd {build_dir}; hg pull; hg update {branch}; cd -;';
        $this->_exec( $command );
    }
    
    protected function _updateGitBuilds()
    {
        $command = 'cd ${build.dir.root}; git pull; git checkout {branch}; cd -;';
        $this->_exec( $command );
    }
}
