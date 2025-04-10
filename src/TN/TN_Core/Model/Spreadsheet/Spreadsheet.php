<?php

namespace TN\TN_Core\Model\Spreadsheet;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use TN\TN_Core\Error\ValidationException;

class Spreadsheet
{
    /** @var string[] the headers */
    public array $headers = [];

    /** @var object[] the rows - each is an object of the data */
    public array $rows = [];

    /** @var array on reading in headers, rename them according to this array */
    public static array $headerTranslations = [];

    /**
     * gets a spreadsheet from a string of the spreadsheet's contents
     * @param string $contents
     * @return Spreadsheet
     * @throws ValidationException
     */
    public static function fromString(string $contents): static
    {
        // let's just split into headers and rows
        $lines = preg_split('/\\r?\\n/', $contents);
        $headers = array_shift($lines);

        // Use first line to choose a tokenize character for all the lines.
        $tokens = ["\t", ",", ";"];
        $token = false;
        foreach ($tokens as $tokenI) {
            if (str_contains($headers, $tokenI)) {
                $token = $tokenI;
                break;
            }
        }

        if ($token === false) {
            $token = $tokens[0];
        }

        $headers = explode($token, strtolower($headers));
        $class = get_called_class();
        foreach ($headers as &$header) {
            $header = trim($header);
            if (isset($class::$headerTranslations[$header])) {
                $header = $class::$headerTranslations[$header];
            }
        }

        $rows = [];
        foreach ($lines as $line) {
            $numerativeRow = explode($token, $line);
            $row = new \stdClass;
            unset($header);
            foreach ($headers as $i => $header) {
                $row->$header = $numerativeRow[$i] ?? '';
            }
            $rows[] = $row;
        }
        return new static($headers, $rows);
    }

    /**
     * constructor
     * @param array $headers
     * @param array $rows
     */
    public function __construct(array $headers, array $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
    }

    /**
     * deletes rows that contain no data
     * @return void
     */
    public function deleteEmptyRows(): void
    {
        $newRows = [];
        foreach ($this->rows as $row) {
            $empty = true;
            foreach ($this->headers as $header) {
                if (!empty($row->$header)) {
                    $empty = false;
                    break;
                }
            }
            if (!$empty) {
                $newRows[] = $row;
            }
        }
        $this->rows = $newRows;
    }

    /**
     * @return string[]
     */
    protected function getRenderHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return object[]
     */
    protected function getRenderRows(): array
    {
        return $this->rows;
    }

    /**
     * render the spreadsheet
     * @param string $format
     * @return string
     * @throws ValidationException
     */
    public function render(string $format = 'csv'): string
    {
        $headers = $this->getRenderHeaders();
        $rows = $this->getRenderRows();
        switch ($format) {
            case 'csv':
                return $this->renderCsv($headers, $rows);
                break;
            default:
                throw new ValidationException('Invalid spreadsheet render format: ' . $format);
        }
    }

    /**
     * render the csv
     * @param array $headers
     * @param array $rows
     * @return string
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function renderCsv(array $headers, array $rows): string
    {
        $csv = Writer::createFromString();
        $csv->insertOne($headers);
        $csvRows = [];
        foreach ($rows as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row->$header ?? '';
            }
            $csvRows[] = $csvRow;
        }
        $csv->insertAll($csvRows);
        return $csv->toString();
    }
}
