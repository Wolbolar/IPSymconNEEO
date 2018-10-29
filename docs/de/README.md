# NEEO Brain und NEEO Fernbedienung
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/37412-IP-Symcon-5-0-%28Testing%29)


Modul für IP-Symcon ab Version 5

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguartion)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

Das Modul stellt eine Schnittstelle für das [NEEO](https://neeo.com/ "NEEO") Fernbedienungssystem zu IP-Symcon 5 zur Verfügung.
Mit dem Modul ist es möglich von einem [NEEO](https://neeo.com/ "NEEO") Brian aus IP-Symcon zusteuern, auf gesendete Befehle von NEEO kann reagiert werden z.B. mit einem Ereigniss oder einem Skript . Weiterhin ist es auch möglich Geräte aus IP-Symcon auf einem NEEO Brian zu steuern. 

### Funktionen:  

 - NEEO Oberfläche aus dem Webfront aufrufbar
 - Schalten von an NEEO angelernten Geräten
    - Ein- / Ausschalten von Geräten
    - Dimmen von Lampen
    
	  

## 2. Voraussetzungen

 - IP-Symcon 5
 - NEEO Brian 

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 5) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

![Modules](img/modules.png?raw=true "Modules")

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

```	
https://github.com/Wolbolar/IPSymconNEEO  
```
    
und mit _OK_ bestätigen.    
    
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_ 

### b. Einrichten der Geräte in NEEO  

Mit der NEEO App sind zunächst die Geräte einzurichten, die später aus IP-Symcon angesteuert werden sollen. Geräte aus IP-Symcon werden in einem späteren Schritt in NEEO angelegt.
Zunächst werden also alle IR Geräte in NEEO angelegt, die gesteuert werden sollen.


### c. Einrichtung in IPS

Zunächst den IO für NEEO anlegen. In IP-Symcon im Objektbaum auf _I/O Instanzen_ mit der rechten Maustatse klicken und _Objekt hinzufügen -> Instanz_ auswählen.
![NEEOIO](img/NEEO_IO.png?raw=true "NEEOIO")

In der NEEO IO Instanz ist die IP Adresse vom NEEO Brain einzutragen, diese kann in der NEEO APP unter _About LAN IP / WLAN IP_ nachgeschaut werden. Optional kann noch ein Skript angeben werden, falls man die Daten von NEEo direkt in einem Skript weiterverarbeiten will.

Ein Skript zum Auswerten der Daten sieht im Grundgerüst so aus

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

Übergebene Parameter von NEEO werden ebenfalls in Variablen abgelegt.
![NEEOIO](img/NEEO_Variables.png?raw=true "NEEOIO")

So kann man mit Ereignissen z.B. auf einen bestimmten Wert einer Variable reagieren.


#### NEEO Geräte in IP-Symcon anlegen

Um Geräte aus NEEO als Instanzen in IP-Symcon anzulegen kann  ein Konfigurator genutzt werden. In IP-Symcon im Objektbaum auf _Konfigurator Instanzen_ mit der rechten Maustatse klicken und _Objekt hinzufügen -> Instanz_ auswählen.

![NEEOConfigurator](img/NEEO_configurator.png?raw=true "NEEOConfigurator")

Nachdem der Konfigurator angelegt wurde kann ein Gerät über _erstellen_ erzeugt werden.

![NEEOConfigurator](img/NEEO_configurator_1.png?raw=true "NEEOConfigurator")

In dem Gerät sieht man dann was das Gerät in NEEO selber an Schaltoptionen zur Verfügung stellt.

![NEEODevice](img/NEEO_device.png?raw=true "NEEODevice")

### Webfront Ansicht




## 4. Funktionsreferenz

### NEEO:

 _**Schaltet ein Gerät ein**_
  
 ```php
 NEEO_PowerOn($InstanceID);
 ```   
 
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO Device Instanz
	
 _**Schaltet ein Gerät aus**_
   
 ```php
 NEEO_PowerOff($InstanceID);
 ```   
  
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO Device Instanz
  
 _**Schaltet LED des Brain ein**_
   
 ```php
 NEEO_LED_On($InstanceID);
 ```   
  
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO Cranium Instanz
 
 _**Schaltet LED des Brain aus**_
   
 ```php
 NEEO_LED_Off($InstanceID);
 ```   
  
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO Cranium Instanz 

 _**Reboot des NEEO Brain**_
   
 ```php
 NEEO_Brain_Reboot($InstanceID);
 ```   
  
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO Cranium Instanz

## 5. Konfiguration:

### Eigenschaften:

| Eigenschaft     | Typ     | Standardwert | Funktion                                      |
| :-------------: | :-----: | :----------: | :-------------------------------------------: |
| Host            | string  |              | IP Adresse des NEEO Brain                     |







## 6. Anhang

###  a. Funktionen:

#### NEEO:

 _**Schaltet ein Gerät ein**_
  
 ```php
 NEEORD_PowerOn($InstanceID);
 ```   
 
 Parameter _$InstanceID_ __*ObjektID*__ der NEEO IO Instanz
	
  _**Schaltet ein Gerät aus**_
   
  ```php
  NEEORD_PowerOff($InstanceID);
  ```   
  
  Parameter _$InstanceID_ __*ObjektID*__ der NEEO IO Instanz
