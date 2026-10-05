<?php
namespace App\Core;

use DateTime;
use Exception;

class CSVParser {

    /**
     * Parses a CSV file into an array of associative arrays.
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public static function parse(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("CSV file not found or is not readable.");
        }

        $header = null;
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!$header) {
                    $header = array_map('trim', $row); // Trim whitespace from headers
                } else {
                    if (count($header) !== count($row)) {
                        // Skip rows that don't match the header count
                        continue;
                    }
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Attempts to parse a date from various common formats into 'Y-m-d'.
     * @param string $dateString
     * @return string|null
     */
    public static function normalizeDate(string $dateString): ?string {
        if (empty($dateString)) {
            return null;
        }

        $formats = [
            'Y-m-d', // Standard
            'd-m-Y', // UK/India
            'm/d/Y', // US
            'd/m/Y', // UK/India
            'd.m.Y', // German
            'Y.m.d',
            'd-M-Y', // e.g., 17-Mar-2026
            'd M Y',
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date && $date->format($format) === $dateString) {
                return $date->format('Y-m-d');
            }
        }

        // As a last resort, try strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }

        return null; // Return null if no format matches
    }
}
