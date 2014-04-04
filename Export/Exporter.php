<?php

namespace Ob\CmsBundle\Export;

class Exporter implements ExporterInterface
{
    private $exporters = array();

    /**
     * {@inheritdoc}
     */
    public function export($filename, $format, $data, $fields)
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($format)) {
                return $exporter->export($filename, $format, $data, $fields);
            }
        }

        throw new \InvalidArgumentException(sprintf('There are no exporters for format %s.', $format));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($format)
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($format)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add an exporter
     *
     * @param ExporterInterface $exporter
     */
    public function addExporter(ExporterInterface $exporter)
    {
        $this->exporters[] = $exporter;
    }
}