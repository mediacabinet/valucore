<?php
namespace Valu\Service\Resource;

interface ResourceServiceInterface
{
    /**
     * Create a new resource
     * 
     * @param array $specs Resource specs
     * @return mixed
     */
    public function create(array $specs);
    
    /**
     * Update resource matched by query
     * 
     * @param array $query
     * @param array $specs
     * @return boolean
     */
    public function update(array $query, array $specs);
    
    /**
     * Batch-update resources matched by query
     * 
     * @param array $query
     * @param array $specs
     * @return array         Status array, where keys are resource IDs and
     *                       corresponding values error codes, where 0 means
     *                       no error
     */
    public function updateMany($query, array $specs);
    
    /**
     * Remove a single resource matched by query
     * 
     * @param array $query
     * @return boolean        True if resource was found and removed,
     *                        false if resource could not be found or removed
     */
    public function remove($query);
    
    /**
     * Remove all resources matched by query
     * 
     * @param array $query
     * @return array         Status array, where keys are resource IDs and
     *                       corresponding values error codes, where 0 means
     *                       no error
     */
    public function removeMany($query);
    
    /**
     * Find a single resource matched by query
     * 
     * @param array $query
     */
    public function find($query, array $specs = null);
    
    /**
     * Find all resources matched by query
     * 
     * @param array $query
     * @return array
     */
    public function findMany($query, array $specs = null);
}