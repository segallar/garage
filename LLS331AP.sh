#!/bin/bash
#
# garage project 
#

 WHO_AM_I=`sudo i2cget -y 1 0x5c 0x0f`
 if [ $WHO_AM_I != "0xbb" ]; then
   echo "device NG"
   exit 1
 fi

 ### set active mode
 sudo i2cset -y 1 0x5c 0x20 0x90

 ### read pres data

 PressOut_XL=`sudo i2cget -y 1 0x5c 0x28`
 PressOut_L=`sudo i2cget -y 1 0x5c 0x29`
 PressOut_H=`sudo i2cget -y 1 0x5c 0x2a`

 RawDatHex=`echo "${PressOut_H:2:2}${PressOut_L:2:2}${PressOut_XL:2:2}"`

 #RawDatDec=`printf %d $RawDatHex`

 # echo "scale=2;$RawDatDec/4096" |bc

 ### Temp 

 TempOut_L=`sudo i2cget -y 1 0x5c 0x2b`
 TempOut_H=`sudo i2cget -y 1 0x5c 0x2c`

 RawTempHex=`echo "${TempOut_H:2:2}${TempOut_L:2:2}"`

 #RawTempDec=`printf %d $RawTempHex`

 #echo "scale=2;42.5+($RawTempDec/480)" |bc

 echo "$RawDatHex $RawTempHex"

 ### set power down
 sudo i2cset -y 1 0x5c 0x20 0x00
