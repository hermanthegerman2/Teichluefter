<?php

declare(strict_types=1);

require_once __DIR__ . "/../libs/TLConstants.php"; // Victron Daten Library
//require_once __DIR__ . "/../libs/ModuleHelper.php"; // globale Funktionen
require_once __DIR__ . '/../libs/OWNet.php';  // Ownet.php from owfs distribution
require_once __DIR__ . '/../libs/images.php';  // eingebettete Images


	class TeichluefterOWNet extends IPSModule {

        //use ModuleHelper;
        use TeichluefterTLConstants;
        use TeichluefterImagesLib;


		public function Create()
		{
			//Never delete this line!
			parent::Create();

            // Modul-Eigenschaftserstellung

            $this->RegisterPropertyBoolean("Open", false);
            $this->RegisterPropertyString('Category', 'OWNet Devices');
            $this->RegisterPropertyInteger('ParentCategory', 0); //parent cat is root
            $this->RegisterPropertyInteger('Port', 4304);
            $this->RegisterPropertyInteger('UpdateInterval', 30);
            $this->RegisterPropertyString('Host', '127.0.0.1');

            $this->RegisterPropertyBoolean("debug", true);
            $this->RegisterPropertyBoolean("log", true);
            $this->RegisterPropertyBoolean("AutoRestart", true);
            $this->RegisterPropertyBoolean('AutoCreate', true);
            // Statusvariablen anlegen
            $this->RegisterVariableBoolean("ConnectionStatus", $this->Translate("ConnectionStatus"), "~Alert.Reversed", 40);
            $this->DisableAction("ConnectionStatus");

            $OWDeviceArray = array();
            $this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));

            //timer
            /* $this->RegisterTimer('Update', 0, $this->module_data["prefix"] . '_UpdateEvent($_IPS[\'TARGET\']);');


            if (IPS_GetKernelRunlevel() == self::KR_READY) {
                if ($this->isActive()) {
                    $this->SetStatus(self::ST_AKTIV);
                    $i = $this->GetUpdateInterval();
                    $this->SetTimerInterval('Update', ($i * 1000));//ms
                    $this->debug(__FUNCTION__, "Starte Timer $i sec");
                    $this->init();
                } else {
                    $this->SetStatus(self::ST_INACTIV);
                    $this->SetTimerInterval('Update', 0);
                }
            }*/


        }

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

        public function GetConfigurationForm()
        {
            $formElements = $this->GetFormElements();
            $formActions = $this->GetFormActions();
            $formStatus = $this->GetFormStatus();

            $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
            if ($form == '') {
                $this->_log(__FUNCTION__, 'json_error=' . json_last_error_msg());
                $this->_log(__FUNCTION__, '=> formElements=' . print_r($formElements, true));
                $this->_log(__FUNCTION__, '=> formActions=' . print_r($formActions, true));
                $this->_log(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true));
            }
            return $form;
        }

        private function GetFormElements()
        {
            // $Connection_Type = $this->ReadPropertyString('Connection_Type');
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $this->Translate('___ Options _______________________________________________________________________________________________________________')
            ];
            $formElements[] = [
                "type" => "CheckBox",
                "name" => "log",
                "caption" => $this->Translate("enable Logging")
            ];
            $formElements[] = [
                "type" => "CheckBox",
                "name" => "debug",
                "caption" => $this->Translate("enable debug messages")
            ];
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $this->Translate('___ OWNet Client Socket Connection Parameters _____________________________________________________________________________')
            ];

            $formElements[] = [
                "type" => "ValidationTextBox",
                "name" => "Host",
                "caption" => $this->Translate("OWNet Host")
            ];
            $formElements[] = [
                "type" => "NumberSpinner",
                "name" => "Port",
                "caption" => $this->Translate("OWNet Port")
            ];

            $formElements[] = [
                'type'    => 'Label',
                'caption' => $this->Translate('___ OWNet Devices _________________________________________________________________________________________________________')
            ];
            $arraySort = array();
            $arraySort = array("column" => "DeviceTyp", "direction" => "ascending");
            // Tabelle für die gefundenen 1-Wire-Devices
            $arrayOWColumns = array();
            $arrayOWColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
            $arrayOWColumns[] = array("label" => "Serien-Nr.", "name" => "DeviceSerial", "width" => "120px", "add" => "");
            $arrayOWColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
            $arrayOWColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");

            If ($this->GetBuffer("OW_Handle") >= 0) {
                // 1-Wire-Devices einlesen und in das Values-Array kopieren
                //$OWDeviceArray = array();
                $this->OWSearchStart();
                $OWDeviceArray = array($this->GetBuffer('OWDeviceArray'));
                If (count($OWDeviceArray , COUNT_RECURSIVE) >= 4) {
                    $arrayOWValues = array();
                    for ($i = 0; $i < Count($OWDeviceArray); $i++) {
                        $arrayOWValues[] = array("DeviceTyp" => $OWDeviceArray[$i][0], "DeviceSerial" => $OWDeviceArray[$i][1], "InstanceID" => $OWDeviceArray[$i][2], "DeviceStatus" => $OWDeviceArray[$i][3], "rowColor" => $OWDeviceArray[$i][4]);
                    }
                    $formElements[] = array("type" => "List", "name" => "OW_Devices", "caption" => "1-Wire-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayOWColumns, "values" => $arrayOWValues);
                    $formElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
                }
                else {
                    $formElements[] = array("type" => "Label", "label" => "Es wurden keine 1-Wire-Devices gefunden.");
                }
            }

            $formElements[] = [
                'type'  => 'Image',
                'image' => 'data:image/png;base64,' . $this->GetBrandImage()
            ];

            return $formElements;
        }

        private function GetFormActions()
        {
            $formActions[] = [
                'type'    => 'Label',
                'caption' => 'Geräte suchen'
            ];
            $formActions[] = [
                'type'    => 'Button',
                'caption' => 'Search Devices',
                'onClick' => $this->OWSearchStart()
            ];

            return $formActions;
        }

        private function GetFormStatus()
        {
            $formStatus = [];
            $formStatus[] = ['code' => 101, 'icon' => 'inactive', 'caption' => $this->Translate('Instance getting created')];
            $formStatus[] = ['code' => 102, 'icon' => 'active', 'caption' => $this->Translate('Instance is active')];
            $formStatus[] = ['code' => 104, 'icon' => 'inactive', 'caption' => $this->Translate('Instance is not active')];
            $formStatus[] = ['code' => 200, 'icon' => 'error', 'caption' => $this->Translate('Instance Error')];

            return $formStatus;
        }

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

        //------------------------------------------------------------------------------
        //public functions
        //------------------------------------------------------------------------------

        /**
         * Query %OWNet daemon
         */
        public function OWSearchStart()
        {
            $OWDeviceArray = '';
            $ow = new OWNet("tcp://" . $this->ReadPropertyString('Host') . ':' . $this->ReadPropertyInteger('Port'));
            if ($ow) {
                //we are connected, proceed
                $this->_log('OWNet', 'tcp://' . $this->ReadPropertyString('Host') . ':' . $this->ReadPropertyInteger('Port'));
                // retrieve owfs directory from given root
                $ow_dir = $ow->dir($this->ow_path);
                if ($ow_dir && isset($ow_dir['data_php'])) {
                    //walk through the retrieved tree
                    $dirs = explode(",", $ow_dir['data_php']);
                    if (is_array($dirs) && (count($dirs) > 0)) {
                        foreach ($dirs as $dev) {
                            #print_r($ow->dir($dev));
                            $data = array();
                            $caps = '';
                            /* read standard device details */
                            //get family id
                            $fam = $ow->read("$dev/family");
                            if (!$fam) continue; //not a device path
                            //get device id
                            $id = $ow->read("$dev/id");
                            //get alias (if any) and owfs detected device description as type
                            $alias = $ow->get("$dev/alias");
                            $type = $ow->get("$dev/type");
                            if (!$type) {
                                $type = "1Wire Family " . $fam;
                            }
                            //assign names for ips categories
                            $name = $id;
                            if ($alias) {
                                $name = $alias;
                                $caps = 'Name';
                            }

                            //save date
                            $data['Date'] = date('Y-m-d H:i:s', time());

                            //get varids
                            $addr = "$fam.$id";
                            //print "$id ($alias): Type $type Family $fam\n";

                            //retrieve device specific data
                            switch ($fam) {
                                case '28': //DS18B20 temperature sensors
                                case '10': //DS18S20 temperature sensors
                                case '22': //DS1820 temperature sensors
                                    $temp = $ow->read("$dev/temperature", true);
                                    $temp = str_replace(",", ".", $temp);
                                    if (strlen($temp) > 0) {
                                        //store new temperature value
                                        //$this->_log('OWNet', "$type $id ($alias): $temp");
                                        $data['Name'] = $name;
                                        $data['Id'] = $addr;
                                        $data['Typ'] = $type;
                                        $data['Temp'] = sprintf("%4.2F", $temp);
                                        //print " Alias '$alias',Temp $temp\n";
                                        $caps .= ';Temp';
                                        $this->_log('OWNet Device', $data);
                                        $OWDeviceArray = $OWDeviceArray.$data;
                                    }
                                    break;
                                default:
                                    $this->_log('OWNet', "$id ($alias): Type $type Family $fam not implemented yet");
                            }
                        }
                        $this->_log('OWNet Device Array', $OWDeviceArray);
                        $this->SetBuffer('OWDeviceArray', $OWDeviceArray);
                    } else {
                        //no device fount
                        $this->_log('OWNet', "No 1Wire Device found");
                    }
                } else {
                    //dir command failed, stop here
                    $this->_log('OWNet', "Dir using $this->ow_path' failed");
                }
            } else {
                //no object, connect has been failed, stop here
                $this->_log('OWNet', "Connect to '$connect' failed");
            }
        }
            /**
             * @param null $sender
             * @param mixed $message
             * @param bool $debug
             */
            protected function _log($sender = NULL, $message = '')
        {
            if ($this->ReadPropertyBoolean('log')) {
                if (is_array($message)) {
                    $message = json_encode($message);
                }
                IPS_LogMessage($sender, $message);
            }
            if ($this->ReadPropertyBoolean('debug')) {
                if (is_array($message)) {
                    $message = json_encode($message);
                }
                $this->SendDebug($sender, $message, 0);
            }
        }


    }