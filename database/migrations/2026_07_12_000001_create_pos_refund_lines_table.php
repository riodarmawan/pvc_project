<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_refund_lines')) {
            Schema::create('pos_refund_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_refund_id')->constrained('pos_refunds')->cascadeOnDelete();
                $table->foreignId('pos_sale_line_id')->constrained('pos_sale_lines')->cascadeOnDelete();
                $table->decimal('qty', 18, 3);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_refund_lines');
    }
};
