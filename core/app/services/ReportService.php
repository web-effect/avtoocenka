<?php
namespace App\Services;
use App\Models\Report;

class ReportService
{
    /**
     * Получить отчёт или выдать ошибку
     */
    public static function getOrFail(int $id, bool $asObject = false)
    {
        UserService::requireAccess(); // если не авторизован вернёт 401

        $report = Report::find($id);

        if (!$report) {
            abortx(404, 'Отчёт не найден');
        }

        if (UserService::isAdmin() || $report->user_id == UserService::id()) {
            return $asObject ? $report : $report->toArray();
        }

        abortx(403); // чужой заказ и нет прав админа


        // global $modx;
        // UserService::requireAccess(); // если не авторизован — 401

        // $report = [];
        // $resource = $modx->getObject('modResource', $id);

        // if (!$resource) {
        //     abortx(404, 'Отчёт не найден');
        // }

        // foreach (['user_id', 'step', 'vehicle', 'discount'] as $field) {
        //     $report[$field] = $resource->getTVValue($field);
        // }

        // $report['id'] = (int)$resource->id;
        // $report['vehicle'] = json_decode($report['vehicle'], true) ?: [];

        // $report['price'] = 744800;
        // $report['discount'] = (int)$report['discount'];
        // $report['discount_price'] = round($report['price'] * $report['discount'] / 100);
        // $report['final_price'] = $report['price'] - $report['discount_price'];

        // if (UserService::isAdmin() || $report['user_id'] == UserService::id()) {
        //     return $asObject ? $resource : $report;
        // }

        // abortx(403); // чужой заказ или нет прав админа
    }

    public static function getList(array $options = []): array
    {
        UserService::requireAccess();

        $limit = (int)($options['limit'] ?? 0);

        $q = Report::where('user_id', UserService::id())->orderByDesc('id');
        $total = $q->count();

        if ($limit) {
            $q->limit($limit);
        }

        $items = $q->get()->toArray();

        $result = [
            'total' => $total,
            'items' => $items
        ];

        return $result;
    }
}