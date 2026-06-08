<?php

namespace App\Http\Controllers\Vendor;

use App\Support\ListExports\VendorListExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportController extends VendorController
{
    public function __invoke(Request $request, string $module, VendorListExporter $exporter): StreamedResponse|Response
    {
        return $exporter->export($request, $module);
    }
}
