<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state. A real one-page PDF is written to the private disk.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'title' => Str::title(fake()->words(3, true)),
            'file_path' => fn (array $attributes): string => self::storeSamplePdf($attributes['title']),
            'original_name' => fn (array $attributes): string => Str::slug($attributes['title']).'.pdf',
        ];
    }

    /**
     * Write a sample PDF to the materials directory and return its path.
     */
    public static function storeSamplePdf(string $heading): string
    {
        $path = 'materials/'.Str::uuid().'.pdf';
        Storage::disk(Material::DISK)->put($path, self::samplePdfContents($heading));

        return $path;
    }

    /**
     * A minimal valid one-page PDF showing the given heading.
     */
    public static function samplePdfContents(string $heading): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $heading);
        $stream = "BT /F1 24 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }
}
