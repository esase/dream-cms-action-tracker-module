<?php
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

        // send an email notification about add the new action
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