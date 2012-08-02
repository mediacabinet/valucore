<?php
namespace Foaf\Service\Feature;

interface DefinitionProviderInterface
{
    
    /**
     * Retrieve service version
     * 
     * @return string
     */
    public static function version();
    
	/**
	 * Define service
	 * 
	 * @return \Foaf\Service\Definition Service definition
	 */
	public function define();
}