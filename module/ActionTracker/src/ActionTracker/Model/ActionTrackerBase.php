<?php
namespace ActionTracker\Model;

use Application\Utility\ApplicationErrorLogger;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\ResultSet\ResultSet;
use Exception;

class ActionTrackerBase extends ApplicationAbstractBase
{
    /**
     * Log action
     *
     * @param integer $actionId
     * @param string $description
     * @param array $params
     * @return boolean|string
     */
    public function logAction($actionId, $description, array $params = [])
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('action_tracker_log')
                ->values([
                    'action_id' => $actionId,
                    'description' => $description,
                    'description_params' => serialize($params),
                    'registered' => time()
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get activated actions
     *
     * @return object ResultSet
     */
    public function getActivatedActions()
    {
        $select = $this->select();
        $select->from(['a' => 'action_tracker_connection'])
            ->columns([
                'action_id'
            ])
            ->join(
                ['b' => 'application_event'],
                'a.action_id = b.id',
                [
                    'name'
                ]
            )
            ->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            );

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }
}