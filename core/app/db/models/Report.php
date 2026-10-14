<?php
namespace App\Models;
use Illuminate\Support\Carbon;

class Report extends \Illuminate\Database\Eloquent\Model {
    public $table = 'app_reports';
    protected $guarded = [];
    protected $appends = ['discount_price', 'final_price', 'price', 'prices', 'evaluation_date', 'wear'];

    protected $casts = [
        'vehicle' => 'array',
        'customer' => 'array',
        'owner' => 'array',
        'sent_emails' => 'array',
        'api_prices' => 'array',
        'api_offers' => 'array',
    ];

    private const TECHNICAL_CONDITION_WEAR = [
        'Новое' => 5,
        'Очень хорошее' => 15,
        'Хорошее' => 30,
        'Удовлетворительное' => 50,
        'Условно пригодное' => 67.5,
        'Неудовлетворительное' => 77.5,
        'Предельное' => 90,
    ];

    public function getWearAttribute(): float
    {
        return self::TECHNICAL_CONDITION_WEAR[$this->vehicle['technical_condition'] ?? ''] ?? 0;
    }

    public function getPricesAttribute(): array
    {
        $prices = [];

        foreach ($this->api_offers ?? [] as $offer) {
            if (!empty($offer['selected']) && isset($offer['price']) && is_numeric($offer['price'])) {
                $prices[] = round((float)$offer['price']);
            }
        }

        if (!$prices) {
            return [
                'avg' => 0,
                'min' => 0,
                'max' => 0,
            ];
        }

        return [
            'avg' => round(array_sum($prices) / count($prices)),
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    public function getPriceAttribute(): int
    {
        return $this->prices['avg'];
    }

    public function getDiscountPriceAttribute(): int
    {
        return round($this->price * $this->discount / 100);
    }

    public function getFinalPriceAttribute()
    {
        //$wear = (int)($this->vehicle['wear'] ?? 0);

        $price = $this->price - $this->discount_price;
        //$price -= $price * $this->wear / 100; // износ убран

        return max(0, round($price));
    }

    // public function getPriceAttribute()
    // {
    //     return $this->api_prices['avg'] ?? 0;;
    // }

    // public function getDiscountPriceAttribute()
    // {
    //     $price = $this->api_prices['avg'] ?? 0;
    //     return round($price * $this->discount / 100);
    // }

    // public function getFinalPriceAttribute()
    // {
    //     $price = $this->api_prices['avg'] ?? 0;
    //     $discountPrice = round($price * $this->discount / 100);
    //     return $price - $discountPrice;
    // }

    // дата отчёта — дата смерти владельца
    public function getEvaluationDateAttribute()
    {
        return $this->owner['deathdate'] ?? '';
        //return $this->report_date ?? '';
    }
}