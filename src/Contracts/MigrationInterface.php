<?php

namespace Forge\Database;

interface MigrationInterface
{
    public function up(): void;
    public function down(): void;
}
