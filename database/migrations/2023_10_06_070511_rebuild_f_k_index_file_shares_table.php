<?php

use App\Models\File;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_shares', function (Blueprint $table) {
            $table->dropForeignIdFor(File::class);

            $table->foreign('file_id')
                ->on('files')
                ->references('id')
                ->cascadeOnDelete();

            $table->dropForeign('file_shares_user_id_foreign');

            $table->foreign('for_user_id')
                ->on('users')
                ->references('id')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_shares', function (Blueprint $table) {
            $table->dropForeignIdFor(File::class);

            $table->foreign('file_id')
                ->on('files')
                ->references('id');

            $table->dropForeign('file_shares_for_user_id_foreign');

            $table->foreign('for_user_id')
                ->on('users')
                ->references('id');
        });
    }
};
