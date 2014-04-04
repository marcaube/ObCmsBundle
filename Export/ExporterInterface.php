<?php

namespace Ob\CmsBundle\Export;

use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface
{
    /**
     * @param string $filename
     * @param string $format
     * @param array  $data
     * @param array  $fields
     *
     * @return Response
     */
    public function export($filename, $format, $data, $fields);

    /**
     * Returns whether this exporter supports a certain file format or not
     *
     * @param string $format
     *
     * @return boolean
     */
    public function supports($format);
}