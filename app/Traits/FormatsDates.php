<?php
namespace App\Traits;

use Carbon\Carbon;

trait FormatsDates
{
    protected $newDateFormat = 'Y-m-d';

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format($this->newDateFormat);
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format($this->newDateFormat);
    }
}