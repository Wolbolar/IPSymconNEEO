# NEEO brain und NEEO remote
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/37412-IP-Symcon-5-0-%28Testing%29)


Module for IP Symcon version 5 or higher

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

The module provides an interface for the [NEEO](https://neeo.com/ "NEEO") remote control system to IP-Symcon 5.
With the module it is possible to control a [NEEO](https://neeo.com/ "NEEO") Brian from IP-Symcon. You can react on sent commands from NEEO e.g. with an event or a script. It is also possible to control devices from IP-Symcon on a NEEO Brian.
	  
## 2. Requirements

 - NEEO Web User Interface for the Webfront
 - Switch NEEO devices
    - Power on / Power off of devices
    - dim lights

## 3. Installation

### a. Loading the module

Open the IP Symcon (min Ver 5) console. In the object tree, under Core instances, open the instance __*Modules*__ with a double mouse click.

![Modules](img/modules.png?raw=true "Modules")

In the _Modules_ instance, press the button __*Add*__ in the top right corner.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
Add the following URL in the window that opens:

```	
https://github.com/Wolbolar/IPSymconNEEO  
```
    
and confirm with _OK_.    
    
Then an entry for the module appears in the list of the instance _Modules_

### b. Setting up the devices in NEEO  

With the NEEO app, the devices are first to be set up, which are later to be controlled from IP-Symcon. Devices from IP-Symcon will be created in NEEO in a later step.
First of all, all IR devices that are to be controlled are created in NEEO.


### c. Configuration in IPS

First create the IO for NEEO. In IP-Symcon in the object tree, click on _I/O instances_ with the right mouse button and select _Object -> add instance_.
![NEEOIO](img/NEEO_IO.png?raw=true "NEEOIO")

In the NEEO IO instance, the IP address of the NEEO Brain must be entered, which can be looked up in the NEEO APP under _About LAN IP / WLAN IP_. Optionally, you can specify a script if you want to process the NEEO data directly in a script.

A basic script for evaluating the data looks like this:

 ```php
$action = $_IPS['action'];
$device = $_IPS['device'];
$room = $_IPS['room'];
$actionparameter = $_IPS['actionparameter'];
$recipe = $_IPS['recipe'];

if($action == "launch" && $recipe == "LIGHT") // if action is launch and the recipe is LIGHT do something
{
IPS_LogMessage("NEEO Forward Script", "Recipe ". $recipe . " was triggered");
// add device to trigger

}
 ```  

Passed parameters of NEEO are also stored in variables.
![NEEOIO](img/NEEO_Variables.png?raw=true "NEEOIO")

So you can react with events, e.g. respond to a specific value of a variable.

#### Create NEEO Devices in IP-Symcon

To create devices from NEEO as instances in IP-Symcon, a configurator can be used. In IP-Symcon in the object tree, click on _Configurator Instances_ with the right mouse button and select _Object -> add instance_.

![NEEOConfigurator](img/NEEO_configurator.png?raw=true "NEEOConfigurator")

After the configurator has been created, a device can be created via _generate_.

![NEEOConfigurator](img/NEEO_configurator_1.png?raw=true "NEEOConfigurator")

In the device you can see what kind of commands are available in NEEO for this device.

![NEEODevice](img/NEEO_device.png?raw=true "NEEODevice")

### Webfront Screen



## 4. Function reference

### NEEO:

 _**Switch on a device**_
  
 ```php
 NEEORD_PowerOn($InstanceID);
 ```   
 
 Parameter _$InstanceID_ __*ObjektID*__ from the NEEO IO Instance
	
  _**Switch off a device**_
   
  ```php
  NEEORD_PowerOff($InstanceID);
  ```   
  
  Parameter _$InstanceID_ __*ObjektID*__ from the NEEO IO Instance
 
	

## 5. Configuration:

### Properties:

| Property        | Type    | Standard Value | Function                                      |
| :-------------: | :-----: | :------------: | :-------------------------------------------: |
| Host            | string  |                | IP address from the NEEO Brain                |


## 6. Annnex

###  a. Methods:

#### NEEO:

 _**Switch on a device**_
  
 ```php
 NEEORD_PowerOn($InstanceID);
 ```   
 
 Parameter _$InstanceID_ __*ObjektID*__ from the NEEO IO Instance
	
  _**Switch off a device**_
   
  ```php
  NEEORD_PowerOff($InstanceID);
  ```   
  
  Parameter _$InstanceID_ __*ObjektID*__ from the NEEO IO Instance
