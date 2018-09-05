<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Export extends CI_Controller {
    
    private $enable_export = TRUE;
    private $enable_captcha = TRUE;
    private $enable_secret = TRUE;
    private $secret_str = 'TheRoadNotTaken';

    public function __construct()
    {
        parent::__construct();
        if($this->enable_export === FALSE)
        {
            show_404();
        }
        $this->_load_dependencies();
        //$this->output->enable_profiler(TRUE);
    }

    private function _load_dependencies()
    {
        $libs = array('zip','table');
        $helpers = array('html','file','form','url');
        $this->load->library($libs);
        $this->load->helpers($helpers);
        if($this->enable_captcha === TRUE)
        {
            $this->enable_captcha = file_exists(APPPATH.'libraries/Ci_captcha.php');
        }
        if($this->enable_captcha){
            $this->load->library('ci_captcha');
            $this->load->helpers('captcha');
        }
    }

    private function _draw_html($html_body="",$title)
    {
        echo doctype('html5').PHP_EOL;
        echo "<html>".PHP_EOL;
        echo "  <head>".PHP_EOL;
        echo "      <title>".$title."</title>".PHP_EOL;
        echo "      <style>body {
        background-color: #fff;
        font: 13px/20px normal Helvetica, Arial, sans-serif;
        color: #4F5155;
    }</style>";
        echo "  </head>".PHP_EOL;
        echo "  <body>".PHP_EOL;
        echo $html_body.PHP_EOL;
        echo "  </body>".PHP_EOL;
        echo "</html>".PHP_EOL;
    }

    private function dlzip()
    {
        if( isset($_POST['ci_core']) && $this->input->post('ci_core') == TRUE )
        {
            $this->export_ci();
        }
        if( isset($_POST['ci_db']) && $this->input->post('ci_db') == TRUE ){
            $this->export_db();
        }
        $this->zip->download('exported.zip');
    }

    private function check_secret()
    {
        if($this->enable_secret === FALSE OR $this->input->post('secret_str') === $this->secret_str)
        {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function index()
    {
        $this->zip->clear_data();
        $captcha_status = '';
        if($this->input->post())
        {
            if(isset($_POST['captcha_verification']) && $this->enable_captcha === TRUE)
            {
                if($this->verify_captcha($this->input->post('captcha_verification')))
                {
                    if( $this->check_secret())
                    {
                        $this->dlzip();
                    } else {
                        $captcha_status = "<div style='background-color:red;color:#fff;border:0px;padding:4px;text-align:center'>Secret Key is incorrect</div><br/>";
                    }
                } else {
                    $captcha_status = "<div style='background-color:red;color:#fff;border:0px;padding:4px;text-align:center'>Captcha Verification Failed</div><br/>";
                }
            } else {
                if($this->enable_captcha === TRUE){
                    $captcha_status = "<div style='background-color:red;color:#fff;border:0px;padding:4px;text-align:center'>Captcha Verification is required</div><br/><br/>";
                } else {
                    if( $this->check_secret())
                    {
                        $this->dlzip();
                    } else {
                        $captcha_status = "<div style='background-color:red;color:#fff;border:0px;padding:4px;text-align:center'>Secret Key is incorrect</div><br/>";
                    }
                }
            }
        }
        $title = "CI Export";
        $hbody = "<div style='width:320px; margin:0px auto;'>";
        $hbody .= heading($title,'1','style="text-align:center;margin:40px"');
        $tbl = array(
                array('','Description',''),
                array(form_checkbox('ci_core','Codeigniter',isset($_POST['ci_core']) && $_POST['ci_core']),'Codeigniter','<code>'.FCPATH.'</code>'),
                array(form_checkbox('ci_db','Database',isset($_POST['ci_db']) && $_POST['ci_db']),'Database')
            );

        $hbody .= $captcha_status;
        $hbody .= form_open();
        $hbody .= $this->table->generate($tbl);
        $checked = ! $this->enable_captcha;
        if($this->enable_secret)
        {
            $hbody .= "<div style='background-color:#d5d5d5;padding:8px; text-align:center; margin:9px'>";
            $hbody .= "Secret Key: ".form_password('secret_str','','placeholder = "Enter Secret Key"');
            $hbody .= "</div>";
        }
        if($this->enable_captcha)
        {
            $captcha = $this->ci_captcha->get_challenge();
            $hbody .= "<div style='border:1px solid #fff;padding:4px;width:150px; margin:15px auto;padding:5px;'>";
            $hbody .= $captcha['image'];
            $hbody .= "<br/>";
            $hbody .= form_input('captcha_verification', '', 'placeholder="Enter Captcha" style="display:block;width:95%"'); 
            $hbody .= "</div>";
        }
        $hbody .= form_submit('formSubmit', 'Download', 'style="display:block; width:100%; padding:10px"');
        $hbody .= "</form></div>";
        $this->_draw_html($hbody,$title);
    }

    private function export_ci($dl = FALSE)
    {
        $path = FCPATH;
        $this->zip->read_dir($path,FALSE);
        if( $dl){
            $this->zip->download('ci.zip');
            exit;
        }
    }

    private function export_db($dl = FALSE)
    {
        $this->load->dbutil();
        $prefs = array(
            'format'    => 'txt',
            'filename'  => 'dbbkp.sql',
            'foreign_key_checks' => FALSE,
            'add_drop'  => FALSE
        );
        $dbbkp = $this->dbutil->backup($prefs);
        $this->zip->add_data('db.sql',$dbbkp);
        if($dl){
            $this->zip->download('db.zip');
            exit;
        }
    }

    private function verify_captcha($word)
    {
        return $this->ci_captcha->check($word,$this->input->ip_address());
    }

}
