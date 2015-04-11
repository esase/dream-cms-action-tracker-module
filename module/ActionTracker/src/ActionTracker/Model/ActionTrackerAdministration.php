<?php
namespace ActionTracker\Model;

use ActionTracker\Event\ActionTrackerEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;
use Exception;

class ActionTrackerAdministration extends ActionTrackerBase
{
    /**
     * Deactivate action
     *
     * @param integer $actionId
     * @return boolean|string
     */
    public function deactivateAction($actionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('action_tracker_connection')
                ->where([
                    'action_id' => $actionId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the deactivate action event
        ActionTrackerEvent::fireDeactivateActionEvent($actionId);
        return true;
    }

    /**
     * Activate action
     *
     * @param integer $actionId
     * @return boolean|string
     */
    public function activateAction($actionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // check the existing connection
            $select = $this->select();
            $select->from('action_tracker_connection')
                ->columns([
                    'id'
                ])
                ->where([
                    'action_id' => $actionId
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            // add a new connection
            if (!$result->current()) {
                $insert = $this->insert()
                    ->into('action_tracker_connection')
                    ->values([
                        'action_id' => $actionId
                    ]);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the activate action event
        ActionTrackerEvent::fireActivateActionEvent($actionId);
        return true;
    }

    /**
     * Get actions
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      array modules
     *      string status
     * @return object Paginator
     */
    public function getActions($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = [
            'id',
            'connection'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'application_event'])
            ->columns([
                'id',
                'description'
            ])
            ->join(
                ['b' => 'application_module'],
                new Expression('a.module = b.id and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'module' => 'name'
                ]
            )
            ->join(
                ['c' => 'action_tracker_connection'],
                'a.id = c.action_id',
                [
                    'connection' => 'id'
                ],
                'left'
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by modules
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $select->where->in('a.module', $filters['modules']);
        }

        // filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'deactivated' :
                    $select->where->IsNull('c.id');
                    break;
                case 'activated' :
                default :
                    $select->where->IsNotNull('c.id');
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}