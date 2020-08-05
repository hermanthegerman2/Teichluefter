<?php
	class Teichluefter extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent("{7AABF817-B04B-4A68-A1F6-BCE8EF7B3B87}");
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function Send()
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{69BA421E-CBB6-73B1-031E-40F7B87DB050}")));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("Device RECV", utf8_decode($data->Buffer));
		}

	}