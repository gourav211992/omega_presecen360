<?php

namespace App\Helpers\Configuration;

class Constants
{
    public const ORG_CONFIG_ENFORCE_UIC_SCANNING = "enforce_uic_scanning";
    public const COMP_TDS_APPLICABLE = "tds_applicable";
    public const ORG_MORPH_TYPE = "organization";
    public const COMPANY_MORPH_TYPE = "company";

    // To Enable Wip Qty in Production slip as per Group alias
    public const GROUP_PSLIP_WIP_QTY = [
        'Shufab OLD',
        'Shufab UAT',
        'Shufab',
        'Staqo',
    ];

    // To make attachment mandatory for mrn as per Group alias
    public const GROUP_ATTACHMENT_MANDATORY = [
        'HOK',
    ];
}
