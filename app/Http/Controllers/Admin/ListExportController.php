<?php

namespace App\Http\Controllers\Admin;

use App\Support\ListExports\AdminListExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportController extends AdminController
{
    public function __invoke(Request $request, string $module, AdminListExporter $exporter): StreamedResponse|Response
    {
        return $exporter->export($request, $module);
    }
}
