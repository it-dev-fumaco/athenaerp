<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\ProductBrochureLog;
use Closure;
use Illuminate\Support\Facades\DB;

class PersistBrochureLogPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if ($passable instanceof \Illuminate\Http\JsonResponse) {
            return $passable;
        }

        DB::table((new ProductBrochureLog)->getTable())->insert([
            'name' => uniqid(),
            'creation' => $passable->transactionDate,
            'modified' => $passable->transactionDate,
            'modified_by' => $passable->request->ip(),
            'owner' => $passable->request->ip(),
            'project' => $passable->project,
            'filename' => $passable->newFilename.'.'.$passable->fileExt,
            'created_by' => $passable->request->ip(),
            'transaction_date' => $passable->transactionDate,
            'remarks' => null,
            'transaction_type' => 'Upload Excel File',
        ]);

        return $next($passable);
    }
}
