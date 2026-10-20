<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    public function up(): void
    {
        Capsule::schema()->create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade');
            $table->string('title');
            $table->integer('position')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('board_columns');
    }
};
