<?php

declare(strict_types=1);

class IPS_WLANThermo extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        $this->RegisterVariableBoolean('WLANThermoa_Charge', $this->Translate('Charge'), '');
        $this->RegisterVariableInteger('WLANThermoa_SOC', $this->Translate('SOC'), '');
        $this->RegisterVariableInteger('WLANThermoa_RSSI', $this->Translate('RSSI'), '');

        $this->RegisterPropertyString('MQTTTopic', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        //Setze Filter für ReceiveData
        $MQTTTopic = $this->ReadPropertyString('MQTTTopic');
        $this->SetReceiveDataFilter('.*' . $MQTTTopic . '.*');
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (!empty($this->ReadPropertyString('MQTTTopic'))) {
            $Data = json_decode($JSONString);
            // Buffer decodieren und in eine Variable schreiben
            $this->SendDebug('Topic', $Data->Topic, 0);
            $this->SendDebug('Payload', $Data->Payload, 0);
            if (property_exists($Data, 'Topic')) {
                if (fnmatch('*/status/data*', $Data->Topic)) {
                    $Payload = json_decode($Data->Payload);

                    SetValue($this->GetIDForIdent('WLANThermoa_Charge'), $Payload->system->charge);
                    SetValue($this->GetIDForIdent('WLANThermoa_SOC'), $Payload->system->soc);
                    SetValue($this->GetIDForIdent('WLANThermoa_RSSI'), $Payload->system->rssi);

                    foreach ($Payload->channel as $channel) {
                        $this->RegisterVariableFloat('WLANThermoa_Temperature' . $channel->number, $channel->name . ' ' . $this->translate('Temperature'), '~Temperature');
                        $this->RegisterVariableFloat('WLANThermoa_Min' . $channel->number, $channel->name . ' ' . $this->translate('Min'), '~Temperature');
                        $this->RegisterVariableFloat('WLANThermoa_Max' . $channel->number, $channel->name . ' ' . $this->translate('Max'), '~Temperature');
                        $this->RegisterVariableInteger('WLANThermoa_Typ' . $channel->number, $this->translate('Typ Channel') . ' ' . $channel->number, '');
                        $this->RegisterVariableBoolean('WLANThermoa_Alarm' . $channel->number, $this->translate('Alarm Channel') . ' ' . $channel->number, '~Switch');

                        $this->EnableAction('WLANThermoa_Min' . $channel->number);
                        $this->EnableAction('WLANThermoa_Max' . $channel->number);
                        $this->EnableAction('WLANThermoa_Alarm' . $channel->number);

                        SetValue($this->GetIDForIdent('WLANThermoa_Temperature' . $channel->number), $channel->temp);
                        SetValue($this->GetIDForIdent('WLANThermoa_Min' . $channel->number), $channel->min);
                        SetValue($this->GetIDForIdent('WLANThermoa_Max' . $channel->number), $channel->max);
                        SetValue($this->GetIDForIdent('WLANThermoa_Typ' . $channel->number), $channel->typ);
                        SetValue($this->GetIDForIdent('WLANThermoa_Alarm' . $channel->number), $channel->alarm);
                    }
                }
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if (fnmatch('WLANThermoa_Alarm*', $Ident)) {
            $Channel = substr($Ident, -1, 1);
            $this->SendDebug('RequestAction setAlarm', $Value, 0);
            $this->setAlarm(intval($Channel), $Value);
        }
        if (fnmatch('WLANThermoa_Min*', $Ident)) {
            $Channel = substr($Ident, -1, 1);
            $this->setMin(intval($Channel), $Value);
        }
        if (fnmatch('WLANThermoa_Max*', $Ident)) {
            $Channel = substr($Ident, -1, 1);
            $this->setMax(intval($Channel), $Value);
        }
    }

    public function setAlarm(int $channel, bool $value)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = false;
        $Data['Topic'] = 'WLanThermo/' . $this->ReadPropertyString('MQTTTopic') . '/set/channels';

        $Payload['number'] = strval($channel);
        $Payload['alarm'] = $value;

        $Data['Payload'] = json_encode($Payload, JSON_UNESCAPED_SLASHES);
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . 'Topic', $Data['Topic'], 0);
        $this->SendDebug(__FUNCTION__, $DataJSON, 0);
        $this->SendDataToParent($DataJSON);
    }

    public function setMin(int $channel, float $value)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = false;
        $Data['Topic'] = 'WLanThermo/' . $this->ReadPropertyString('MQTTTopic') . '/set/channels';

        $Payload['number'] = strval($channel);
        $Payload['min'] = strval($value);

        $Data['Payload'] = json_encode($Payload, JSON_UNESCAPED_SLASHES);
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . 'Topic', $Data['Topic'], 0);
        $this->SendDebug(__FUNCTION__, $DataJSON, 0);
        $this->SendDataToParent($DataJSON);
    }

    public function setMax(int $channel, float $value)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = false;
        $Data['Topic'] = 'WLanThermo/' . $this->ReadPropertyString('MQTTTopic') . '/set/channels';

        $Payload['number'] = strval($channel);
        $Payload['max'] = strval($value);

        $Data['Payload'] = json_encode($Payload, JSON_UNESCAPED_SLASHES);
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . 'Topic', $Data['Topic'], 0);
        $this->SendDebug(__FUNCTION__, $DataJSON, 0);
        $this->SendDataToParent($DataJSON);
    }
}
