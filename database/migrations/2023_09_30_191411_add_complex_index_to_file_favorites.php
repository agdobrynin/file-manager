<?php

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_favorites', function (Blueprint $table) {
            $table->unique(['file_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_favorites', function (Blueprint $table) {
            $table->dropForeignIdFor(File::class);
            $table->dropForeignIdFor(User::class);

            $table->dropUnique(['file_id', 'user_id']);

            $table->foreign('file_id')
                ->on('files')
                ->references('id');

            $table->foreign('user_id')
                ->on('users')
                ->references('id');
        });
    }
};
