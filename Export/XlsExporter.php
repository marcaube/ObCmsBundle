<?php

namespace Ob\CmsBundle\Export;

use Liuggio\ExcelBundle\Factory;

class XlsExporter implements ExporterInterface
{
    /**
     * @var Factory
     */
    private $phpexcel;

    /**
     * @param Factory $phpexcel
     */
    public function __construct(Factory $phpexcel)
    {
        $this->phpexcel = $phpexcel;
    }

    /**
     * {@inheritdoc}
     */
    public function export($filename, $format, $data, $fields)
    {
        $file = $this->phpexcel->createPHPExcelObject();
        $sheet = $file->setActiveSheetIndex(0);

        $sheet = $this->writeRows($sheet, $fields, $data);
        $sheet = $this->writeHeaders($sheet, $fields);

        $writer = $this->phpexcel->createWriter($file, 'Excel5');

        $response = $this->phpexcel->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }

    private function writeHeaders($sheet, $columnNames)
    {
        // Define column range
        $first = $last = 'A';

        // Write headers
        foreach ($columnNames as $header) {
            $sheet->setCellValue($last . '1', $header);
            $last++;
        }

        $sheet->freezePane('A2');

        // Make them stand out
        $sheet->getStyle($first . '1:' . $last . '1')->getFont()->setBold(true);

        // Auto-size columns
        foreach (range($first, $last) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $sheet;
    }

    private function writeRows($sheet, $columnNames, $data)
    {
        $firstDataRow = 2;

        foreach ($data as $entity) {
            $cell = 'A';

            foreach ($columnNames as $column) {
                if (strpos($column, '.') !== false) {
                    list($relation, $field) = explode('.', $column);
                    $value = $entity->{"get$relation"}()->{"get$field"}();
                } else {
                    $value = $this->stringify($entity->{"get$column"}());
                }

                $sheet->setCellValue($cell . $firstDataRow, $value);
                $cell++;
            }

            $firstDataRow++;
        }

        return $sheet;
    }

    /**
     * Transform objects to strings
     *
     * @param $value
     * @return string|mixed
     */
    private function stringify($value)
    {
        if (gettype($value) == 'object') {
            switch (get_class($value)) {
                case 'DateTime':
                    $value = $value->format('Y-m-d');
                    break;
                case 'Doctrine\Common\Collections':
                case 'Doctrine\ORM\PersistentCollection':
                    $value = count($value);
                    break;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($format)
    {
        if ($format == 'xls') {
            return true;
        }

        return false;
    }
}