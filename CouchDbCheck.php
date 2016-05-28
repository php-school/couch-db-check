<?php

namespace PhpSchool\CouchDb;

use Doctrine\CouchDB\CouchDBClient;
use Doctrine\CouchDB\HTTP\HTTPException;
use PhpSchool\PhpWorkshop\Check\ListenableCheckInterface;
use PhpSchool\PhpWorkshop\Event\CliExecuteEvent;
use PhpSchool\PhpWorkshop\Event\Event;
use PhpSchool\PhpWorkshop\Event\EventDispatcher;
use PhpSchool\PhpWorkshop\Result\Failure;
use PhpSchool\PhpWorkshop\Result\Success;

/**
 * @package PhpSchool\SimpleMath\Check
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CouchDbCheck implements ListenableCheckInterface
{
    /**
     * @var string
     */
    private static $studentDb = 'phpschool-student';

    /**
     * @var string
     */
    private static $solutionDb = 'phpschool';

    /**
     * Return the check's name
     *
     * @return string
     */
    public function getName()
    {
        return 'Couch DB Verification Check';
    }

    /**
     * This returns the interface the exercise should implement
     * when requiring this check
     *
     * @return string
     */
    public function getExerciseInterface()
    {
        return CouchDbExerciseCheck::class;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function attach(EventDispatcher $eventDispatcher)
    {
        $studentClient = CouchDBClient::create(['dbname' => static::$studentDb]);
        $solutionClient = CouchDBClient::create(['dbname' => static::$solutionDb]);

        $studentClient->createDatabase($studentClient->getDatabase());
        $solutionClient->createDatabase($solutionClient->getDatabase());

        $eventDispatcher->listen('verify.start', function (Event $e) use ($studentClient, $solutionClient) {
            $e->getParameter('exercise')->seed($studentClient);
            $this->replicateDbFromStudentToSolution($studentClient, $solutionClient);
        });

        $eventDispatcher->listen('run.start', function (Event $e) use ($studentClient) {
            $e->getParameter('exercise')->seed($studentClient);
        });

        $eventDispatcher->listen('cli.verify.solution-execute.pre', function (CliExecuteEvent $e) {
            $e->prependArg('phpschool');
        });

        $eventDispatcher->listen(
            ['cli.verify.user-execute.pre', 'cli.run.user-execute.pre'],
            function (CliExecuteEvent $e) {
                $e->prependArg('phpschool-student');
            }
        );

        $eventDispatcher->insertVerifier('verify.finish', function (Event $e) use ($studentClient) {
            $verifyResult = $e->getParameter('exercise')->verify($studentClient);

            if (false === $verifyResult) {
                return Failure::fromNameAndReason($this->getName(), 'Database verification failed');
            }

            return Success::fromCheck($this);
        });

        $eventDispatcher->listen(
            [
                'cli.verify.solution-execute.fail',
                'verify.finish',
                'run.finish'
            ],
            function (Event $e) use ($studentClient, $solutionClient) {
                $studentClient->deleteDatabase(static::$studentDb);
                $solutionClient->deleteDatabase(static::$solutionDb);
            }
        );
    }

    /**
     * @param CouchDBClient $studentClient
     * @param CouchDBClient $solutionClient
     * @throws \Doctrine\CouchDB\HTTP\HTTPException
     */
    private function replicateDbFromStudentToSolution(CouchDBClient $studentClient, CouchDBClient $solutionClient)
    {
        $response = $studentClient->allDocs();

        if ($response->status !== 200) {
            //should maybe throw an exception - but what should we print?
            return;
        }

        foreach ($response->body['rows'] as $row) {
            $doc = $row['doc'];

            $data = array_filter($doc, function ($key) {
                return !in_array($key, ['_id', '_key']);
            }, ARRAY_FILTER_USE_KEY);

            try {
                $solutionClient->putDocument(
                    $data,
                    $doc['_id'],
                    $doc['_rev']
                );
            } catch (HTTPException $e) {
            }
        }
    }
}
