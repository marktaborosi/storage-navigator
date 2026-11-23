<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\I18n;

/**
 * Human-readable sizes and dates for the UI.
 */
final readonly class Formatter
{
    private const array UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    private \DateTimeZone $timezone;

    public function __construct(
        private string $dateFormat,
        ?string $timezone,
        private string $decimalSeparator = '.',
    ) {
        $this->timezone = new \DateTimeZone($timezone ?? date_default_timezone_get());
    }

    /**
     * 0 B, 999 B, 1.5 KB, 12 MB: one decimal below 10, none above.
     */
    public function bytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '';
        }
        $unit = 0;
        $value = (float) max(0, $bytes);
        while ($value >= 1024 && $unit < count(self::UNITS) - 1) {
            $value /= 1024;
            $unit++;
        }
        $decimals = $unit === 0 || $value >= 10 ? 0 : 1;

        return number_format($value, $decimals, $this->decimalSeparator, '') . ' ' . self::UNITS[$unit];
    }

    public function date(?int $timestamp): string
    {
        return $timestamp === null ? '' : $this->dateTime($timestamp)->format($this->dateFormat);
    }

    /**
     * A timestamp in an explicit format, in the configured time zone.
     */
    public function dateIn(?int $timestamp, string $format): string
    {
        return $timestamp === null ? '' : $this->dateTime($timestamp)->format($format);
    }

    /**
     * ISO 8601, for the datetime attribute of <time>.
     */
    public function isoDate(?int $timestamp): string
    {
        return $timestamp === null ? '' : $this->dateTime($timestamp)->format(\DateTimeInterface::ATOM);
    }

    private function dateTime(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone($this->timezone);
    }
}
