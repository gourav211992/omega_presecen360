<?php

namespace App\Helpers\TransactionReport;

class pwoReportHelper
{    
    const PWO_TABLE_HEADERS = [
        [
            'name' => 'S. No',
            'field' => 'DT_RowIndex',
            'header_class' => '',
            'column_class' => '',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Series',
            'field' => 'book_name',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Doc No',
            'field' => 'document_number',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Date',
            'field' => 'document_date',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Location',
            'field' => 'store_name',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Store',
            'field' => 'sub_store_name',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => '',
            'column_style' => '',
        ],
        [
            'name' => 'Status',
            'field' => 'status',
            'header_class' => '',
            'column_class' => 'no-wrap',
            'header_style' => 'text-align:center',
            'column_style' => '',
        ],
    ];
    const PWO_FILTERS = [
        [
            'colSpan' => 'auto',
            'label' => 'Series',
            'id' => 'book_filter',
            'requestName' => 'book_id',
            'term' => 'report_pwo_book',
            'value_key' => 'id',
            'label_key' => 'book_code',
            'type' => 'auto_complete'
        ],
        [
            'colSpan' => 'auto',
            'label' => 'Doc No',
            'id' => 'doc_number_filter',
            'requestName' => 'document_number',
            'term' => 'report_so_documents',
            'value_key' => 'id',
            'label_key' => 'document_number',
            'type' => 'input_text'
        ],
        [
            'colSpan' => 'auto',
            'label' => 'Product',
            'id' => 'product_filter',
            'requestName' => 'product_id',
            'term' => 'pr_item',
            'value_key' => 'id',
            'label_key' => 'item_name',
            'type' => 'auto_complete'
        ],  
        [
            'colSpan' => 'auto',
            'label' => 'Created By',
            'id' => 'created_filter',
            'requestName' => 'created_id',
            'term' => 'auth_user',
            'value_key' => 'id',
            'label_key' => 'name',
            'type' => 'auto_complete'
        ],
        [
            'colSpan' => 'auto',
            'label' => 'Status',
            'id' => 'doc_status_filter',
            'requestName' => 'doc_status',
            'term' => 'document_statuses',
            'value_key' => 'id',
            'label_key' => 'name',
            'type' => 'auto_complete'
        ],
    ];
}