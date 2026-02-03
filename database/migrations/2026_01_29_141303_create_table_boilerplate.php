<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration to generate tables related to product quantity calculations:
     * - allocated_item
     * - order
     * - order_item
     * - order_item_groups
     * - order_status
     * - product
     * - product_to_warehouse_location
     * - shipment
     * - shipment_item
     * - stock_transfer_items
     * - stock_transfers
     * - warehouse_locations
     */
    public function up(): void
    {
        if (DB::table('migrations')->where('migration', '2026_01_29_141303_create_table_boilerplate')->exists()) {
            return;
        };

        Schema::create('allocated_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('model');
            $table->integer('model_id');
            $table->char('warehouse_location_uuid', 36)->index('allocated_item_warehouse_location_uuid_foreign');
            $table->integer('allocated_quantity');
            $table->integer('required_quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('cloned_to_id')->nullable()->index('order_cloned_to_id_foreign');
            $table->string('restore_key')->nullable()->unique();
            $table->unsignedBigInteger('order_status_id')->nullable()->index('order_order_status_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
        });

        Schema::create('order_item', function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('order_item_group_uuid', 36)->index();
            $table->integer('product_id')->index('product_id');
            $table->integer('quantity');
        });

        Schema::create('order_item_groups', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->integer('order_id')->index('order_item_groups_order_id_foreign');
            $table->timestamps();
        });

        Schema::create('order_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug');
            $table->string('name');
        });

        Schema::create('product', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamps();
        });

        Schema::create('product_to_warehouse_location', function (Blueprint $table) {
            $table->integer('product_id')->index('product_to_warehouse_location_product_id_foreign');
            $table->char('warehouse_location_uuid', 36)->index('product_to_warehouse_location_warehouse_location_uuid_foreign');
            $table->integer('on_hand_quantity');
            $table->integer('pre_order_quantity')->default(0);
            $table->integer('orderable_quantity')->default(0);
            $table->integer('threshold');
            $table->timestamps();
            $table->softDeletes();
            $table->bigIncrements('id');
        });

        Schema::create('shipment', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
        });

        Schema::create('shipment_item', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('shipment_id')->nullable();
            $table->unsignedBigInteger('allocated_item_id')->nullable()->index('shipment_item_allocated_item_id_idx');
            $table->integer('quantity')->nullable();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_transfer_id')->index('stock_transfer_items_stock_transfer_id_foreign');
            $table->integer('product_id')->index('stock_transfer_items_product_id_foreign');
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('warehouse_from_uuid', 36)->index('stock_transfers_warehouse_from_uuid_foreign');
            $table->char('warehouse_to_uuid', 36)->index('stock_transfers_warehouse_to_uuid_foreign');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->string('name');
            $table->string('slug', 50)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('allocated_item', function (Blueprint $table) {
            $table->foreign(['warehouse_location_uuid'])->references(['uuid'])->on('warehouse_locations')->onUpdate('no action')->onDelete('cascade');
        });

        Schema::table('order', function (Blueprint $table) {
            $table->foreign(['order_status_id'])->references(['id'])->on('order_status')->onUpdate('no action')->onDelete('no action');
        });

        Schema::table('order_item', function (Blueprint $table) {
            $table->foreign(['order_item_group_uuid'])->references(['uuid'])->on('order_item_groups')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['product_id'])->references(['id'])->on('product')->onUpdate('no action')->onDelete('no action');
        });

        Schema::table('order_item_groups', function (Blueprint $table) {
            $table->foreign(['order_id'])->references(['id'])->on('order')->onUpdate('no action')->onDelete('cascade');
        });

        Schema::table('product_to_warehouse_location', function (Blueprint $table) {
            $table->foreign(['product_id'])->references(['id'])->on('product')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['warehouse_location_uuid'])->references(['uuid'])->on('warehouse_locations')->onUpdate('no action')->onDelete('cascade');
        });

        Schema::table('shipment_item', function (Blueprint $table) {
            $table->foreign(['allocated_item_id'], 'shipment_item_allocated_item_id_fk')->references(['id'])->on('allocated_item')->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->foreign(['product_id'])->references(['id'])->on('product')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['stock_transfer_id'])->references(['id'])->on('stock_transfers')->onUpdate('no action')->onDelete('cascade');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreign(['warehouse_from_uuid'])->references(['uuid'])->on('warehouse_locations')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['warehouse_to_uuid'])->references(['uuid'])->on('warehouse_locations')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign('stock_transfers_warehouse_from_uuid_foreign');
            $table->dropForeign('stock_transfers_warehouse_to_uuid_foreign');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign('stock_transfer_items_product_id_foreign');
            $table->dropForeign('stock_transfer_items_stock_transfer_id_foreign');
        });

        Schema::table('shipment_item', function (Blueprint $table) {
            $table->dropForeign('shipment_item_allocated_item_id_fk');
        });

        Schema::table('product_to_warehouse_location', function (Blueprint $table) {
            $table->dropForeign('product_to_warehouse_location_product_id_foreign');
            $table->dropForeign('product_to_warehouse_location_warehouse_location_uuid_foreign');
        });

        Schema::table('order_item_groups', function (Blueprint $table) {
            $table->dropForeign('order_item_groups_order_id_foreign');
        });

        Schema::table('order_item', function (Blueprint $table) {
            $table->dropForeign('order_item_order_item_group_uuid_foreign');
            $table->dropForeign('order_item_product_id_foreign');
        });

        Schema::table('order', function (Blueprint $table) {
            $table->dropForeign('order_order_status_id_foreign');
        });

        Schema::table('allocated_item', function (Blueprint $table) {
            $table->dropForeign('allocated_item_warehouse_location_uuid_foreign');
        });

        Schema::dropIfExists('warehouse_locations');

        Schema::dropIfExists('stock_transfers');

        Schema::dropIfExists('stock_transfer_items');

        Schema::dropIfExists('shipment_item');

        Schema::dropIfExists('shipment');

        Schema::dropIfExists('product_to_warehouse_location');

        Schema::dropIfExists('product');

        Schema::dropIfExists('order_status');

        Schema::dropIfExists('order_item_groups');

        Schema::dropIfExists('order_item');

        Schema::dropIfExists('order');

        Schema::dropIfExists('allocated_item');
    }
};
