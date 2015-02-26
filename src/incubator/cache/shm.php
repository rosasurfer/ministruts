<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003-2005 Andrey Hristov                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt                                   |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Andrey Hristov <andrey@php.net>                              |
// +----------------------------------------------------------------------+
//

/************************ CHANGELOG ************************
   10-02-2005 :
   - Moved the sem_get to the constructor of Shm_Semaphore
     Less resources allocated if ::acquire() is used a lot.
   - Changed the license and the copyright holder.
***********************************************************/

/**************************TODO*******************************************
1) To add phpdoc documentation
2) to add methods for setting debug mode
**************************TODO*******************************************/

/********************** TEST CASE ********************
error_reporting(E_ALL);
require_once 'shm.php';

$code_protector = & new Shm_Load_Protector("backends", 5);
$code_protector->_debug = 1;
if ($code_protector->increaseLoad());
   sleep(5);
   $code_protector->decreaseLoad();
} else {
   printf('Too many processes');
}
*****************************************************/


class Shm_SharedObject
{
    function Shm_SharedObject()
    {

    }// Shm_SharedObject

    function _memSegSpot($str)
    {
        $str = str_pad($str, 4, "Z");     // Z = 5a
        $out = '';
        for ($a = 0; $a < 4; $a++) {
            $out1 = substr($str, $a , 1); // walk through
            $out .= dechex(ord($out1));   // ord returns dec, we need hex for shared memory segments
        }// for
        return hexdec("0x".$out);         // prepend it with 0x
    }// _memSegSpot
}// class Shm_SharedObject

class Shm_Semaphore extends Shm_SharedObject
{
    public $_sem_name      = NULL;
    public $_sem_id        = NULL;
    public $_max_acquire   = NULL;
    public $_perm          = NULL;


    function Shm_Semaphore($sem_name, $max_acquire = 1, $perm='666')
    {
        parent::Shm_SharedObject();
        $this->_sem_name = $this->_memSegSpot(substr($sem_name,0,4));
        $this->_max_acquire = $max_acquire;
        $this->_perm = $perm;
        $this->_sem_id = sem_get($this->_sem_name, $this->_max_acquire, OctDec($this->_perm));//only matters the first  time
    }// Shm_Semaphore

    function acquire()
    {
        if (!sem_acquire($this->_sem_id)) {
            printf("cannot acquire semaphore<br>\n");
            return false;
        }// if
        return true;
    }// acquire

    function release()
    {
      if (is_resource($this->_sem_id)) {
         return sem_release($this->_sem_id);
      } else {
         printf("Semaphore was not acquired\n");
         return false;
      }
    }// release

    function destroy() {
      if (is_resource($this->_sem_id)) {
         $r = sem_remove($this->_sem_id);
         $this->_sem_id = NULL;
         return $r;
      } else {
         printf("Sem ID is not resource\n");
         return false;
      }
    }
}// class Shm_Semaphore

class Shm_Var extends Shm_SharedObject
{
    public $_debug = false;

    public $_key = 1;
    public $_shm_name      = NULL;
    public $_shm_id        = NULL;
    public $_memory_size   = NULL;
    public $_perm          = NULL;

    function Shm_Var($shm_name, $memory_size, $perm)
    {
        $this->_shm_name = (int)$this->_memSegSpot(substr($shm_name,0,4));
// $this->_shm_name = ftok('/home/andrey/test/phpconf/shm1.php','T');
        $this->_debug && var_dump($this->_shm_name);
        $this->_memory_size = $memory_size;
        $this->_perm = $perm;
        $this->_shm_id = shm_attach($this->_shm_name, $this->_memory_size, OctDec($this->_perm));
    }// Shm_Var

    function getVar()
    {
        return @shm_get_var($this->_shm_id, $this->_key);
    }// getVar

    function putVar($val)
    {
        return shm_put_var($this->_shm_id, $this->_key, $val);
    }// putVar
}// class Shm_Var


class Shm_Message_Queue extends Shm_SharedObject
{
   public $_debug = false;

   public $_key           = 1;
   public $_shm_name         = NULL;
   public $_msg_queue_id     = NULL;
   public $_memory_size      = NULL;
   public $_perm             = NULL;
   public $_send_options      = array('serialize'=> TRUE, 'blocking' => TRUE, 'message_type' => 1);
   public $_receive_options   = array('unserialize'=> TRUE, 'desired_message_type' => 1, 'flags' => 0, 'max_size' => 16384);
   public $_received_msg      = NULL;
   public $_received_msg_type = 0;
   public $_err_code       = 0;

   function Shm_Message_Queue($shm_name, $size = 16384, $perm = '666')
   {
      $this->_shm_name = (int)$this->_memSegSpot(substr($shm_name,0,4));
      $this->_debug && var_dump($this->shm_name);
      $this->_perm = $perm;
      $this->_memory_size = $size;
      $this->_msg_queue_id = msg_get_queue($this->_shm_name, OctDec($this->_perm));
      if ($this->_msg_queue_id) {
         msg_set_queue ($this->_msg_queue_id, array ('msg_qbytes'=> $size));
         $this->_receive_options['max_size'] = $size;
      }
   }// Shm_Message_Queue
   function setSendOptions($options)
   {
      foreach ($options as $k => $v) {
         if (array_key_exists($k, $this->_send_options)) {
            $this->_send_options[$k] = $v;
         }
      }
   }

