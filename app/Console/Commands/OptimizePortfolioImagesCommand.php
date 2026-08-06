<?php

namespace App\Console\Commands;

use App\Models\PortfolioItem;
use App\Models\PortfolioItemImage;
use App\Models\PortfolioItemVariant;
use App\Support\PortfolioImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizePortfolioImagesCommand extends Command
{
    protected $signature = 'portfolio:optimize-images
                            {--dry-run : Report only, do not write files}
                            {--limit=0 : Max number of source images to process (0 = all)}';

    protected $description = 'Generate missing thumbnails for existing portfolio product images';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $disk = Storage::disk('public');
        $written = 0;
        $skipped = 0;
        $seen = 0;

        $paths = collect()
            ->merge(PortfolioItem::query()->whereNotNull('image_url')->pluck('image_url'))
            ->merge(PortfolioItemImage::query()->where(function ($q) {
                $q->whereNull('media_type')->orWhere('media_type', PortfolioItemImage::TYPE_IMAGE);
            })->pluck('image_path'))
            ->merge(PortfolioItemVariant::query()->whereNotNull('image_path')->pluck('image_path'))
            ->filter(fn ($path) => is_string($path) && $path !== '' && ! str_starts_with($path, 'http'))
            ->unique()
            ->values();

        foreach ($paths as $path) {
            if ($limit > 0 && $seen >= $limit) {
                break;
            }

            if (! $disk->exists($path)) {
                $skipped++;
                continue;
            }

            $thumb = PortfolioImageOptimizer::thumbPathFor($path);
            if ($disk->exists($thumb)) {
                $skipped++;
                continue;
            }

            $seen++;
            if ($dryRun) {
                $this->line("[dry-run] would write {$thumb}");
                continue;
            }

            if (PortfolioImageOptimizer::ensureThumb($path)) {
                $written++;
                $this->line("thumb: {$thumb}");
            } else {
                $skipped++;
            }
        }

        $this->info("Done. written={$written} skipped={$skipped}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
