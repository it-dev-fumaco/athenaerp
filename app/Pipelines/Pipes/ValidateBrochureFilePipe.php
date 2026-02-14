<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Http\Helpers\ApiResponse;
use Closure;

class ValidateBrochureFilePipe implements Pipe
{
    protected const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'XLSX', 'XLS'];

    public function handle(mixed $passable, Closure $next): mixed
    {
        $file = $passable->file;

        $fileExt = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        if (! in_array($fileExt, self::ALLOWED_EXTENSIONS)) {
            return ApiResponse::failure('Sorry, only .xlsx and .xls files are allowed.');
        }

        $passable->fileExt = $fileExt;

        return $next($passable);
    }
}
