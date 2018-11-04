<?php
/**
 * Created by PhpStorm.
 * User: santana
 * Date: 03/11/18
 * Time: 18:35
 */

require_once 'FileHelper.php';
use FileHelper as Helper;


class Server {

    //lista de mensagens
    private $mensagens;

    //servidor ira saber se tem o token ou nao
    private $controleToken;

    //controla se pode enviar mensagem
    private $controleMensagem;

    //variavel responsavel pela configuração
    private $config;

    //token definido no trabalho
    private static $token = 1234;

    //socket
    private $socket;

    public function __construct(){
        $this->config = Helper::readFile();
        $this->controleToken = $this->config['token'];
        if($this->controleToken){
            $this->controleMensagem = true;
        }
        $this->criaSocket();
    }

    private function enfileiraMensagem($mensagem){
        if(count($this->mensagens) >= 10){
            return array(false, '[ERROR] Fila chegou no seu limite');
        }
        $this->mensagens[] = $mensagem;
        return array(true, '[OK] Mensagem enfileirada com sucesso!');
    }

    private function criaSocket(){
        if(!($this->socket = socket_create(AF_INET, SOCK_DGRAM, 0))){
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("Não conseguiu criar o socket [$errorcode] $errormsg \n");
        }
        if( !socket_bind($this->socket, '127.0.0.1' , 6000) ) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("Não conseguiu bindar a porta [$errorcode] $errormsg \n");
        }
    }

    public function startServer(){
        echo "Inicializando servidor\n";
        echo "Ira se comunicar com: ".$this->config['ipDestino'].":".$this->config['porta']."\n";
        while(1){
            echo str_pad("=", 80 , "=")."\n";
            if($this->controleToken){
                if(count($this->mensagens) && $this->controleMensagem){
                    //tenho token e posso mandar mensagens
                    $mensagem = array_shift($this->mensagens);
                    //$this->controleMensagem = false;
                    echo "Desenfileirado mensagem\n";
                } else {
                    //so envio o token
                    echo "Enviando token\n";
                    $mensagem = self::$token;
                    $this->controleToken = false;
                    $this->controleMensagem = false;
                }
                socket_sendto($this->socket, $mensagem, 100 , 0 , $this->config['ipDestino'], $this->config['porta']);
            } else {

                socket_recvfrom($this->socket, $mensagem, 512, 0, $remoteIp, $remotePort);
                if($mensagem != ''){
                    echo "Mensagem recebida: ".$mensagem."\n";
                }
                $retorno = $this->trataRetornoRecebido($remoteIp, $mensagem);

                //se for o ip local manda pra ele de novo, senao manda pro da frente
                $remote = ($remoteIp == '127.0.0.1' AND strstr($mensagem, 'INTERNO')) ? array($remoteIp, $remotePort) : array($this->config['ipDestino'], $this->config['porta']);

                //Envia a mensagem de novo pro cliente
                socket_sendto($this->socket, $retorno[1], 100, 0, $remote[0], $remote[1]);
            }
            sleep($this->config['tempo']);
        }
    }

    private function trataRetornoRecebido($remoteIp, $mensagem){
        if($remoteIp == '127.0.0.1' AND strstr($mensagem, 'INTERNO')){
            return $this->enfileiraMensagem(str_replace('INTERNO', '',$mensagem));
        }

        if(substr($mensagem, 0, 5) == '2345;'){
            $data = explode(":", substr($mensagem, 0, strlen($mensagem)));
            $controle     = $data[0];
            $de           = $data[1];
            $para         = $data[2];
            $tipo         = $data[3];
            $texto        = $data[4];
            if($para == $this->config['apelido']){
                //a mensagem é pra mim
                //aplica propablidade
                if(rand(0,50) <= 10){
                    $controle = 'erro';
                } else {
                    $controle = 'OK';
                }
                if($tipo == 'A'){
                    //tipo A cria um arquivo
                    Helper::writeFile($de,$texto);
                }
                return array(true, '2345;'.$controle.":".$de.":".$para.":".$tipo.":".$texto);
            } elseif($de == $this->config['apelido']){
                //eu que enviei
                if(strstr($controle, 'erro')){
                    //enfileira a mensagem de novo
                    return $this->enfileiraMensagem("2345;naocopiado:".$de.":".$para.":".$tipo.":".$texto);
                }
                //se nao for erro, nao faz nada
            } else {
                //a mensagem é pra outra pessoa, só repassa
                echo "Essa mensagem eh uma retransmissao\n";
                return array(true, $mensagem);
            }
        } elseif($mensagem == self::$token){
            //é o token
            echo "Token recebido com sucesso!\n";
            //seta o controle como true
            $this->controleToken = true;
            //pode enviar mensagem
            $this->controleMensagem = true;
        }

    }





}