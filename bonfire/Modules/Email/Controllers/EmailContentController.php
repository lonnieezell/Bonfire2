<?php

namespace Bonfire\Modules\Email\Controllers;

use App\Controllers\AdminController;
use EmailQueue\Models\EmailQueueModel;
use EmailQueue\EmailQueue;

class EmailContentController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Email\Views\\';

    /**
     * Display the Email queue page.
     *
     * @return string
     */
	public function index(){

    helper('text');

    EmailQueue::enqueue('ruzkleberyahoobr', 'Teste alfa', array('message'=>'alfabeta'));

    $pager = \Config\Services::pager();

    $queue = new EmailQueueModel();

    $emails = $queue->getBatch();

    $page = isset($_GET['page']) ? $_GET['page'] : 1;

    $total = count($emails);

    $limit = 12;

    $offset = ($page > 1)? ($page-1)*$limit: 0;

    $pager->makeLinks($page,$limit,$total);

    $emails = array_slice($emails, $offset, $limit);

		return $this->render($this->viewPrefix .'email_queue', [
            'headers' => [
    		            'email' => 'To Email',
    		            'subject' => 'Subject',
                    'attempts' => 'Attempts',
                    'sent' => 'Sent',
                    'created_at' => 'Created at',
                ],
    		    'showSelectAll' => true,
            'queue' => $emails,
            'pager' => $pager,
        ]);
	}


    /**
     * Display the Email preview.
     *
     * @return string
     */
	public function preview(int $id){

    $queue = new EmailQueueModel();

    $email = $queue->find($id);

		return $this->render($this->viewPrefix .'email_preview', [
            'email' => $email,
        ]);
	}


    /**
     * Process queue.
     *
     * @return string
     */
	public function process_queue(){

		 if(EmailQueue::process()){

     return redirect()->to(ADMIN_AREA.'/content/email')->with('message', lang('Email.queue_process_success'));

   }else{

      return redirect()->back()->with('error', lang('Email.queue_process_failed'));

   }
}


  /**
   * Delete the specified email in queue.
   *
   * @return \CodeIgniter\HTTP\RedirectResponse
   */
  public function delete(){

    if (isset($_POST['delete'])) {

      $queue = new EmailQueueModel();

        $checked = $_POST['checked'];
        $numChecked = count($checked);

        if (is_array($checked) && $numChecked) {

            foreach ($checked as $id) {
                $queue->delete($id);
            }

          return redirect()->to(ADMIN_AREA.'/content/email')->with('message', lang('Email.delete_success'));

        }
      }

    }


}
