<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Libraries;

use DateTime;

/**
 * Provides view cells for Users
 */
class Logs
{
    public const MAX_LOG_SIZE           = 52428800; // 50MB
    public const MAX_STRING_LENGTH      = 300; // 300 chars
    public const LOG_LINE_START_PATTERN = '/((DEBUG)|(INFO)|(NOTICE)|(WARNING)|(ERROR)|(CRITICAL)|(ALERT)|(EMERGENCY))[\\s\\-\\d:\\.\\/]+(-->)/';
    public const LOG_DATE_PATTERN       = ['/^((DEBUG)|(INFO)|(NOTICE)|(WARNING)|(ERROR)|(CRITICAL)|(ALERT)|(EMERGENCY))\\s\\-\\s/', '/\\s(-->)/'];
    public const LOG_LEVEL_PATTERN      = '/^((DEBUG)|(INFO)|(NOTICE)|(WARNING)|(ERROR)|(CRITICAL)|(ALERT)|(EMERGENCY))/';

    private static $levelsIcon = [
        'DEBUG'     => 'fas fa-bug',
        'INFO'      => 'fas fa-info-circle',
        'NOTICE'    => 'fas fa-info-circle',
        'WARNING'   => 'fas fa-times',
        'ERROR'     => 'fas fa-times',
        'CRITICAL'  => 'fas fa-exclamation-triangle',
        'ALERT'     => 'fas fa-exclamation-triangle',
        'EMERGENCY' => 'fas fa-exclamation-triangle',
    ];
    private static $levelClasses = [
        'DEBUG'     => 'warning',
        'INFO'      => 'info',
        'NOTICE'    => 'info',
        'WARNING'   => 'warning',
        'ERROR'     => 'danger',
        'CRITICAL'  => 'danger',
        'ALERT'     => 'danger',
        'EMERGENCY' => 'danger',
    ];

    /**
     * This function will process the logs. Extract the log level, icon class and other information
     * from each line of log and then arrange them in another array that is returned to the view for processing
     *
     * @params logs. The raw logs as read from the log file
     *
     * @param mixed $file
     *
     * @return array. An [[], [], [] ...] where each element is a processed log line
     * */
    public function processFileLogs($file)
    {
        if (null === $file) {
            return [];
        }

        $logs = $this->getLogs($file);

        $superLog = [];

        foreach ($logs as $log) {
            // get the logLine Start
            $logLineStart = $this->getLogLineStart($log);

            if (! empty($logLineStart)) {
                // this is actually the start of a new log and not just another line from previous log
                $level = $this->getLogLevel($logLineStart);
                $data  = [
                    'level' => $level,
                    'date'  => $this->getLogDate($logLineStart),
                    'icon'  => self::$levelsIcon[$level],
                    'class' => self::$levelClasses[$level],
                ];

                $logMessage = preg_replace(self::LOG_LINE_START_PATTERN, '', $log);

                if (strlen($logMessage) > self::MAX_STRING_LENGTH) {
                    $data['content'] = substr($logMessage, 0, self::MAX_STRING_LENGTH);
                    $data['extra']   = substr($logMessage, (self::MAX_STRING_LENGTH + 1));
                } else {
                    $data['content'] = $logMessage;
                }

                $superLog[] = $data;
            } elseif ($superLog !== []) {
                // this log line is a continuation of previous logline
                // so let's add them as extra
                $prevLog                        = $superLog[count($superLog) - 1];
                $extra                          = (array_key_exists('extra', $prevLog)) ? $prevLog['extra'] : '';
                $prevLog['extra']               = $extra . "\n" . $log;
                $superLog[count($superLog) - 1] = $prevLog;
            }
        }

        return $superLog;
    }

    public function countLogLevels($filePath): string
    {
        $levels = array_keys(self::$levelClasses);

        // Initialize the counts array
        $counts = array_fill_keys($levels, 0);

        // Read the file content
        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            throw new Exception("Unable to read the file: {$filePath}");
        }

        // Count occurrences of each level
        foreach ($levels as $level) {
            $counts[$level] = substr_count($fileContent, $level);
        }

