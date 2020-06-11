<?php

declare(strict_types=1);

namespace Sbooker\TransactionManager\Yii2ActiveRecord;

use yii\db\ActiveRecord;

interface EntityActiveRecordMapper
{
    public function create(object $entity): ActiveRecord;

    public function update(object $entity, ActiveRecord $activeRecord): void;

    public function hydrate(ActiveRecord $activeRecord): object;
}