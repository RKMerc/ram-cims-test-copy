<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linked_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_subject_id');
            $table->string('provider_tenant_id')->nullable();
            $table->string('provider_email');
            $table->timestamp('linked_at');
            $table->timestamps();
            $table->unique(['provider', 'provider_tenant_id', 'provider_subject_id'], 'linked_identities_provider_subject_unique');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_code');
            $table->string('type');
            $table->integer('quantity_changed');
            $table->integer('previous_quantity');
            $table->integer('updated_quantity');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_code')->nullable();
            $table->string('kind');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['clinic_notification_id', 'user_id'], 'notification_reads_unique');
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_code');
            $table->string('status');
            $table->timestamp('opened_at');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedInteger('minimum_stock')->nullable()->after('ITEM_QUANTITY');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn('minimum_stock');
        });
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('clinic_notifications');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('linked_identities');
    }
};
