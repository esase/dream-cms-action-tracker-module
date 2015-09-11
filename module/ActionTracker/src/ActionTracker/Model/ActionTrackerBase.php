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

use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;
use Localization\Service\Localization as LocalizationService;
use Application\Service\ApplicationSetting as SettingService;
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

        // send an email notification about add the adding new action
        if (SettingService::getSetting('action_tracker_send_actions')) {
            $defaultLocalization = LocalizationService::getDefaultLocalization();
            $actionDescription = vsprintf($this->serviceLocator->
                    get('Translator')->translate($description, 'default', $defaultLocalization['locale']), $params);

            EmailNotificationUtility::sendNotification(SettingService::getSetting('application_site_email'),
                SettingService::getSetting('action_tracker_title', $defaultLocalization['language']),
                SettingService::getSetting('action_tracker_message', $defaultLocalization['language']), [
                    'find' => [
                        'Action',
                        'Date'
                    ],
                    'replace' => [
                        $actionDescription,
                        $this->serviceLocator->get('viewHelperManager')->
                                get('applicationDate')->__invoke(time(), [], $defaultLocalization['language'])
                    ]
                ]);
        }

        return true;
    }

    /**
     * Get activated actions
     *
     * @return \Zend\Db\ResultSet\ResultSet
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