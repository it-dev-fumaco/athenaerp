<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class ReadBrochureSpreadsheetPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $fileContents = ($passable->readFileCallable)($passable->file);

        $passable->fileContents = $fileContents;
        $passable->content = $fileContents['content'];
        $passable->project = isset($fileContents['project']) && $fileContents['project']
            ? trim(str_replace('/', '-', $fileContents['project']))
            : '-';
        $passable->customer = $fileContents['customer'];
        $passable->headers = $fileContents['headers'];
        $passable->tableOfContents = $fileContents['table_of_contents'];

        return $next($passable);
    }
}
