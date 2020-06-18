<?php

declare(strict_types=1);

namespace Sbooker\TransactionManager\Yii2ActiveRecord;

use yii\db\Connection;
use yii\db\Transaction;

final class TransactionHandler implements \Sbooker\TransactionManager\TransactionHandler
{
    /** @var Connection */
    private $connection;

    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var Transaction */
    private $transaction;

    public function __construct(Connection $connection, UnitOfWork $unitOfWork = null)
    {
        $this->connection = $connection;
        $this->unitOfWork = $unitOfWork ?? new UnitOfWork();
    }

    public function begin(): void
    {
        $this->transaction = $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        if ($this->transaction->getLevel() == 1) {
            $this->unitOfWork->commit();
        }
        $this->transaction->commit();
        $this->clear();
    }

    public function rollBack(): void
    {
        $this->transaction->rollBack();
        $this->clear();
    }

    public function clear(): void
    {
        if (!$this->transaction->getIsActive()) {
            $this->unitOfWork->clear();
        }
    }
}