<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAnalysisSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id',
        'generated_at',
        'period_days',
        'target_days',
        'kpis',
        'stock_predictions',
        'expiry_alerts',
        'anomalies',
        'narrative',
        'methodology',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'kpis' => 'array',
        'stock_predictions' => 'array',
        'expiry_alerts' => 'array',
        'anomalies' => 'array',
        'methodology' => 'array',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
