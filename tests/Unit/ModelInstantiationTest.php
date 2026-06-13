<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Approval;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InboxItem;
use App\Models\LeftoverPiece;
use App\Models\LeftoverPieceConsumption;
use App\Models\LeftoverPieceUsage;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestLine;
use App\Models\MaterialReturn;
use App\Models\MaterialReturnLine;
use App\Models\PosPayment;
use App\Models\PosRefund;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosServiceLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\ProjectBom;
use App\Models\ProjectService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\StockLocation;
use App\Models\StockMove;
use App\Models\StockQuant;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceLine;
use App\Models\TransferDoc;
use App\Models\Uom;
use App\Models\User;

class ModelInstantiationTest extends TestCase
{
    public function test_all_models_can_be_instantiated(): void
    {
        $models = [
            new Approval(), new Attachment(), new AuditLog(), new Branch(),
            new CashMovement(), new Customer(), new GoodsReceipt(), new GoodsReceiptLine(),
            new InboxItem(), new LeftoverPiece(), new LeftoverPieceConsumption(),
            new LeftoverPieceUsage(), new MaterialRequest(), new MaterialRequestLine(),
            new MaterialReturn(), new MaterialReturnLine(), new PosPayment(), new PosRefund(),
            new PosSale(), new PosSaleLine(), new PosServiceLine(), new Product(),
            new ProductCategory(), new Project(), new ProjectBom(), new ProjectService(),
            new PurchaseOrder(), new PurchaseOrderLine(), new Role(), new SalesInvoice(),
            new SalesInvoiceLine(), new StockLocation(), new StockMove(), new StockQuant(),
            new StockTransfer(), new StockTransferLine(), new Supplier(), new SupplierInvoice(),
            new SupplierInvoiceLine(), new TransferDoc(), new Uom(), new User(),
        ];

        $this->assertCount(42, $models);
    }

    public function test_all_models_have_correct_table_names(): void
    {
        $expected = [
            Approval::class => 'approvals',
            Attachment::class => 'attachments',
            AuditLog::class => 'audit_logs',
            Branch::class => 'branches',
            CashMovement::class => 'cash_movements',
            Customer::class => 'customers',
            GoodsReceipt::class => 'goods_receipts',
            GoodsReceiptLine::class => 'goods_receipt_lines',
            InboxItem::class => 'inbox_items',
            LeftoverPiece::class => 'leftover_pieces',
            LeftoverPieceConsumption::class => 'leftover_piece_consumptions',
            LeftoverPieceUsage::class => 'leftover_piece_usages',
            MaterialRequest::class => 'material_requests',
            MaterialRequestLine::class => 'material_request_lines',
            MaterialReturn::class => 'material_returns',
            MaterialReturnLine::class => 'material_return_lines',
            PosPayment::class => 'pos_payments',
            PosRefund::class => 'pos_refunds',
            PosSale::class => 'pos_sales',
            PosSaleLine::class => 'pos_sale_lines',
            PosServiceLine::class => 'pos_service_lines',
            Product::class => 'products',
            ProductCategory::class => 'product_categories',
            Project::class => 'projects',
            ProjectBom::class => 'project_boms',
            ProjectService::class => 'project_services',
            PurchaseOrder::class => 'purchase_orders',
            PurchaseOrderLine::class => 'purchase_order_lines',
            Role::class => 'roles',
            SalesInvoice::class => 'sales_invoices',
            SalesInvoiceLine::class => 'sales_invoice_lines',
            StockLocation::class => 'stock_locations',
            StockMove::class => 'stock_moves',
            StockQuant::class => 'stock_quants',
            StockTransfer::class => 'stock_transfers',
            StockTransferLine::class => 'stock_transfer_lines',
            Supplier::class => 'suppliers',
            SupplierInvoice::class => 'supplier_invoices',
            SupplierInvoiceLine::class => 'supplier_invoice_lines',
            TransferDoc::class => 'transfer_docs',
            Uom::class => 'uoms',
            User::class => 'users',
        ];

        foreach ($expected as $modelClass => $expectedTable) {
            $model = new $modelClass();
            $this->assertEquals(
                $expectedTable,
                $model->getTable(),
                "Model {$modelClass} should use table '{$expectedTable}' but uses '{$model->getTable()}'"
            );
        }
    }

    public function test_total_model_count(): void
    {
        $modelsDir = dirname(__DIR__, 2) . '/app/Models';
        $phpFiles = glob($modelsDir . '/*.php');
        $this->assertCount(45, $phpFiles);
    }
}
