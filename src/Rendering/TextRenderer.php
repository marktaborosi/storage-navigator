<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Rendering;

use Marktaborosi\StorageNavigator\Entry\Listing;
use Marktaborosi\StorageNavigator\I18n\Formatter;

/**
 * Plain-text table of a listing, for command-line scripts:
 *
 * <code>
 * echo (new TextRenderer())->render($navigator->listing('reports'));
 * </code>
 */
final readonly class TextRenderer
{
    private Formatter $formatter;

    public function __construct(string $dateFormat = 'Y-m-d H:i', ?string $timezone = null)
    {
        $this->formatter = new Formatter($dateFormat, $timezone);
    }

    public function render(Listing $listing): string
    {
        $rows = [['Name', 'Size', 'Modified']];
        foreach ($listing as $entry) {
            $rows[] = [
                $entry->isDirectory() ? $entry->name() . '/' : $entry->name(),
                $entry->isDirectory() ? '<DIR>' : $this->formatter->bytes($entry->size),
                $this->formatter->date($entry->lastModified),
            ];
        }

        $widths = [0, 0, 0];
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], mb_strwidth($cell));
            }
        }

        $pad = static fn (string $text, int $width, bool $right = false): string => $right
            ? str_repeat(' ', $width - mb_strwidth($text)) . $text
            : $text . str_repeat(' ', $width - mb_strwidth($text));

        $lines = ['/' . $listing->directory->toString()];
        foreach ($rows as $index => $row) {
            $lines[] = $pad($row[0], $widths[0]) . '  ' . $pad($row[1], $widths[1], true) . '  ' . $row[2];
            if ($index === 0) {
                $lines[] = str_repeat('-', $widths[0] + $widths[1] + $widths[2] + 4);
            }
        }
        $lines[] = sprintf('%d folders, %d files, %s', count($listing->directories()), count($listing->files()), $this->formatter->bytes($listing->totalSize()));

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
