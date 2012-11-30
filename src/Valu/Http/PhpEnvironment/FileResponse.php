<?php
namespace Valu\Http\PhpEnvironment;

use Zend\Http\PhpEnvironment\Response;

class FileResponse extends Response
{
    /**
     * File
     * 
     * @var string
     */
    protected $file = null;
    
    /**
     * Flag that indicates this file as a temporary
     * file that should be removed after it is outputted
     * 
     * @var boolean
     */
    protected $cleanup = false;

	/**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

	/**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Should the file be removed after it has
     * been sent?
     * 
     * @return boolean
     */
    public function getCleanup()
    {
        return $this->cleanup;
    }
    
    /**
     * Mark file as a temporary file
     * 
     * @param boolean $Cleanup
     */
    public function setCleanup($cleanup)
    {
        $this->cleanup = $cleanup;
    }
    
    
    public function setContent($content)
    {
        throw new \BadMethodCallException('Method setContent is not supported for FileResponse, use setFile instead');
    }
    
    /**
     * Send content
     *
     * @return Response
     */
    public function sendContent()
    {
        if ($this->contentSent()) {
            return $this;
        }
    
        if (file_exists($this->getFile())) {
            readfile($this->getFile());
        }
        
        $this->contentSent = true;
        return $this;
    }
}