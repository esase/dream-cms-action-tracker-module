<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
            $statement->execute();

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
     * @return \Zend\Paginator\Paginator
     */
    public function getActions($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
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
 
    /**
     * Get actions log
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      array modules
     * @return \Zend\Paginator\Paginator
     */
    public function getActionsLog($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'registered'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'action_tracker_log'])
            ->columns([
                'id',
                'description',
                'description_params',
                'registered'
            ])
            ->join(
                ['b' => 'application_event'],
                'a.action_id = b.id',
                []
            )
            ->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'module' => 'name'
                ]
            )
            ->order($orderBy . ' ' . $orderType);
                            
        // filter by modules
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $select->where->in('b.module', $filters['modules']);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Delete action log
     *
     * @param integer $actionId
     * @return boolean|string
     */
    public function deleteActionLog($actionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('action_tracker_log')
                ->where([
                    'id' => $actionId
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

        // fire the delete action log event
        ActionTrackerEvent::fireDeleteActionEvent($actionId);

        return $result->count() ? true : false;
    }
}