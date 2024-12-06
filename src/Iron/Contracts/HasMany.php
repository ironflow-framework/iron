<?php

namespace Forge\Database\Iron\Contracts;

interface HasMany extends RelationshipInterface
{
    public function associate($model): void;
    public function dissociate(): void;
    public function getRelated(): mixed;
}
