<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Tools\Libraries;

/**
 * Provides view cells for Users
 */
class Logs
{
    public const MAX_LOG_SIZE           = 52428800; // 50MB
    public const MAX_STRING_LENGTH      = 300; // 300 chars
    public const LOG_LINE_START_PATTERN = '/((INFO)|(ERROR)|(CRITICAL)|(DEBUG)|(ALL))[\\s\\-\\d:\\.\\/]+(-->)/';
    public const LOG_DATE_PATTERN       = ['/^((ERROR)|(CRITICAL)|(INFO)|(DEBUG)|(ALL))\\s\\-\\s/', '/\\s(-->)/'];
    public const LOG_LEVEL_PATTERN      = '/^((ERROR)|(CRITICAL)|(INFO)|(DEBUG)|(ALL))/';

    private static $levelsIcon = [
        'INFO'     => 'fas fa-info-circle',
        'ERROR'    => 'fas fa-times',
        'CRITICAL' => 'fas fa-exclamation-triangle',
        'DEBUG'    => 'fas fa-bug',
        'ALL'      => 'fas fa-minus',
    ];
    private static $levelClasses = [
        'INFO'     => 'info',
        'ERROR'    => 'danger',
        'CRITICAL' => 'danger',
        'DEBUG'    => 'warning',
        'ALL'      => 'muted',
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
            } elseif (! empty($superLog)) {
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

    /**
     * returns an array of the file contents
     * each element in the array is a line
     * in the underlying log file
     *
     * @returns array | each line of file contents is an entry in the returned array.
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
        if (! empty($matches)) {
            return $matches[0];
        }

        return '';
    }
}
