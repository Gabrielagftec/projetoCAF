<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Email
 *
 * @author Renan
 */
class LogCFG {

    public $id;
    public $log;
    public $data;
    
    function __construct() {

        $this->id = 0;
        $this->log = "";
        $this->data = round(microtime(true)*1000);
        
    }


    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('".addslashes($this->log)."',FROM_UNIXTIME($this->data/1000))");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE log_cfg SET log='".addslashes($this->log)."',data=FROM_UNIXTIME($this->data/1000) WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
        
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM log_cfg WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
