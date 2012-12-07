<?php

#!/usr/bin/php -q

namespace eoko\shell;

use eoko\shell\libs\ShellCore;
use eoko\config\ConfigManager;

class ShellEoze extends ShellCore {

    private $currentNode = array();
    private $cli;
    private $customHelp = array();
    private $conn = array();

    function __construct() {
//
//		$this->conn[0] = new ShellCore();
//		var_dump($this->conn);
//		die();

        parent::__construct();


        $this->setAppName("Bienvenue sur le panneau d'administration d'eoze");
        $this->setAppDesc("L'objectif de cette application est de permettre...");

        $this->clear_screen();


        $t = ConfigManager::get('eoze\application\directories\name');

        $msgAccueil = "
                      
      :::::::::: :::::::: ::::::::: ::::::::::     " . ConfigManager::get('eoze\application\shell\welcomeMessage') . "
     :+:       :+:    :+:     :+:  :+:             " . ConfigManager::get('eoze\application\shell\description') . "
    +:+       +:+    +:+    +:+   +:+                 
   +#++:++#  +#+    +:+   +#+    +#++:++#          For questions, please go to the forum : http://forum.eoko-lab.fr
  +#+       +#+    +#+  +#+     +#+                For any suggestions : http//issue.eoko-lab.fr
 #+#       #+#    #+# #+#      #+#
########## ######## ######### ##########           " . ConfigManager::get('eoze\application\licence') . "
";
        $this->tag_string($msgAccueil, "bg_blue|bold", TRUE);
    }

    function startConsole() {
        $this->tag_string("\n\n" . $this->appDesc . "\n\n", 'magenta', TRUE);
        $this->tag_string('Pour optenir plus d\'aide sur la console veuillez taper help.', 'bold', TRUE);

        $this->defineArg('config', 'c', '', 'Afficher la configuration de l\'application');
        $this->defineArg('user', 'u', '', "Définie l'utilisateur Gtalk (ex : username@gmail.com)");
        $this->defineArg('usersconn', 'uc', '', "Affiche les utilisateurs connectés");
        $this->defineArg('msg', 'm', '', 'Envoyer un message à un autre utilisateur');
        $this->defineArg('statut', 's', '', 'Afficher son statut');
        $this->defineArg('rep_auto', 'r', '', 'Réponse automatique (expérimental)');
        $this->defineArg('forcerefresh', 'fr', '', 'Forcer le rafraichissement des connections');
        $this->defineArg('deconnect', 'd', '', 'Se déconnecter');
        $this->defineArg('disconnectall', 'da', '', 'Déconnecter tout le monde');
        $this->defineArg('exit', 'e', '', 'Fermer la console');
        $this->parseArgs();

        $this->menuManager();
    }

    function menuManager() {

        //$cmd = $this->get_args();
        $cmd = '';

        while ($cmd != 'exit') {
            $cmd = $this->read("\nQue voulez-vous faire? (help pour l'aide)");
            echo "\n";
            switch ($cmd) {
                case "--config":
                    $this->exploreNode();
                    break;
                case "--fileconfig":
                    $this->msg_($this->printFilesConfig($node));
                    break;
                case "--disconnectall":
                    break;
                case "--show":
                    break;
                case "help":
                    $this->display_help();
                    break;
                default:
                    break;
            }
        }
    }

    function exploreNode() {
        $read = '';
        while ($read != 'stop') {
            $pathString = implode('/', $this->currentNode);
            $read = $this->read("Node to explore : $pathString ?");

            switch ($read) {
                case '..':
                    array_pop($this->currentNode);
                    break;
                case '/':
                    $this->currentNode = array();
                    break;
                case 'dump' :
                    var_dump($this->currentNode);
                    break;
                case '' :
                    break;
                default:
                    if (count_chars(str_replace(' ', '', $read)) > 0) {
                        $pathArray = explode('/', $read);
                        $this->currentNode = array_merge($this->currentNode, $pathArray);
                    }
                    break;
            }
            $this->printConfig(implode('/', $this->currentNode));
        }
    }

    function printKeys($item, $key,$spacer) {
        $value = $item;
        $this->msg_($spacer . $key . " : " . $value);
    }

    function printConfig($node, $depth = 10) {
        $config = ConfigManager::get($node);
        $spacer = str_repeat(' ', $depth);
        $this->msg_($spacer . "..");
        array_walk($config, array($this, 'printKeys'),$spacer);
    }

}

//
//$myCli = new console();
//die();
//$msg->defineArg('name', 'n', 'Default', 'your name');
//$msg->defineArg('un autre', 'u', 'jjj', 'Un autre exemple très intéressant');
//
//$msg->parseArgs();
//echo "finish";
//
//$msg->msg_("ralalalralrara", ShellCore::LEVEL_ERROR);
//$msg->msg_("ralalalralrara", ShellCore::LEVEL_WARNING);
//$msg->msg_("ralalalralrara", ShellCore::LEVEL_NOTICE);
//$msg->msg_("ralalalralrara", ShellCore::LEVEL_MESSAGE);
//$s = $msg->get_args();
////var_dump($s);
//
//echo $msg->get_arg('name');