        // Remove entries with value 0
        $counts = array_filter($counts, static fn ($value) => $value > 0);

        $counts = array_reverse($counts);


        // Transform the array into a string with color codes
        $result = [];
        foreach ($counts as $level => $count) {
            $class = self::$levelClasses[$level];
            $result[] = '<span class="text-' . $class . '">' . $level . '</span>: ' . $count;
        }

        return strtolower(implode(', ', $result));
    }


    /**
     * returns an array of the file contents
     * each element in the array is a line
     * in the underlying log file
     *
     * @returns array | each line of file contents is an entry in the returned array.
     *
     * @params complete fileName
     *
     * @param mixed $fileName
     * */
    public function getLogs($fileName)
    {
        $size = filesize($fileName);
        if (! $size || $size > self::MAX_LOG_SIZE) {
            return null;
        }

        return file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * This function will paginate logs files lines or logs files.
     *
     * @param array $logs.  The raw logs as read from the log file or log files.
     * @param int   $limit. Number of results per page.
     *
     * @return array with pager object and filtered array.
     */
    public function paginateLogs(array $logs, int $limit)
    {
        $pager  = service('pager');
        $page   = $_GET['page'] ?? 1;
        $offset = ($page > 1) ? ($page - 1) * $limit : 0;

        $pager->makeLinks($page, $limit, count($logs));

        return ['pager' => $pager, 'logs' => array_slice($logs, $offset, $limit)];
    }

    /**
     * Retrieves the adjacent log files (previous and next) relative to the given log file.
     *
     * @param string $currentFile The current log file name.
     * @param array $logFiles An array of all log file names.
     * @return array An associative array with 'previous' and 'next' keys containing the respective log file names, or null if not available.
     */
    public function getAdjacentLogFiles($currentLogFileBasename, $logsPath): array
    {
        // Extract the date from the current log file name
        preg_match('/log-(\d{4}-\d{2}-\d{2})/', $currentLogFileBasename, $matches);
        $currentDate = new DateTime($matches[1]);

        // Retrieve the list of log files in the directory
        $logFiles = glob($logsPath . '/log-*.log');

        // Extract dates from the filtered log file names
        $logDates = array_map(static fn ($filePath) => basename($filePath, '.log'), $logFiles);

        // Sort the log files by date
        usort($logDates, static function ($a, $b) {
            // Extract the date part of the log file names for comparison
            $dateA = str_replace('log-', '', $a);
            $dateB = str_replace('log-', '', $b);

            return strcmp($dateA, $dateB);
        });

        // Find the index of the current log file
        $currentLogFileName = basename($currentLogFileBasename, '.log');
        $currentIndex       = array_search($currentLogFileName, $logDates, true);

        // Determine the next and previous log files based on the index
        $previousLogFileBasename = $currentIndex > 0 ? $logDates[$currentIndex - 1] : null;
        $nextLogFileBasename     = $currentIndex < count($logDates) - 1 ? $logDates[$currentIndex + 1] : null;

        return [
            'prev' => [
                'link' => $previousLogFileBasename,
                'label' => substr($previousLogFileBasename ?? '', 4, 10),
            ],
           'curr' => [
                'label' => substr($currentLogFileBasename, 4, 10),
            ],
            'next' => [
                'link' => $nextLogFileBasename,
                'label' => substr($nextLogFileBasename ?? '', 4, 10),
            ],
        ];
    }

    /**
     * extract the log level from the logLine
     *
     * @param string $logLineStart - The single line that is the start of log line.
     *                             extracted by getLogLineStart()
     *
     * @return string Log level e.g. ERROR, DEBUG, INFO
     * */
    private function getLogLevel($logLineStart)
    {
        preg_match(self::LOG_LEVEL_PATTERN, $logLineStart, $matches);

        return $matches[0];
    }

    private function getLogDate($logLineStart)
    {
        return preg_replace(self::LOG_DATE_PATTERN, '', $logLineStart);
    }

    private function getLogLineStart($logLine)
    {
        preg_match(self::LOG_LINE_START_PATTERN, $logLine, $matches);
        if ($matches !== []) {
            return $matches[0];
        }

        return '';
    }
}
