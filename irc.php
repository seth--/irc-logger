<?php
//Configure
$host = "ircnode.com";
$port = 6667;
$nick = "l0g-d00d";
$chan = "#sethlandia";
$path = './logs/irc.txt';
$admins = array("seth", "ksha", "p0fk", "S[e]C"); //los que pueden tirarle el comando log
$loging = true; //loguea apenas empieza?
//----------------------
include_once('Net/SmartIRC.php');
    
class logbot
{
    //Prender o apagar el logueo
    function log_cmd(&$irc, &$data)
    {
        global $loging, $admins;
        
        if (in_array($data->nick,$admins))
        {
            $loging = ! $loging;
            if ($loging)
            {
                $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel,
                'Yes sir! echelon mode ON');
            }
            else
            {
                $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel,
                'Yes sir! echelon mode OFF');
            }
        }    
    }
    
    //Muestra si esta logueando
    function log_status(&$irc, &$data)
    {
        global $loging;
        
        if ($loging)
        {
            $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel,
            'Echelon mode ON');
        }
        else
        {
            $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel,
            'Echelon mode OFF');
        }
        
    }
    
    //Guarda en $fileopen todo lo que pasa en $chan
    function log_save(&$irc, &$data)
    {
        global $loging, $fileopen, $chan;
        
        if ($data->type == SMARTIRC_TYPE_JOIN)
        {
            fwrite($fileopen, '*'.$data->nick.'* entra a '.$chan."\n");
        }
        
        elseif ($data->type == SMARTIRC_TYPE_PART)
        {
            fwrite($fileopen, '*'.$data->nick.'* sale de '.$chan.' ('.$data->message.")\n");
        }
        
        elseif ($data->type == SMARTIRC_TYPE_CHANNEL)
        {
            fwrite($fileopen, '<'.$data->nick.'> '.$data->message."\n");
        }
        
        elseif ($data->type == SMARTIRC_TYPE_NOTICE)
        {
            fwrite($fileopen, '>'.$data->nick.'< '.$data->message."\n");
        }        
        
        elseif ($data->type == SMARTIRC_TYPE_TOPICCHANGE)
        {
            fwrite($fileopen, '*'.$data->nick.'* cambió el topic de '.$data->channel.' a '.$data->message."\n");
        }
        
        elseif ($data->type == SMARTIRC_TYPE_TOPIC)
        {
            fwrite($fileopen, 'El topic para '.$data->channel.' es: '.$data->message."\n");
        }      
        
        elseif ($data->type == SMARTIRC_TYPE_NICKCHANGE)
        {
            fwrite($fileopen, '*'.$data->nick.'* ahora es conocido como '.$data->message."\n");
        }        
        
        elseif ($data->type == SMARTIRC_TYPE_NOTICE)
        {
            fwrite($fileopen, '>'.$data->nick.'< '.$data->message."\n");
        }
       
        elseif ($data->type == SMARTIRC_TYPE_QUIT)
        {
            fwrite($fileopen, '*'.$data->nick.' salió ('.$data->message.")\n");
        }
        
        elseif ($data->type == SMARTIRC_TYPE_MODECHANGE)
        {
            $quienes = "";
            foreach ($data->rawmessageex as $id => $quien)
            {
                if (($id>3) and($data->rawmessageex[$id] != $data->rawmessageex[$id-1]))
                {
                    $quienes.= $quien." ";
                }
            }
            if(str_replace(" ","",$quienes) == "")
            {
                fwrite($fileopen, '*'.$data->nick.'* establece modo '.$data->rawmessageex[3].' en '.$data->rawmessageex[2]."\n");
            }
            else
            {
                fwrite($fileopen, '*'.$data->nick.'* establece modo '.$data->rawmessageex[3].' a '.$quienes.'en '.$data->rawmessageex[2]."\n");
            }
        }
        
        elseif ($data->type == SMARTIRC_TYPE_KICK)
        {
            fwrite($fileopen, '*'.$data->nick.'* hecha a '.$data->rawmessageex[3].' de '.$data->rawmessageex[2].' ('.$data->messageex[0].')'."\n");
        }
        
        elseif (($data->type == SMARTIRC_TYPE_NAME) and ($data->message != 'End of /NAMES list.'))
        {            
            fwrite($fileopen, 'Usuarios en '.$data->channel.': '.$data->message."\n");
        }
        
        /*//Debugging
         elseif (
                ($data->type != SMARTIRC_TYPE_MOTD) and 
                ($data->type != SMARTIRC_TYPE_CTCP_REQUEST) and 
                ($data->type != SMARTIRC_TYPE_CTCP_REPLY)
                )
        {
            print_r($data);
        }*/
    }
    
    //Esto es solo para tirar un gettopic
    function log_head(&$irc, &$data)
    {
        global $handler_temporal, $fileopen, $chan;
        
        $irc->getTopic($chan);
        $irc->unregisterActionid($handler_temporal);
    }
}
    

$fileopen = fopen($path,'a');
$bot = &new logbot( );
$irc = &new Net_SmartIRC( );

$irc->registerActionhandler( SMARTIRC_TYPE_CHANNEL,
    '^'.$nick.'\s*log$', $bot, 'log_cmd' );

$handler_temporal = $irc->registerActionhandler( SMARTIRC_TYPE_ALL,
    '.*', $bot, 'log_head' );

$irc->registerActionhandler( SMARTIRC_TYPE_ALL,
    '.*', $bot, 'log_save' );

$irc->registerActionhandler( SMARTIRC_TYPE_CHANNEL,
    '^'.$nick.'\s*status$', $bot, 'log_status' );

$irc->setAutoReconnect(TRUE);
$irc->setAutoRetry(TRUE);
$irc->setCtcpVersion("Log");
$irc->connect( $host, $port );
$irc->login( $nick, 'Logger', 0, $nick );
$irc->join( array( $chan ) );
$irc->listen( );
$irc->disconnect( );
fclose($fileopen);
?>
