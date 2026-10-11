Dear {{ config('clinic.alert_greeting') }},

RAM-CIMS has detected an inventory item requiring your attention.

Item Name: {{ $item->GENERIC_NAME }}
Category: {{ $item->ITEM_CATEGORY }}
Current Stock: {{ $item->ITEM_QUANTITY }} {{ $item->stockUnit() }}
Automatic Low-Stock Threshold: {{ $item->stockThreshold() === null ? 'Baseline not recorded' : $item->stockThreshold().' '.$item->stockUnit() }}
Inventory Status: {{ $status }}
Date and Time: {{ $detectedAt }}

Please review the inventory and arrange replenishment when necessary.

Thank you.

RAM-CIMS
Asia Pacific College School Clinic

This is an automated notification. Please do not reply to this email.
