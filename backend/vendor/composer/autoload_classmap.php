<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Analysis_customer' => $baseDir . '/app/models/ReportSettings/Analysis_customer.php',
    'Analysis_product' => $baseDir . '/app/models/ReportSettings/Analysis_product.php',
    'Audit_Report' => $baseDir . '/app/models/ReportSettings/Audit_Report.php',
    'BaseController' => $baseDir . '/app/controllers/BaseController.php',
    'BatchController' => $baseDir . '/app/controllers/BatchController.php',
    'CashReceiptSummary' => $baseDir . '/app/models/ReportSettings/superseed/CashReceiptSummary.php',
    'CommissionController' => $baseDir . '/app/controllers/CommissionController.php',
    'Cost_Price_Report' => $baseDir . '/app/models/ReportSettings/Cost_Price_Report.php',
    'Customer' => $baseDir . '/app/models/Customer.php',
    'CustomerController' => $baseDir . '/app/controllers/CustomerController.php',
    'CustomerManipulation' => $baseDir . '/app/models/Customer/CustomerManipulation.php',
    'CustomerProductDiscount' => $baseDir . '/app/models/CustomerProductDiscount.php',
    'Customer_MonthlyCreditSummary' => $baseDir . '/app/models/ReportSettings/Customer_MonthlyCreditSummary.php',
    'DataWarehouseController' => $baseDir . '/app/controllers/DataWarehouseController.php',
    'DatabaseSeeder' => $baseDir . '/app/database/seeds/DatabaseSeeder.php',
    'Debug' => $baseDir . '/app/models/Debug.php',
    'DeliveryController' => $baseDir . '/app/controllers/DeliveryController.php',
    'GroupController' => $baseDir . '/app/controllers/GroupController.php',
    'HomeController' => $baseDir . '/app/controllers/HomeController.php',
    'IPFController' => $baseDir . '/app/controllers/IPFController.php',
    'IPFManipulation' => $baseDir . '/app/models/IPF/IPFManipulation.php',
    'IlluminateQueueClosure' => $vendorDir . '/laravel/framework/src/Illuminate/Queue/IlluminateQueueClosure.php',
    'Invoice' => $baseDir . '/app/models/Invoice.php',
    'InvoiceImage' => $baseDir . '/app/models/Invoice/InvoiceImage.php',
    'InvoiceItem' => $baseDir . '/app/models/InvoiceItem.php',
    'InvoiceManipulation' => $baseDir . '/app/models/Invoice/InvoiceManipulation.php',
    'InvoicePdf' => $baseDir . '/app/models/Invoice/InvoicePdf.php',
    'InvoicePrintFormat' => $baseDir . '/app/models/InvoicePrintFormat.php',
    'InvoicePrinter' => $baseDir . '/app/models/Invoice/InvoicePrinter.php',
    'InvoiceStatusController' => $baseDir . '/app/controllers/InvoiceStatusController.php',
    'InvoiceStatusManager' => $baseDir . '/app/models/Invoice/InvoiceStatusManager.php',
    'InvoiceUnloader' => $baseDir . '/app/models/Invoice/InvoiceUnloader.php',
    'InvoiceUserPerformance' => $baseDir . '/app/models/InvoiceUserPerformance.php',
    'Invoice_1FPickingList' => $baseDir . '/app/models/ReportSettings/Invoice_1FPickingList.php',
    'Invoice_9FPickingList' => $baseDir . '/app/models/ReportSettings/Invoice_9FPickingList.php',
    'Invoice_CashReceiptSummary' => $baseDir . '/app/models/ReportSettings/Invoice_CashReceiptSummary.php',
    'Invoice_CustomerBreakdown' => $baseDir . '/app/models/ReportSettings/Invoice_CustomerBreakdown.php',
    'Invoice_VanSellList' => $baseDir . '/app/models/ReportSettings/Invoice_VanSellList.php',
    'Items_Summary' => $baseDir . '/app/models/ReportSettings/Items_Summary.php',
    'LoginAudit' => $baseDir . '/app/models/LoginAudit.php',
    'MixMatch' => $baseDir . '/app/models/MixMatch.php',
    'OrderController' => $baseDir . '/app/controllers/OrderController.php',
    'PDF' => $baseDir . '/app/models/Report.php',
    'Payment' => $baseDir . '/app/models/Payment.php',
    'PaymentController' => $baseDir . '/app/controllers/PaymentController.php',
    'PickingList' => $baseDir . '/app/models/ReportSettings/superseed/PickingList.php',
    'PrintQueue' => $baseDir . '/app/models/PrintQueue.php',
    'PrintQueueController' => $baseDir . '/app/controllers/PrintQueueController.php',
    'Printlog' => $baseDir . '/app/models/Printlog.php',
    'Product' => $baseDir . '/app/models/Product.php',
    'ProductController' => $baseDir . '/app/controllers/ProductController.php',
    'ProductGroup' => $baseDir . '/app/models/ProductGroup.php',
    'ProductManipulation' => $baseDir . '/app/models/Product/ProductManipulation.php',
    'ProductSearchCustomerMap' => $baseDir . '/app/models/ProductSearchCustomerMap.php',
    'QueueController' => $baseDir . '/app/controllers/QueueController.php',
    'Report' => $baseDir . '/app/models/Report.php',
    'ReportArchive' => $baseDir . '/app/models/ReportArchive.php',
    'ReportController' => $baseDir . '/app/controllers/ReportController.php',
    'ReportFactory' => $baseDir . '/app/models/Report/ReportFactory.php',
    'Report_Archived' => $baseDir . '/app/models/ReportSettings/Report_Archived.php',
    'Report_DailySummary' => $baseDir . '/app/models/ReportSettings/Report_DailySummary.php',
    'SecurityLog' => $baseDir . '/app/models/SecurityLog.php',
    'SessionHandlerInterface' => $vendorDir . '/symfony/http-foundation/Symfony/Component/HttpFoundation/Resources/stubs/SessionHandlerInterface.php',
    'SystemController' => $baseDir . '/app/controllers/SystemController.php',
    'TableAudit' => $baseDir . '/app/models/TableAudit.php',
    'TestCase' => $baseDir . '/app/tests/TestCase.php',
    'TestController' => $baseDir . '/app/controllers/TestController.php',
    'TruckDayEndSummary' => $baseDir . '/app/models/ReportSettings/superseed/TruckDayEndSummary.php',
    'UseSoftDelete' => $vendorDir . '/toddish/verify/src/migrations/2013_05_11_082613_use_soft_delete.php',
    'User' => $baseDir . '/app/models/User.php',
    'UserController' => $baseDir . '/app/controllers/UserController.php',
    'UserManipulation' => $baseDir . '/app/models/Staff/UserManipulation.php',
    'UserZone' => $baseDir . '/app/models/UserZone.php',
    'VanSellController' => $baseDir . '/app/controllers/VanSellController.php',
    'VerifyInit' => $vendorDir . '/toddish/verify/src/migrations/2013_03_17_131246_verify_init.php',
    'VerifyUserSeeder' => $vendorDir . '/toddish/verify/src/app/database/seeds/VerifyUserSeeder.php',
    'Whoops\\Module' => $vendorDir . '/filp/whoops/src/deprecated/Zend/Module.php',
    'Whoops\\Provider\\Zend\\ExceptionStrategy' => $vendorDir . '/filp/whoops/src/deprecated/Zend/ExceptionStrategy.php',
    'Whoops\\Provider\\Zend\\RouteNotFoundStrategy' => $vendorDir . '/filp/whoops/src/deprecated/Zend/RouteNotFoundStrategy.php',
    'Zone' => $baseDir . '/app/models/Zone.php',
    'customerGroup' => $baseDir . '/app/models/customerGroup.php',
    'data_invoice' => $baseDir . '/app/models/dataWarehouse/data_invoice.php',
    'data_invoiceitem' => $baseDir . '/app/models/dataWarehouse/data_invoiceitem.php',
    'data_product' => $baseDir . '/app/models/dataWarehouse/data_product.php',
    'datawarehouse_customer' => $baseDir . '/app/models/dataWarehouse/datawarehouse_customer.php',
    'datawarehouse_product' => $baseDir . '/app/models/dataWarehouse/datawarehouse_products.php',
    'holiday' => $baseDir . '/app/models/holiday.php',
    'lastitem' => $baseDir . '/app/models/lastitem.php',
    'pickingListVersionControl' => $baseDir . '/app/models/pickingListVersionControl.php',
    'report_stat' => $baseDir . '/app/models/ReportSettings/report_stat.php',
    'role' => $baseDir . '/app/models/role.php',
    'vansell' => $baseDir . '/app/models/vansell.php',
);
