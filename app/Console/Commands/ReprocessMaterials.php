<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMaterial;
use App\Models\Material;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('materials:reprocess')]
#[Description('Queue every lesson PDF for re-processing (page count, thumbnail and search text)')]
class ReprocessMaterials extends Command
{
    public function handle(): int
    {
        $count = 0;

        Material::query()->lazyById()->each(function (Material $material) use (&$count): void {
            ProcessMaterial::dispatch($material);
            $count++;
        });

        $this->components->info("Queued {$count} PDFs for processing.");

        return self::SUCCESS;
    }
}
