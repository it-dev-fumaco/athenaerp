<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductBrochureLog extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tabProduct Brochure Log';

    protected $primaryKey = 'name';

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * Scope for upload transaction type.
     */
    public function scopeUploads($query): Builder
    {
        return $query->where('transaction_type', 'Upload Excel File');
    }

    /**
     * Scope for recent uploads grouped by project/filename.
     */
    public function scopeRecentUploads($query, ?string $search = null, int $limit = 10)
    {
        return $query->uploads()
            ->when($search, fn ($q) => $q->where('project', 'like', "%{$search}%")->orWhere('filename', 'like', "%{$search}%"))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), DB::raw('MAX(creation) as creation'), 'project', 'filename', DB::raw('MIN(created_by) as created_by'))
            ->groupBy('project', 'filename')
            ->orderByDesc(DB::raw('MAX(creation)'))
            ->limit($limit)
            ->get();
    }

    /**
     * Get human-readable duration from transaction date (e.g. "44m ago", "2h ago").
     */
    public function getHumanDurationAttribute(): string
    {
        $parsedDate = Carbon::parse($this->transaction_date);
        $seconds = (int) now()->diffInSeconds($parsedDate, true);
        $minutes = (int) now()->diffInMinutes($parsedDate, true);
        $hours = (int) now()->diffInHours($parsedDate, true);
        $days = (int) now()->diffInDays($parsedDate, true);
        $months = (int) now()->diffInMonths($parsedDate, true);
        $years = (int) now()->diffInYears($parsedDate, true);

        if ($years >= 1) {
            return $years.'y ago';
        }
        if ($months >= 1) {
            return $months.'mo ago';
        }
        if ($days >= 1) {
            return $days.'d ago';
        }
        if ($hours >= 1) {
            return $hours.'h ago';
        }
        if ($minutes >= 1) {
            return $minutes.'m ago';
        }

        return $seconds.'s ago';
    }
}
