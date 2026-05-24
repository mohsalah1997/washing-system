<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Exports\CustomerReadingsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerReadingTemplateExportController extends Controller
{
    public function __invoke()
    {
        $fileName = 'customers_readings_template_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new CustomerReadingsTemplateExport(), $fileName);
    }
}

