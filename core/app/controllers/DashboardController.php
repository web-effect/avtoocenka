<?php
namespace App\Controllers;
use Zoomx\Controllers;
use App\Services\EvaluationService;
use App\Services\UserService;
use App\Services\ReportService;
use App\Models\Report;

class DashboardController extends \Zoomx\Controllers\Controller
{
    public function index($key = '')
    {
        if (!UserService::id()) redirectx('/auth');

        if ($key == 'profile') {
            return viewx('dashboard/profile.tpl',  [ ]);
        } else if ($key == 'pricing') {
            return viewx('dashboard/pricing.tpl',  [ ]);
        } else if ($key == 'appraiser') {
            return viewx('dashboard/appraiser.tpl',  [ ]);
        } else if ($key == 'info') {
            return viewx('dashboard/info.tpl',  [ ]);
        }

        redirectx('/dashboard/reports');
    }


    public function reports($key = '')
    {
        global $modx;
        if (!UserService::id()) redirectx('/auth');

        if (!$key) {
            //$this->modx->resource = zoomx()->getResource('dashboard/evaluations');
            return viewx('dashboard/reports.tpl',  [
                
            ]);
        }
        
        if ($key === 'new') {
            $availableReports = UserService::getAvailableReports();
            if ($availableReports <= 0) {
                redirectx('/dashboard/pricing');
                return;
            }

            $report = Report::create([
                'user_id' => UserService::id()
            ]);

            UserService::changeAvailableReports(-1);
            
            return redirectx("/dashboard/reports/{$report->id}");
        }
        
        if ((int)$key > 0) {
            $report = ReportService::getOrFail((int)$key);
            
            $this->modx->resource = zoomx()->getResource('dashboard/reports/new');

            if ($report['step'] >= 5) {
                $this->modx->resource->set('pagetitle', 'Готовый отчёт');
            }
            
            return viewx('dashboard/report.tpl',  [
                'report' => $report,
            ]);
        }
    }
}