   function setReceiveOptions($options)
   {
      foreach ($options as $k => $v) {
         if (array_key_exists($k, $this->_receive_options)) {
            $this->_receive_options[$k] = $v;
         }
      }
   }

   function send($message, $options = array())
   {
      $so = &$this->_send_options;
      $err_code = 0;

      msg_send($this->_msg_queue_id,
               array_key_exists('message_type', $options)?
                  (int) $options['message_type']
                  :
                  (int) $so['message_type'],
               $message,
               array_key_exists('serialize', $options)?
                  (bool) $options['serialize']
                  :
                  (bool) $so['serialize'],
               array_key_exists('blocking', $options)?
                  (bool) $options['blocking']
                  :
                  (bool) $so['blocking'],
               $err_code
         );
      if ($err_code) {
         $this->_err_code = $err_code;
         return FALSE;
      }
      return TRUE;
   }

   function receive($options = array()) {
      $so            = &$this->_receive_options;
      $err_code      = 0;
      $real_msg_type = 0;
      $message       = NULL;

      msg_receive($this->_msg_queue_id,
               (int) array_key_exists('desired_message_type', $options) ? $options['desired_message_type'] : $so['desired_message_type'],
               $real_msg_type,
               (int) array_key_exists('max_size',             $options) ? $options['max_size'            ] : $so['max_size'            ],
               $message,
               (bool) array_key_exists('unserialize',         $options) ? $options['unserialize'         ] : $so['unserialize'         ],
               (bool) array_key_exists('flags',               $options) ? $options['flags'               ] : $so['flags'               ],
               $err_code
         );
      if ($err_code) {
         $this->_err_code = $err_code;
         return FALSE;
      }

      $this->_received_msg      = $message;
      $this->_received_msg_type = $real_msg_type;
      return TRUE;
   }

   function getRecvMsg()
   {
      return $this->_received_msg;
   }

   function getErrorCode()
   {
      return $this->_err_code;
   }

   function getRecvMsgType()
   {
      return $this->_received_msg_type;
   }

}// class Shm_Message_Queue


class Shm_Protected_Var
{
   public $_debug = false;

   public $_sem           = NULL;
   public $_shm_var       = NULL;
   public $_cached_val    = NULL;
   public $_in_section    = false;

    function Shm_Protected_Var($name, $size)
    {
        $sname = substr($name,0,4);
        $this->_debug && printf("NAME=[%s]\n", $sname);
        $this->_sem = & new Shm_Semaphore($sname, 1, '666');
        $this->_shm_var = & new Shm_Var($sname, $size, '666');
    }// Shm_Protected_Var

    function startSection()
    {
        if ($this->_in_section === true) {
            printf("Already in critical section\n");
            return false;
        }// if
        if (!$this->_sem->acquire()) {
            return false;
        }
        $this->_in_section = true;

        return true;
    }// startSection

    function endSection()
    {
        if ($this->_in_section === false) {
            printf("Not in critical section\n");
            return false;
        }// if
        if (!$this->_sem->release()) {
            return false;
        }
        $this->_in_section = FALSE;
    }// endSection


    function setVal($val)
    {
        if ($this->_in_section === false) {
            printf("Not in critical section\n");
            return false;
        }// if
        $this->_cached_val = $val;
        $this->_shm_var->putVar($this->_cached_val);

        return true;
    }// setVal

    function getVal()
    {
        if ($this->_in_section === false) {
            printf("Not in critical section\n");
            return false;
        }// if
        return $this->_shm_var->getVar();
    }// getVal
}// class CProtectedShmVar

class Shm_Load_Protector
{
   public $_running_processes = NULL;

   public $_debug = false;

    function Shm_Load_Protector($code_name, $max_processes)
    {

        $this->_max_processes = $max_processes;
        $this->_running_processes = & new Shm_Protected_Var($code_name, 1024);
    }// Shm_Load_Protector

    function increaseLoad()
    {
        $this->_running_processes->startSection();
        $val = $this->_running_processes->getVal();
        $this->_debug && printf("[RUNNING_PROCESSES=%d]\n", (int)$val);flush();
        $this->_debug && printf("[MAX_RUNNING_PROCESSES=%d]\n", (int) $this->_max_processes);flush();
        if ($val === false) {
            $val = 0;
        } else if ($val >= $this->_max_processes) {
            $this->_running_processes->endSection();
            return false;
        }// if
        $val = (int)$val + 1;
        $this->_running_processes->setVal($val);
        $val = $this->_running_processes->getVal();
        $this->_debug && printf("[RUNNING_PROCESSES=%d]\n", (int)$val);flush();
        $this->_running_processes->endSection();

        return true;
    }// increaseLoad

    function decreaseLoad()
    {
        $this->_running_processes->startSection();
        $val = $this->_running_processes->getVal();
        $this->_debug && printf("[RUNNING_PROCESSES=%d]\n", (int)$val);flush();
        $val--;
        $this->_debug && printf("[RUNNING_PROCESSES=%d]\n", (int)$val);flush();
        $this->_running_processes->setVal($val);
        $this->_running_processes->endSection();
    }// decreaseLoad

    function nullCounterValue()
    {
        $this->_running_processes->startSection();
        $this->_running_processes->setVal(0);
        $this->_running_processes->endSection();
    }// null_counter_value

    function getCounterValue()
    {
        $this->_running_processes->startSection();
        $v = $this->_running_processes->getVal(0);
        $this->_running_processes->endSection();

        return $v;
    }// getCounterValue
}// class Shm_Load_Protector
