Dear Clinic Personnel,

RAM-CIMS has detected an inventory item that requires attention.

Item Name: {{ $item->GENERIC_NAME }}
Category: {{ $item->ITEM_CATEGORY }}
Current Stock: {{ $item->ITEM_QUANTITY }}
Minimum Threshold: {{ $item->stockThreshold() ?? 'Configuration required' }}
Status: {{ $status }}

Please review the inventory and arrange replenishment when necessary.

RAM-CIMS Inventory Management System
