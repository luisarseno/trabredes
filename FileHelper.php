<?php
/**
 * Created by PhpStorm.
 * User: santana
 * Date: 03/11/18
 * Time: 18:36
 */

class FileHelper{

    /**
     * Pega o json do arquivo de configuracao
     * @param string $file de configuracao
     * @return mixed
     */
    public static function readFile($file = 'config.json'){
        if(is_file($file)){
            return json_decode(file_get_contents($file),1);
        }
    }

    /**
     * Grava o arquivo quando a mensagem por um file
     * @param $from
     * @param $message
     */
    public static function writeFile($from, $message){
        file_put_contents($from."-".time(), $message);
    }

}