<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            $table->string('provider_name')->nullable()->after('remember_token');
            $table->string('provider_id')->nullable()->after('provider_name');
            $table->string('provider_avatar')->nullable()->after('provider_id');

            $table->unique(['provider_name', 'provider_id']);
        });

        Schema::create('user_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone');
            $table->string('code_hash', 255);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['phone', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_otps');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['provider_name', 'provider_id']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'last_login_at',
                'provider_name',
                'provider_id',
                'provider_avatar',
            ]);
        });
    }
};
