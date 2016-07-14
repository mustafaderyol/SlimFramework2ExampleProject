<?php

namespace Utils;

use MongoId;

/**
 * Some versions of mongo support "MongoDB\BSON\ObjectID"
 *
 * We extend to mongo ıd so you change your mongo id object 
 * depending to your server configuration
 * 
 * http://php.net/manual/tr/class.mongoid.php
 * 
 */
class MongoHelper {

    /**
     * Mongo id
     * 
     * @param string $id id
     * 
     * @return object MongoId
     */
    public static function id($id)
    {
        return new MongoId((string)$id);
    }

}