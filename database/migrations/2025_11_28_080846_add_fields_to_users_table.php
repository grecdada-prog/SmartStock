<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_activity')->nullable()->after('is_active');
            $table->string('session_id')->nullable()->after('last_activity');
            $table->boolean('google2fa_enabled')->default(false)->after('session_id');
            $table->text('google2fa_secret')->nullable()->after('google2fa_enabled');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'phone',
                'is_active',
                'last_activity',
                'session_id',
                'google2fa_enabled',
                'google2fa_secret',
                'created_by',
            ]);
        });
    }
};
