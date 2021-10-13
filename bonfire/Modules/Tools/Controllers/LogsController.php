<?php

namespace Bonfire\Tools\Controllers;

use App\Controllers\AdminController;
use CodeIgniter\CodeIgniter;

class LogsController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Tools\Views\\';

    protected $logs_path = WRITEPATH . 'logs/';

    private static $levelsIcon = [
        'INFO'  => 'fas fa-info-circle',
        'ERROR' => 'fas fa-times',
        'CRITICAL' => 'fas fa-exclamation-triangle',
        'DEBUG' => 'fas fa-bug',
        'ALL'   => 'fas fa-minus',
    ];

    private static $levelClasses = [
        'INFO'     => 'info',
        'ERROR'    => 'danger',
        'CRITICAL' => 'danger',
        'DEBUG'    => 'warning',
        'ALL'      => 'muted',
    ];

    const MAX_LOG_SIZE = 52428800; //50MB
    const MAX_STRING_LENGTH = 350; //300 chars

    const LOG_LINE_START_PATTERN = "/((INFO)|(ERROR)|(CRITICAL)|(DEBUG)|(ALL))[\s\-\d:\.\/]+(-->)/";
    const LOG_DATE_PATTERN = ["/^((ERROR)|(CRITICAL)|(INFO)|(DEBUG)|(ALL))\s\-\s/", "/\s(-->)/"];
    const LOG_LEVEL_PATTERN = "/^((ERROR)|(CRITICAL)|(INFO)|(DEBUG)|(ALL))/";

    /**
     * Displays all logs.
     *
     * @return string
     */
    public function index()
    {
        
      if (isset($_POST['delete'])) {

          $checked = $_POST['checked'];
          $numChecked = count($checked);

          if (is_array($checked) && $numChecked) {

              foreach ($checked as $file) {
                  @unlink($this->logs_path . $file);
              }

            return redirect()->to(ADMIN_AREA.'tools/logs')->with('message', lang('Logs.delete_success'));

          }

        } elseif (isset($_POST['delete_all'])) {

          helper('filesystem');

          if (delete_files($this->logs_path)) {

              // Restore the index.html file.
              @copy(APPPATH . '/index.html', "{$this->logs_path}index.html");
              return redirect()->to(ADMIN_AREA.'tools/logs')->with('message', lang('Logs.delete_all_success'));


          } else {
              return redirect()->to(ADMIN_AREA.'tools/logs')->with('error', lang('Logs.delete_error'));
          }
      }

        global $app;
        $db = db_connect();


        helper('form');

        $uri = new \CodeIgniter\HTTP\URI();

        $pager = \Config\Services::pager();

        $page = isset($_GET['page']) ? $_GET['page'] : 1;

        // Load the Log Files.
        $logs = get_filenames($this->logs_path);

        unset($logs[0]);

        $total = count($logs);

        $limit = 15;

        $offset = ($page > 1)? ($page-1)*$limit: 0;

        $pager->makeLinks($page,$limit,$total);

        $logs = array_slice($logs, $offset, $limit);

        return $this->render($this->viewPrefix .'logs', [
            'logs' => $logs,
            'pager' => $pager,
        ]);
    }

    /**
     * Show the contents of a single log file.
     *
     * @param string $file The full name of the file to view (including extension).
     *
     * @return void
     */
    public function view(string $file = '')
    {
        if (empty($file)) {
            Template::set_message(lang('logs_view_empty'), 'danger');
            redirect(SITE_AREA . '/developer/logs');
        }

        helper('form');

        $pager = \Config\Services::pager();

        $path = $this->logs_path. $file;

        $logs =  $this->processLogs($this->getLogs($path));

        $limit = 15;

        $page = isset($_GET['page']) ? $_GET['page'] : 1;

        $offset = ($page > 1)? ($page-1)*$limit: 0;

        $pager->makeLinks($page,$limit,count($logs));

        $logs = array_slice($logs, $offset, $limit);

        return $this->render($this->viewPrefix .'view', [
            'pager' => $pager,
            'log_file' => $file,
            'canDelete' => 1,
            'log_content' => $logs,
            'log_file_pretty' => date('F j, Y', strtotime(str_replace('.log', '', str_replace('log-', '', $file)))),
        ]);

    }


    /*
    * This function will process the logs. Extract the log level, icon class and other information
    * from each line of log and then arrange them in another array that is returned to the view for processing
    *
    * @params logs. The raw logs as read from the log file
    * @return array. An [[], [], [] ...] where each element is a processed log line
    * */
   private function processLogs($logs) {

       if(is_null($logs)) {
           return null;
       }

       $superLog = [];

       foreach ($logs as $log) {

           //get the logLine Start
           $logLineStart = $this->getLogLineStart($log);

           if(!empty($logLineStart)) {
               //this is actually the start of a new log and not just another line from previous log
               $level = $this->getLogLevel($logLineStart);
               $data = [
                   "level" => $level,
                   "date" => $this->getLogDate($logLineStart),
                   "icon" => self::$levelsIcon[$level],
                   "class" => self::$levelClasses[$level],
               ];

               $logMessage = preg_replace(self::LOG_LINE_START_PATTERN, '', $log);

               if(strlen($logMessage) > self::MAX_STRING_LENGTH) {
                   $data['content'] = substr($logMessage, 0, self::MAX_STRING_LENGTH);
                   $data["extra"] = substr($logMessage, (self::MAX_STRING_LENGTH + 1));
               } else {
                   $data["content"] = $logMessage;
               }

               array_push($superLog, $data);

           } else if(!empty($superLog)) {
               //this log line is a continuation of previous logline
               //so let's add them as extra
               $prevLog = $superLog[count($superLog) - 1];
               $extra = (array_key_exists("extra", $prevLog)) ? $prevLog["extra"] : "";
               $prevLog["extra"] = $extra . "<br>" . $log;
               $superLog[count($superLog) - 1] = $prevLog;
           } else {
               //this means the file has content that are not logged
               //using log_message()
               //they may be sensitive! so we are just skipping this
               //other we could have just insert them like this
//               array_push($superLog, [
//                   "level" => "INFO",
//                   "date" => "",
//                   "icon" => self::$levelsIcon["INFO"],
//                   "class" => self::$levelClasses["INFO"],
//                   "content" => $log
//               ]);
           }
       }

       return $superLog;
   }

   /*
    * extract the log level from the logLine
    * @param $logLineStart - The single line that is the start of log line.
    * extracted by getLogLineStart()
    *
    * @return log level e.g. ERROR, DEBUG, INFO
    * */
   private function getLogLevel($logLineStart) {
       preg_match(self::LOG_LEVEL_PATTERN, $logLineStart, $matches);
       return $matches[0];
   }

   private function getLogDate($logLineStart) {
       return preg_replace(self::LOG_DATE_PATTERN, '', $logLineStart);
   }

   private function getLogLineStart($logLine) {
       preg_match(self::LOG_LINE_START_PATTERN, $logLine, $matches);
       if(!empty($matches)) {
           return $matches[0];
       }
       return "";
   }

   /*
    * returns an array of the file contents
    * each element in the array is a line
    * in the underlying log file
    * @returns array | each line of file contents is an entry in the returned array.
    * @params complete fileName
    * */
   private function getLogs($fileName) {
       $size = filesize($fileName);
       if(!$size || $size > self::MAX_LOG_SIZE)
           return null;
       return file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   }


}
