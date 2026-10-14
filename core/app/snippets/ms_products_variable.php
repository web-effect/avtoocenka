<?php

class CatalogWithVariants
{
    protected $modx;
    protected $categoryIds;
    protected $props = [];
    protected $joins = [];
    protected $where = [];
    protected $priceWhere = [];
    protected $sortby = 'msCategory.menuindex';
    protected $sortdir = 'ASC';

    protected $limitBase = 3;
    protected $page = 1;
    protected $extraLoad = 0;

    protected $categoryTemplateId = 25;
    protected $productTemplateId = 23;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;

        $this->categoryIds = $this->getCategoryIds();

        $this->page = max(1, (int)($_REQUEST['page'] ?? 1));
        $this->extraLoad = max(0, (int)($_REQUEST['loadmore'] ?? 0));
        $this->limitBase = 3;
    }

    public function run()
    {
        $this->parseProps();
        $this->buildJoinsAndWhere();
        $this->processSorting();
        $this->processPriceFilters();

        $items = $this->getItems();
        $total = $this->getTotal();

        $totalPages = ceil($total / $this->limitBase);
        $is_loadmore = $this->hasMore($total);

        return [
            'page' => $this->page,
            'loadmore' => $this->extraLoad,
            'is_loadmore' => $is_loadmore,
            'total_products' => $total,
            'total_pages' => $totalPages,
            'pagination' => $this->renderPagination($totalPages, $this->page),
            'items' => $items,
        ];
    }

    private function getCategoryIds()
    {
        $ids = [$this->modx->resource->id];
        $allIds =$this->modx->getChildIds($this->modx->resource->id, 10, ['context' => $this->modx->context->key]);

        $q = $this->modx->newQuery('msCategory');
        $q->where([
            'id:IN' => $allIds,
            'template' => $this->categoryTemplateId,
        ]);
        $q->select('id');

        if ($q->prepare() && $q->stmt->execute()) {
            $ids = array_merge($ids, $q->stmt->fetchAll(PDO::FETCH_COLUMN, 0));
        }

        return $ids;
    }

    /* ------------------------------- Пропсы ------------------------------- */

    protected function parseProps()
    {
        foreach ($_REQUEST as $key => $val) {
            if (preg_match('/sort|page|min_|max_|filter_/i', $key)) {
                if (preg_match('/filter_/i', $key) && is_string($val)) {
                    preg_match_all('/\d+(,\d+)?\s*[^,]+/', $val, $m);
                    $this->props[$key] = $m[0] ?: explode(',', $val);
                } else {
                    $this->props[$key] = $val;
                }
            }
        }
    }

    /* ------------------------- Join & Where по msProduct ------------------------- */

    protected function buildJoinsAndWhere()
    {
        foreach ($this->props as $filterKey => $values) {
            if (empty($values)) continue;

            $option = str_replace('filter_', '', $filterKey);

            if (in_array($option, ['page', 'sort', 'min_price', 'max_price'])) {
                continue;
            }

            $alias = 'opt' . ucfirst($option);

            // join по подтоварам
            $this->joins[] = [
                'class' => 'msProductOption',
                'alias' => $alias,
                'on' => "$alias.product_id = msProduct.id AND $alias.key = '$option'"
            ];

            $this->where["{$alias}.value:IN"] = $values;
        }
    }

    /* ------------------------------- Сортировка ------------------------------- */

    protected function processSorting()
    {
        if (!isset($this->props['sort'])) return;

        $s = explode('_', $this->props['sort']);
        $by = $s[0] ?? '';

        if ($by === 'price') {
            $this->sortby = 'product_data.price';
        } elseif ($by === 'pagetitle') {
            $this->sortby = 'msCategory.pagetitle';
        }

        $this->sortdir = ($s[1] ?? '') === 'desc' ? 'DESC' : 'ASC';
    }

    /* --------------------------- Фильтры min / max --------------------------- */

    protected function processPriceFilters()
    {
        foreach ($this->props as $key => $val) {
            if (preg_match('/min_|max_/i', $key)) {
                $keys = explode('_', $key);
                $sign = $keys[0] == 'min' ? '>=' : '<=';

                if ($keys[1] === 'price') {
                    $this->priceWhere[] = "product_data.price {$sign} " . (int)$val;
                }
            }
        }
    }

    /* -------------------------- Получение товаров -------------------------- */

    protected function getItems()
    {
        $limit = $this->limitBase + ($this->extraLoad * $this->limitBase);
        $offset = ($this->page - 1) * $this->limitBase;

        $q = $this->modx->newQuery('msCategory');
        $q->distinct();

        // join к подтоварам (msProduct → parent = category.id)
        $q->leftJoin('msProduct', 'msProduct', 'msProduct.parent = msCategory.id');
        $q->leftJoin('msProductData', 'product_data', 'product_data.id = msProduct.id');

        foreach ($this->joins as $join) {
            $q->leftJoin($join['class'], $join['alias'], $join['on']);
        }

        $q->where([
            'msCategory.parent:IN' => $this->categoryIds,
            'msCategory.template' => $this->productTemplateId
        ]);
        $q->where($this->where);
        $q->where($this->priceWhere);

        $q->select([
            'msCategory.id',
            'msCategory.pagetitle',
            'msCategory.uri',
            'MIN(product_data.price) AS price'
        ]);

        $q->groupby('msCategory.id');

        $q->sortby($this->sortby, $this->sortdir);
        $q->limit($limit, $offset);

        if ($q->prepare() && $q->stmt->execute()) {
            return $q->stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /* ------------------------- Подсчёт количества ------------------------- */

    protected function getTotal()
    {
        $q = $this->modx->newQuery('msCategory');
        $q->distinct();
        $q->leftJoin('msProduct', 'msProduct', 'msProduct.parent = msCategory.id');
        $q->leftJoin('msProductData', 'product_data', 'product_data.id = msProduct.id');

        foreach ($this->joins as $join) {
            $q->leftJoin($join['class'], $join['alias'], $join['on']);
        }

        $q->where(['msCategory.parent:IN' => $this->categoryIds]);
        $q->where($this->where);
        $q->where($this->priceWhere);

        $q->select('COUNT(DISTINCT msCategory.id)');

        $q->prepare();
        $q->stmt->execute();
        return (int)$q->stmt->fetchColumn();
    }

    /* ----------------------------- Loadmore check ----------------------------- */

    protected function hasMore(int $total)
    {
        $shown = ($this->page * $this->limitBase) + ($this->extraLoad * $this->limitBase);
        return $total > $shown;
    }

    /* ------------------------------- Пагинация ------------------------------- */

    protected function renderPagination($totalPages, $currentPage)
    {
        if ($totalPages <= 1) return '';

        $alias = $this->modx->resource->get('alias');

        $html = '<ul class="pagination-list">';
        $pages = [];

        for ($i = 1; $i <= 2 && $i <= $totalPages; $i++) $pages[] = $i;
        for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++)
            if ($i > 2 && $i < $totalPages - 1) $pages[] = $i;
        for ($i = $totalPages - 1; $i <= $totalPages; $i++)
            if ($i > 2) $pages[] = $i;

        $pages = array_unique($pages);
        sort($pages);

        $last = 0;

        foreach ($pages as $p) {
            if ($p - $last > 1) $html .= '<li class="pagination-list-skip">...</li>';

            if ($p == $currentPage) {
                $html .= "<li class=\"pagination-list-active\">$p</li>";
            } else {
                $html .= "<li><a href=\"catalog/$alias\" data-page=\"$p\">$p</a></li>";
            }

            $last = $p;
        }

        $html .= '</ul>';
        return $html;
    }
}

$filter = new CatalogWithVariants($modx);
return $filter->run();