<?php

namespace App\Exports\finance\gst_reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GstReport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Map invoice type code => Sheet class name
        $map = [
            'b2b' => B2bSheet::class,
            'b2ba' => B2baSheet::class,
            'b2cl' => B2clSheet::class,
            'b2cla' => B2claSheet::class,
            'b2cs' => B2csSheet::class,
            'b2csa' => B2csaSheet::class,
            'cdnr' => CdnrSheet::class,
            'cdnra' => CdnraSheet::class,
            'cdnur' => CdnurSheet::class,
            'cdnura' => CdnuraSheet::class,
            'exp' => ExpSheet::class,
            'expa' => ExpaSheet::class,
            'at' => AtSheet::class,
            'ata' => AtaSheet::class,
            'atadj' => AtadjSheet::class,
            'atadja' => AtadjaSheet::class,
            'hsnb2b' => Hsnb2bSheet::class,
            'hsnb2c' => Hsnb2cSheet::class,
            'doc_issue' => DocIssueSheet::class,
            // 'ecob2b' => Ecob2bSheet::class,
            // 'ecob2c' => Ecob2cSheet::class,
            //'ecourp2b' => Ecourp2bSheet::class,
            // 'ecourp2c' => Ecourp2cSheet::class,
            // 'ecoab2b' => Ecoab2bSheet::class,
            // 'ecoab2c' => Ecoab2cSheet::class,
            // 'ecoaurp2b' => Ecoaurp2bSheet::class,
            'ecoaurp2c' => Ecoaurp2cSheet::class,
            // 'supeco' => SupecoSheet::class,
            // 'supecoa' => SupecoaSheet::class,
           'nil' => NilSheet::class,
            // 'txpd' => TxpdSheet::class,
            // 'txpda' => TxpdaSheet::class,
        ];

        // Loop through all sheets (not just ones with data)
        foreach ($map as $code => $sheetClass) {
            if (!class_exists($sheetClass)) {
                continue;
            }

            // If data exists, pass it; else pass an empty array
            $rows = $this->data[$code] ?? [];

            // Instantiate sheet class; check constructor
            $ref = new \ReflectionClass($sheetClass);
            $ctor = $ref->getConstructor();

            if ($ctor && $ctor->getNumberOfRequiredParameters() >= 1) {
                $sheets[] = $ref->newInstance($rows);
            } else {
                $sheets[] = $ref->newInstance();
            }
        }

        return $sheets;
    }
}
