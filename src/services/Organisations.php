<?php
namespace verbb\xero\services;

use verbb\xero\events\OrganisationEvent;
use verbb\xero\models\Organisation;
use verbb\xero\records\Organisation as OrganisationRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use yii\base\Component;

use Exception;
use Throwable;

use verbb\auth\Auth;

class Organisations extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_ORGANISATION = 'beforeSaveOrganisation';
    public const EVENT_AFTER_SAVE_ORGANISATION = 'afterSaveOrganisation';
    public const EVENT_BEFORE_DELETE_ORGANISATION = 'beforeDeleteOrganisation';
    public const EVENT_AFTER_DELETE_ORGANISATION = 'afterDeleteOrganisation';
    public const CONNECTION_LOCK_TIMEOUT = 10;


    // Properties
    // =========================================================================

    private ?MemoizableArray $_organisations = null;


    // Public Methods
    // =========================================================================

    public function getAllOrganisations(): array
    {
        return $this->_organisations()->all();
    }

    public function getAllEnabledOrganisations(): array
    {
        return $this->_organisations()->where('enabled', true)->all();
    }

    public function getAllConfiguredOrganisations(): array
    {
        $organisations = [];

        foreach ($this->getAllEnabledOrganisations() as $organisation) {
            if ($organisation->isConfigured()) {
                $organisations[] = $organisation;
            }
        }

        return $organisations;
    }

    public function getAllOrganisationsByParams(array $params): array
    {
        $limit = ArrayHelper::remove($params, 'limit');

        $query = $this->_createOrganisationQuery()->where($params)->limit($limit)->all();

        return array_map(function($result) {
            return new Organisation($result);
        }, $query);
    }

    public function getOrganisationById(int $id, bool $enabledOnly = false, bool $connectedOnly = false): ?Organisation
    {
        $organisation = $this->_organisations()->firstWhere('id', $id);

        if ($organisation && (($enabledOnly && !$organisation->enabled) || ($connectedOnly && !$organisation->isConnected()))) {
            return null;
        }

        return $organisation;
    }

    public function getOrganisationByParams(array $params): ?Organisation
    {
        $params['limit'] = 1;

        return $this->getAllOrganisationsByParams($params)[0] ?? null;
    }

    public function saveOrganisation(Organisation $organisation, bool $runValidation = true): bool
    {
        $isNewOrganisation = !$organisation->id;

        // Fire a 'beforeSaveOrganisation' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ORGANISATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ORGANISATION, new OrganisationEvent([
                'organisation' => $organisation,
                'isNew' => $isNewOrganisation,
            ]));
        }

        if (!$organisation->beforeSave($isNewOrganisation)) {
            return false;
        }

        if ($runValidation && !$organisation->validate()) {
            Craft::info('Organisation not saved due to validation error.', __METHOD__);
            return false;
        }

        $organisationRecord = $this->_getOrganisationRecordById($organisation->id);
        $organisationRecord->enabled = $organisation->enabled;
        $organisationRecord->createPayments = $organisation->createPayments;
        $organisationRecord->updateInventory = $organisation->updateInventory;
        $organisationRecord->accountSales = $organisation->accountSales;
        $organisationRecord->accountReceivable = $organisation->accountReceivable;
        $organisationRecord->accountShipping = $organisation->accountShipping;
        $organisationRecord->accountRounding = $organisation->accountRounding;
        $organisationRecord->accountDiscounts = $organisation->accountDiscounts;
        $organisationRecord->accountAdditionalFees = $organisation->accountAdditionalFees;
        $organisationRecord->accountLineItemTax = $organisation->accountLineItemTax;
        $organisationRecord->accountInvoiceStatus = $organisation->accountInvoiceStatus;

        if ($isNewOrganisation) {
            $maxSortOrder = (new Query())
                ->from(['{{%xero_organisations}}'])
                ->max('[[sortOrder]]');

            $organisationRecord->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $organisationRecord->save(false);

        if (!$organisation->id) {
            $organisation->id = $organisationRecord->id;
        }

        $organisation->afterSave($isNewOrganisation);

        // Fire an 'afterSaveOrganisation' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ORGANISATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ORGANISATION, new OrganisationEvent([
                'organisation' => $organisation,
                'isNew' => $isNewOrganisation,
            ]));
        }

        return true;
    }

    public function reorderOrganisations(array $organisationIds): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($organisationIds as $organisationOrder => $organisationId) {
                $organisationRecord = $this->_getOrganisationRecordById($organisationId);
                $organisationRecord->sortOrder = $organisationOrder + 1;
                $organisationRecord->save();
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    public function deleteOrganisationById(int $organisationId): bool
    {
        $organisation = $this->getOrganisationById($organisationId);

        if (!$organisation) {
            return false;
        }

        return $this->deleteOrganisation($organisation);
    }

    public function deleteOrganisation(Organisation $organisation): bool
    {
        if (!$organisation->id) {
            $organisation->addError('id', Craft::t('commerce-xero', 'Couldn’t delete organisation.'));
            return false;
        }

        $mutex = Craft::$app->getMutex();
        $lockName = $this->getConnectionLockName($organisation->id);

        if (!$mutex->acquire($lockName, self::CONNECTION_LOCK_TIMEOUT)) {
            $organisation->addError('id', Craft::t('commerce-xero', 'This organisation is already being updated. Please try again.'));
            return false;
        }

        try {
            return $this->_deleteOrganisation($organisation);
        } finally {
            $mutex->release($lockName);
        }
    }

    public function getConnectionLockName(int $organisationId): string
    {
        return sprintf('commerce-xero:connection:%d', $organisationId);
    }


    // Private Methods
    // =========================================================================

    private function _deleteOrganisation(Organisation $organisation): bool
    {
        // Fire a 'beforeDeleteOrganisation' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ORGANISATION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ORGANISATION, new OrganisationEvent([
                'organisation' => $organisation,
            ]));
        }

        // Keep retry credentials and the local organisation until Xero confirms revocation.
        if (!$organisation->revokeConnection()) {
            $organisation->addError('id', Craft::t('commerce-xero', 'Couldn’t disconnect this organisation from Xero. Please try again.'));
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Auth::getInstance()->getTokens()->deleteTokenByOwnerReference('commerce-xero', (string)$organisation->id);

            $deleted = Craft::$app->getDb()->createCommand()
                ->delete('{{%xero_organisations}}', ['id' => $organisation->id])
                ->execute();

            if (!$deleted) {
                $transaction->rollBack();
                $organisation->addError('id', Craft::t('commerce-xero', 'Couldn’t delete organisation.'));
                return false;
            }

            $transaction->commit();
        } catch (Throwable $e) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            throw $e;
        }

        // Fire an 'afterDeleteOrganisation' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ORGANISATION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ORGANISATION, new OrganisationEvent([
                'organisation' => $organisation,
            ]));
        }

        // Clear caches
        $this->_organisations = null;

        return true;
    }

    private function _organisations(): MemoizableArray
    {
        if (!isset($this->_organisations)) {
            $this->_organisations = new MemoizableArray(
                $this->_createOrganisationQuery()->all(),
                fn(array $result) => new Organisation($result),
            );
        }

        return $this->_organisations;
    }

    private function _createOrganisationQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'enabled',
                'createPayments',
                'updateInventory',
                'accountSales',
                'accountReceivable',
                'accountShipping',
                'accountRounding',
                'accountDiscounts',
                'accountAdditionalFees',
                'accountLineItemTax',
                'accountInvoiceStatus',
                'sortOrder',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%xero_organisations}}'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getOrganisationRecordById(int $organisationId = null): ?OrganisationRecord
    {
        if ($organisationId !== null) {
            $organisationRecord = OrganisationRecord::findOne(['id' => $organisationId]);

            if (!$organisationRecord) {
                throw new Exception(Craft::t('commerce-xero', 'No organisation exists with the ID “{id}”.', ['id' => $organisationId]));
            }
        } else {
            $organisationRecord = new OrganisationRecord();
        }

        return $organisationRecord;
    }

}
