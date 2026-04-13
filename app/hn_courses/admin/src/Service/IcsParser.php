<?php
declare(strict_types=1);

namespace HnCourses\Admin\Service;

use DateTimeImmutable;
use DateTimeZone;

final class IcsParser
{
    /**
     * Parser ICS-innhold og returnerer events i format:
     * [
     *   [
     *     'start' => '2026-04-20 07:00:00',
     *     'end' => '2026-04-20 09:15:00',
     *     'summary' => 'Norskkurs',
     *     'location' => 'Haukeveien 7',
     *     'description' => '...'
     *   ]
     * ]
     *
     * Dette dekker vanlige RFC5545-varianter for VEVENT-import:
     * - folded lines
     * - DTSTART/DTEND med params
     * - TZID
     * - UTC med Z
     * - VALUE=DATE
     * - escaped tekst
     */
    public static function parse(string $ics, ?string $defaultTimezone = 'Europe/Oslo'): array
    {
        $ics = trim($ics);
        if ($ics === '') {
            return [];
        }

        $lines = self::splitAndUnfoldLines($ics);

        $events = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                if (is_array($current)) {
                    $normalized = self::normalizeEvent($current, $defaultTimezone);
                    if ($normalized !== null) {
                        $events[] = $normalized;
                    }
                }
                $current = null;
                continue;
            }

            if (!is_array($current) || $line === '') {
                continue;
            }

            [$name, $params, $value] = self::parseContentLine($line);

            switch ($name) {
                case 'DTSTART':
                    $current['DTSTART'] = [
                        'value'  => $value,
                        'params' => $params,
                    ];
                    break;

                case 'DTEND':
                    $current['DTEND'] = [
                        'value'  => $value,
                        'params' => $params,
                    ];
                    break;

                case 'SUMMARY':
                    $current['SUMMARY'] = self::decodeText($value);
                    break;

                case 'LOCATION':
                    $current['LOCATION'] = self::decodeText($value);
                    break;

                case 'DESCRIPTION':
                    $current['DESCRIPTION'] = self::decodeText($value);
                    break;

                default:
                    // Ignorer andre felt foreløpig
                    break;
            }
        }

        return $events;
    }

    /**
     * Deler opp linjer og håndterer folded lines iht. RFC5545:
     * linjer som starter med space eller tab er fortsettelse av forrige linje.
     */
    private static function splitAndUnfoldLines(string $ics): array
    {
        $rawLines = preg_split('/\r\n|\r|\n/', $ics) ?: [];

        $lines = [];
        foreach ($rawLines as $rawLine) {
            if ($rawLine === '') {
                $lines[] = '';
                continue;
            }

            if (!empty($lines) && (str_starts_with($rawLine, ' ') || str_starts_with($rawLine, "\t"))) {
                $lines[count($lines) - 1] .= substr($rawLine, 1);
            } else {
                $lines[] = $rawLine;
            }
        }

        return $lines;
    }

    /**
     * Parser en content line som f.eks.
     * DTSTART;TZID=Europe/Oslo:20260420T070000
     * SUMMARY:Hei
     *
     * Returnerer:
     * [name, params, value]
     */
    private static function parseContentLine(string $line): array
    {
        $pos = strpos($line, ':');
        if ($pos === false) {
            return [strtoupper($line), [], ''];
        }

        $left  = substr($line, 0, $pos);
        $value = substr($line, $pos + 1);

        $parts = explode(';', $left);
        $name = strtoupper(array_shift($parts));

        $params = [];
        foreach ($parts as $part) {
            $eqPos = strpos($part, '=');
            if ($eqPos === false) {
                $params[strtoupper($part)] = true;
                continue;
            }

            $paramName = strtoupper(substr($part, 0, $eqPos));
            $paramValue = substr($part, $eqPos + 1);
            $params[$paramName] = $paramValue;
        }

        return [$name, $params, $value];
    }

    /**
     * Normaliserer ett event til DB-format.
     */
    private static function normalizeEvent(array $event, ?string $defaultTimezone): ?array
    {
        if (empty($event['DTSTART']['value'])) {
            return null;
        }

        $start = self::parseDateValue(
            $event['DTSTART']['value'],
            $event['DTSTART']['params'] ?? [],
            $defaultTimezone
        );

        if ($start === null) {
            return null;
        }

        $end = null;
        if (!empty($event['DTEND']['value'])) {
            $end = self::parseDateValue(
                $event['DTEND']['value'],
                $event['DTEND']['params'] ?? [],
                $defaultTimezone
            );
        }

        return [
            'start'       => $start,
            'end'         => $end,
            'summary'     => $event['SUMMARY'] ?? null,
            'location'    => $event['LOCATION'] ?? null,
            'description' => $event['DESCRIPTION'] ?? null,
        ];
    }

    /**
     * Parser dato/tid-varianter:
     * - 20260420T070000
     * - 20260420T070000Z
     * - 20260420 (VALUE=DATE)
     */
    private static function parseDateValue(string $value, array $params, ?string $defaultTimezone): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $tzid = isset($params['TZID']) && is_string($params['TZID']) && $params['TZID'] !== ''
            ? $params['TZID']
            : ($defaultTimezone ?: 'Europe/Oslo');

        $valueType = strtoupper((string)($params['VALUE'] ?? ''));

        try {
            if ($valueType === 'DATE' || preg_match('/^\d{8}$/', $value)) {
                $dt = DateTimeImmutable::createFromFormat(
                    'Ymd',
                    $value,
                    new DateTimeZone($tzid)
                );

                if (!$dt) {
                    return null;
                }

                return $dt->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            }

            if (preg_match('/^\d{8}T\d{6}Z$/', $value)) {
                $dt = DateTimeImmutable::createFromFormat(
                    'Ymd\THis\Z',
                    $value,
                    new DateTimeZone('UTC')
                );

                if (!$dt) {
                    return null;
                }

                return $dt->setTimezone(new DateTimeZone($tzid))->format('Y-m-d H:i:s');
            }

            if (preg_match('/^\d{8}T\d{6}$/', $value)) {
                $dt = DateTimeImmutable::createFromFormat(
                    'Ymd\THis',
                    $value,
                    new DateTimeZone($tzid)
                );

                if (!$dt) {
                    return null;
                }

                return $dt->format('Y-m-d H:i:s');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * RFC5545 text escaping:
     * \\n  -> newline
     * \\;  -> ;
     * \\,  -> ,
     * \\\\ -> \
     */
    private static function decodeText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace('\n', "\n", $value);
        $value = str_replace('\N', "\n", $value);
        $value = str_replace('\,', ',', $value);
        $value = str_replace('\;', ';', $value);
        $value = str_replace('\\\\', '\\', $value);

        return trim($value);
    }
}