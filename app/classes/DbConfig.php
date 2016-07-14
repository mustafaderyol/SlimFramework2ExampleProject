<?php

/**
 * Config class
 */
class DbConfig {

    protected $row = null;
    protected $container;
    protected $collection;

    /**
     * Contructor
     * 
     * @param object $container container
     */
    public function __construct($container)
    {
        $this->container  = $container;
        $this->collection = $container['database']->config;
    }

    /**
     * Returns to db config item value
     * 
     * @param string $item config item
     * 
     * @return mixed bool|string
     */
    public function get($item)
    {
        if (empty($this->row)) {  // Don't do query if we already had the config data.
            $this->row = $this->collection->findOne();
        }
        if (isset($this->row[$item])) {
            return $this->row[$item];
        }
        return false;
    }

}