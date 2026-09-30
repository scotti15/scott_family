<?php

function parseSrtFile(string $filePath): array
{
    $parsedEntries = [];
/**********************************
 * READ AND NORMALIZE FILE ENCODING
 **********************************/

$fileContents = file_get_contents($filePath);

if ($fileContents === false) {
    return [];
}

/*
 * SRT files may arrive as UTF-8, UTF-8 with BOM,
 * Windows-1252, or ISO-8859-1.
 *
 * Normalize the contents to UTF-8 before parsing.
 */

$fileContents = preg_replace('/^\xEF\xBB\xBF/', '', $fileContents);

if (!mb_check_encoding($fileContents, 'UTF-8')) {
    $fileContents = mb_convert_encoding(
        $fileContents,
        'UTF-8',
        'Windows-1252'
    );
}

$fileContents = str_replace("\r\n", "\n", $fileContents);
    
    $fileContents = str_replace("\r", "\n", $fileContents);

    $blocks = preg_split(
        "/\n\s*\n/",
        trim($fileContents)
    );

    foreach ($blocks as $block) {

        $lines = explode("\n", trim($block));

        if (count($lines) < 3) {
            continue;
        }

        $subtitleNumber = trim($lines[0]);
        $timestampOriginal = trim($lines[1]);

        $timestamps = explode(
            '-->',
            $timestampOriginal,
            2
        );

        if (count($timestamps) !== 2) {
            continue;
        }

        $startTime = trim($timestamps[0]);
        $endTime = trim($timestamps[1]);

        $text = implode(
            "\n",
            array_slice($lines, 2)
        );

        $parsedEntries[] = [
            'subtitle_number' => $subtitleNumber,
            'timestamp_original' => $timestampOriginal,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'text' => $text
        ];
    }

    return $parsedEntries;
}