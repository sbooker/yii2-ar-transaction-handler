<?php

declare(strict_types=1);

namespace Sbooker\TransactionManager\Yii2ActiveRecord;

use yii\db\ActiveRecord;

class UnitOfWork
{
    /** @var ActiveRecord[] */
    private $insertions = [];

    /** @var ActiveRecord[] */
    private $updates = [];

    /** @var ActiveRecord[] */
    private $deletions = [];

    public function persist(object $entity, EntityActiveRecordMapper $mapper): void
    {
        $oid = spl_object_hash($entity);
        if ($this->isScheduled($oid)) {
            throw new \RuntimeException('Already scheduled');
        }
        $this->scheduleForInsert($oid, $mapper->create($entity));
    }

    public function scheduleForDelete(object $entity): void
    {
        $oid = spl_object_hash($entity);
        if ($this->isScheduledForInsert($oid)) {
            $this->unScheduleForInsert($oid);
            return;
        }
        if (!$this->isScheduledForUpdate($oid)) {
            throw new \RuntimeException();
        }
        $this->deletions[$oid] = $this->updates[$oid];
        $this->unScheduleForUpdate($oid);
    }

    public function scheduleForUpdate(object $entity, EntityActiveRecordMapper $mapper): void
    {
        $oid = spl_object_hash($entity);
        if ($this->isScheduledForInsert($oid)) {
             $mapper->update($entity, $this->insertions[$oid]);
             return;
        }
        if ($this->isScheduledForUpdate($oid)) {
            $mapper->update($entity, $this->updates[$oid]);
            return;
        }

        throw new \RuntimeException('Entity must be gets first');
    }

    public function getForUpdate(ActiveRecord $activeRecord, EntityActiveRecordMapper $mapper): object
    {
        $entity = $mapper->hydrate($activeRecord);
        $oid = spl_object_hash($entity);
        $this->updates[$oid] = $activeRecord;

        return $entity;
    }

    public function commit(): void
    {
        foreach ($this->deletions as $deletion) {
            if (1 !== $deletion->delete()) {
                throw new \RuntimeException();
            }
        }
        foreach ($this->insertions as $insertion) {
            if (false === $insertion->insert(false)) {
                throw new \RuntimeException();
            }
        }
        foreach ($this->updates as $update) {
            if (false === $update->update(false)) {
                throw new \RuntimeException();
            }
        }

        $this->clear();
    }

    public function clear(): void
    {
        $this->insertions = $this->updates = $this->deletions = [];
    }

    private function scheduleForInsert(string $oid, ActiveRecord $activeRecord): void
    {
        $this->insertions[$oid] = $activeRecord;
    }

    private function unScheduleForInsert(string $oid): void
    {
        unset($this->insertions[$oid]);
    }

    private function unScheduleForUpdate(string $oid): void
    {
        unset($this->updates[$oid]);
    }

    private function isScheduled(string $oid): bool
    {
        return $this->isScheduledForInsert($oid) || $this->isScheduledForUpdate($oid) || $this->isScheduledForDelete($oid);
    }

    private function isScheduledForDelete(string $oid): bool
    {
        return isset($this->deletions[$oid]);
    }

    private function isScheduledForInsert(string $oid): bool
    {
        return isset($this->insertions[$oid]);
    }

    private function isScheduledForUpdate(string $oid): bool
    {
        return isset($this->updates[$oid]);
    }
}