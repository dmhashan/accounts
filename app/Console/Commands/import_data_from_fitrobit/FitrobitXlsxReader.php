<?php

namespace App\Console\Commands\import_data_from_fitrobit;

class FitrobitXlsxReader
{
    /**
     * Read an Excel .xlsx sheet and return rows as associative arrays using the first row as headers.
     *
     * @return array<int, array<string, string>>
     */
    public function readSheet(string $filePath, string $sheetNameOrIndex = ''): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Excel file not found at: {$filePath}");
        }

        $zip = new \ZipArchive;
        $openResult = $zip->open($filePath);

        if ($openResult !== true) {
            throw new \RuntimeException("Could not open Excel archive at: {$filePath} (Error code: {$openResult})");
        }

        try {
            $sharedStrings = $this->loadSharedStrings($zip);
            $targetSheetFile = $this->resolveSheetFile($zip, $sheetNameOrIndex);

            $sheetXml = $zip->getFromName($targetSheetFile);

            if ($sheetXml === false) {
                throw new \RuntimeException("Failed to read worksheet XML: {$targetSheetFile}");
            }

            return $this->parseWorksheet($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadSharedStrings(\ZipArchive $zip): array
    {
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($ssXml === false) {
            return $sharedStrings;
        }

        $doc = simplexml_load_string($ssXml);

        if ($doc === false) {
            return $sharedStrings;
        }

        foreach ($doc->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string) $si->t;
            } elseif (isset($si->r)) {
                $text = '';

                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = '';
            }
        }

        return $sharedStrings;
    }

    private function resolveSheetFile(\ZipArchive $zip, string $sheetNameOrIndex): string
    {
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $relMap = [];

        if ($relsXml !== false) {
            $relsDoc = simplexml_load_string($relsXml);

            if ($relsDoc !== false) {
                foreach ($relsDoc->Relationship as $rel) {
                    $relMap[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        $wbXml = $zip->getFromName('xl/workbook.xml');

        if ($wbXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $wbDoc = simplexml_load_string($wbXml);

        if ($wbDoc === false || !isset($wbDoc->sheets->sheet)) {
            return 'xl/worksheets/sheet1.xml';
        }

        $targetSheetFile = null;

        foreach ($wbDoc->sheets->sheet as $s) {
            $name = (string) $s['name'];
            $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];

            if ($sheetNameOrIndex !== '' && (strcasecmp($name, $sheetNameOrIndex) === 0 || $rId === $sheetNameOrIndex)) {
                $targetRel = $relMap[$rId] ?? 'worksheets/sheet1.xml';
                $targetSheetFile = str_starts_with($targetRel, 'xl/') ? $targetRel : 'xl/' . $targetRel;
                break;
            }
        }

        if (!$targetSheetFile) {
            $firstRid = (string) $wbDoc->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $targetRel = $relMap[$firstRid] ?? 'worksheets/sheet1.xml';
            $targetSheetFile = str_starts_with($targetRel, 'xl/') ? $targetRel : 'xl/' . $targetRel;
        }

        return $targetSheetFile;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>
     */
    private function parseWorksheet(string $sheetXml, array $sharedStrings): array
    {
        $sheetDoc = simplexml_load_string($sheetXml);

        if ($sheetDoc === false || !isset($sheetDoc->sheetData->row)) {
            return [];
        }

        $rawRows = [];

        foreach ($sheetDoc->sheetData->row as $r) {
            $row = [];

            foreach ($r->c as $c) {
                $cellRef = (string) $c['r'];
                preg_match('/^([A-Z]+)(\d+)/', $cellRef, $matches);
                $colLetter = $matches[1] ?? $cellRef;
                $type = (string) $c['t'];
                $val = (string) $c->v;

                if ($type === 's' && $val !== '' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                }

                $row[$colLetter] = trim($val);
            }
            $rawRows[] = $row;
        }

        if (count($rawRows) === 0) {
            return [];
        }

        $headerRow = $rawRows[0];
        $namedRows = [];

        for ($i = 1; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            $namedRow = [];

            foreach ($headerRow as $colLetter => $colName) {
                if ($colName === '') {
                    continue;
                }
                $namedRow[$colName] = $row[$colLetter] ?? '';
            }

            $namedRows[] = $namedRow;
        }

        return $namedRows;
    }
}
