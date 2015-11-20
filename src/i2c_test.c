//
// garage project 
//
// I2C test program for a LLS331AP

#include <stdint.h>
#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <linux/i2c-dev.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <fcntl.h>

// I2C Linux device handle
int g_i2cFile;

// open the Linux device
void i2cOpen()
{
	g_i2cFile = open("/dev/i2c-1", O_RDWR);
	if (g_i2cFile < 0) {
		perror("i2cOpen");
		exit(1);
	}
}

// close the Linux device
void i2cClose()
{
	close(g_i2cFile);
}

// set the I2C slave address for all subsequent I2C device transfers
void i2cSetAddress(int address)
{
	if (ioctl(g_i2cFile, I2C_SLAVE, address) < 0) {
		perror("i2cSetAddress");
		exit(1);
	}
}

int main(int argc, char** argv)
{
    // INFO: https://www.kernel.org/doc/Documentation/i2c/dev-interface
    // INFO: http://www.st.com/web/en/resource/technical/document/datasheet/DM00036196.pdf

	// open Linux I2C device
	i2cOpen();

    int devAddr = 0x5c;
    
	// set address of the device	
    i2cSetAddress(devAddr);
    
    __u8  reg = 0x0f; /* Device register to access */
    __u8  res;
    __s32 writeResult;
    __u8  pressHB;
    __u8  pressLB;
    __u8  pressXLB;
    __u8  tempHB;
    __u8  tempLB;
    
    __s32 press;
    __s16 temp;
   
    /* Using SMBus commands */
    // if board installed in system
    res = i2c_smbus_read_byte_data(g_i2cFile, reg);
    if( res == 0xbb ) {
        // power board up
        writeResult = i2c_smbus_write_byte_data(g_i2cFile, 0x20, 0x90);
        if( writeResult < 0 ) {
            perror("i2cPowerUp");
            exit(1);
        }
        // read press
        pressXLB = i2c_smbus_read_byte_data(g_i2cFile, 0x28);
        pressLB  = i2c_smbus_read_byte_data(g_i2cFile, 0x29);
        pressHB  = i2c_smbus_read_byte_data(g_i2cFile, 0x2a);
        // read temp
        tempLB   = i2c_smbus_read_byte_data(g_i2cFile, 0x2b);
        tempHB   = i2c_smbus_read_byte_data(g_i2cFile, 0x2c);
        // print out
        printf("%02x%02x%02x %02x%02x\n",pressHB,pressLB,pressXLB,tempHB,tempLB);
        
        // power down
        writeResult = i2c_smbus_write_byte_data(g_i2cFile, 0x20, 0x00);
        if( writeResult < 0 ) {
            perror("i2cWritePowerDown");
            exit(1);
        }
        
        press = pressHB*0x10000+pressLB*0x100+pressXLB;
        
        printf("0x%08x \n",press);
        
    } else {
        perror("i2cNoDeviceFound");
        exit(1);
    }
        
	// close Linux I2C device
	i2cClose();

	return 0;
}