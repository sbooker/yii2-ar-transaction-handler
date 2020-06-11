<?php

declare(strict_types=1);

namespace Test\Sbooker\TransactionManager\Yii2ActiveRecord;

use http\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Sbooker\TransactionManager\Yii2ActiveRecord\EntityActiveRecordMapper;
use Sbooker\TransactionManager\Yii2ActiveRecord\UnitOfWork;
use yii\db\ActiveRecord;

final class UnitOfWorkTest extends TestCase
{
    public function testPersist(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,1,0);
        $mapper = $this->getMapper($entity, $ar, 1, 0,0);

        $uow->persist($entity, $mapper);
        $uow->commit();
    }

    public function testUpdate(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,1);
        $mapper = $this->getMapper($entity, $ar, 0, 1,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $uow->scheduleForUpdate($mappedEntity, $mapper);
        $uow->commit();

        $this->assertEquals(spl_object_hash($entity), spl_object_hash($mappedEntity));
    }

    public function testUpdatePersisted(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,1,0);
        $mapper = $this->getMapper($entity, $ar, 1, 1,0);

        $uow->persist($entity, $mapper);
        $uow->scheduleForUpdate($entity, $mapper);
        $uow->commit();
    }

    public function testDelete(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(1,0,0);
        $mapper = $this->getMapper($entity, $ar, 0, 0,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $uow->scheduleForDelete($mappedEntity);
        $uow->commit();

        $this->assertEquals(spl_object_hash($entity), spl_object_hash($mappedEntity));
    }

    public function testDeletePersisted(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);
        $mapper = $this->getMapper($entity, $ar, 1, 0,0);

        $uow->persist($entity, $mapper);
        $uow->scheduleForDelete($entity);
        $uow->commit();
    }

    public function testDeleteUpdated(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(1,0,0);
        $mapper = $this->getMapper($entity, $ar, 0, 1,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $uow->scheduleForUpdate($mappedEntity, $mapper);
        $uow->scheduleForDelete($mappedEntity);
        $uow->commit();

        $this->assertEquals(spl_object_hash($entity), spl_object_hash($mappedEntity));
    }

    public function testUpdateDeleted(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);
        $mapper = $this->getMapper($entity, $ar, 0, 0,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $uow->scheduleForDelete($mappedEntity);

        $this->expectException(\RuntimeException::class);
        $uow->scheduleForUpdate($entity, $mapper);

        $this->assertEquals(spl_object_hash($entity), spl_object_hash($mappedEntity));
    }

    public function testPersistPersisted(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);
        $mapper = $this->getMapper($entity, $ar, 1, 0,0);

        $uow->persist($entity, $mapper);
        $this->expectException(\RuntimeException::class);
        $uow->persist($entity, $mapper);
    }

    public function testPersistExists(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);
        $mapper = $this->getMapper($entity, $ar, 0, 0,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $this->expectException(\RuntimeException::class);
        $uow->persist($mappedEntity, $mapper);
    }

    public function testPersistDeleted(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);
        $mapper = $this->getMapper($entity, $ar, 0, 0,1);

        $mappedEntity = $uow->getForUpdate($ar, $mapper);
        $uow->scheduleForDelete($mappedEntity);
        $this->expectException(\RuntimeException::class);
        $uow->persist($mappedEntity, $mapper);
    }

    public function testDeleteNotExists(): void
    {
        $uow = new UnitOfWork();
        $entity = new \stdClass();
        $ar = $this->getActiveRecord(0,0,0);

        $this->expectException(\RuntimeException::class);
        $uow->scheduleForDelete($entity);
    }

    private function getMapper(object $entity, ActiveRecord $activeRecord, int $createCount, int $updateCount, int $hydrateCount): EntityActiveRecordMapper
    {
        $mock = $this->createMock(EntityActiveRecordMapper::class);

        $mock->expects($this->exactly($createCount))->method('create')->with($entity)->willReturn($activeRecord);
        $mock->expects($this->exactly($updateCount))->method('update')->with($entity, $activeRecord);
        $mock->expects($this->exactly($hydrateCount))->method('hydrate')->with($activeRecord)->willReturn($entity);

        return $mock;
    }

    private function getActiveRecord(int $deleteCount, int $insertCount, int $updateCount): ActiveRecord
    {
        $mock = $this->createMock(ActiveRecord::class);

        $mock->expects($this->exactly($insertCount))->method('insert')->with(false)->willReturn(true);
        $mock->expects($this->exactly($updateCount))->method('update')->with(false)->willReturn(true);
        $mock->expects($this->exactly($deleteCount))->method('delete')->willReturn(1);

        return $mock;
    }
}