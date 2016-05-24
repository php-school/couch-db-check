<?php

namespace PhpSchool\CouchDb;

use Doctrine\CouchDB\CouchDBClient;

/**
 * @package PhpSchool\CouchDb
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface CouchDbExerciseCheck
{
    /**
     * @param CouchDBClient $couchDbClient
     * @return void
     */
    public function seed(CouchDBClient $couchDbClient);

    /**
     * @param CouchDBClient $couchDbClient
     * @return bool
     */
    public function verify(CouchDBClient $couchDbClient);
}
