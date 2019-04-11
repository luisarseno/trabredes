<?php

require_once 'FileHelper.php';

class Client{

    protected $serverIp;
    protected $serverPort;
    protected $socket;
    protected $nickname;
    protected $path;

    public function __construct($serverIp, $serverPort, $path) {
        $this->serverIp = $serverIp;
        $this->serverPort = $serverPort;
        $this->criaSocket();
        $this->path = $path;
    }

    public function loadFiles(){
        if(is_dir($this->path)){
            $files = glob($this->path."/*");
            foreach ($files as $file){
                $message = json_encode(array(
                    'action' => 'loadfile',
                    'file'   => str_replace($this->path."/","", $file),
                    'hash'   => md5(uniqid().$file)
                ));
                socket_sendto($this->socket, $message , strlen($message) , 0 , $this->serverIp , $this->serverPort);
            }
        }
    }

    /**
     * Abre socket UDP
     */
    private function criaSocket(){
        if(!($this->socket = socket_create(AF_INET, SOCK_DGRAM, 0))){
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("N�o pode criar o socket: [$errorcode] $errormsg \n");
        }
    }


    /**
     * Starta a comunica��o com o servidor e printa as informa��es na tela do cliente
     */
    public function startClient(){
        while(1)
        {
            $this->printaInstrucoes();
            $mensagem = fgets(STDIN);
            if(strstr($mensagem, "cansei")){
                echo "Finalizando programa\n";
                break;
            }

            //Manda mensagem pro servidor
            if( ! socket_sendto($this->socket, $mensagem[1] , strlen($mensagem[1]) , 0 , $this->serverIp , $this->serverPort))
            {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                echo "Erro ao mandar mensagem pro servidor: [$errorcode] $errormsg \n";
            }

            //Recebe retorno do servidor
            if(socket_recv ( $this->socket , $resposta , 2045 , MSG_WAITALL ) === FALSE)
            {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                echo "Erro ao receber dados: [$errorcode] $errormsg \n";
            }

            $this->printaRetornoServer($resposta);
        }
    }

    /**
     * Trata mensagem que vai ser enviada para o servidor
     * @param $mensagem
     * @return array false|true e mensagem
     */
    private function trataMensagem($mensagem){
        $mensagem = explode(" ", $mensagem);
        $tipo     = $mensagem[0];
        $para     = $mensagem[1];
        $text     = "";
        for ($i = 2 ; $i <= count($mensagem); $i++){
            $text .= $mensagem[$i]." ";
        }

        if(!in_array($tipo, array('file','text'))){
            return array(false, 'Tipo '.$tipo.' eh invalido');
        } elseif (!$para){
            return array(false, 'Destinat�rio invalido');
        } elseif ($text == ""){
            return array(false, 'Mensagem invalida');
        } else {
            $tipo = ($tipo == 'file') ? 'A' : 'M';
            if($tipo == 'A' AND !is_file(rtrim(str_replace(" ", "",$text), "\n"))){
                return array(false, 'Arquivo nao existe!!');
            } else {
                $text = ($tipo == 'A') ? file_get_contents(rtrim(str_replace(" ", "",$text), "\n")) : $text;
                $data = "2345;naocopiado:" . $this->nickname . ":" . $para . ":" . $tipo . ":" . rtrim($text, "\n\t");
                return array(true, $data);
            }
        }

    }

    /**
     * Printa a mensagem que veio do servidor pro cliente
     * @param $retorno que veio do servidor
     */
    private function printaRetornoServer($retorno){
        echo str_pad("=", 80, "=")."\n\n";
        echo "Mensagem servidor: \t".$retorno."\n";
        echo str_pad("=", 80, "=")."\n\n";
    }

    /**
     * Printa as instru��e de como mandar uma mensagem pro cliente
     */
    private function printaInstrucoes(){
        echo "Para enviar mensagem digite: \"text nome-do-destinatario mensagem\" \n";
        echo "Para enviar arquivo digite: \"file nome-do-destinatario mensagem\" \n";
        echo "Para sair digite \"cansei\"\n";
        echo "\tDigite a mensagem: ";
    }
}