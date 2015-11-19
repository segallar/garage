// I2C test program for a PCA9555

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
    
    /*
    
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
    
    ---
    Usage: i2cget [-f] [-y] I2CBUS CHIP-ADDRESS [DATA-ADDRESS [MODE]]
    I2CBUS is an integer or an I2C bus name
    ADDRESS is an integer (0x03 - 0x77)
      MODE is one of:
        b (read byte data, default)
        w (read word data)
        c (write byte/read byte)
        Append p for SMBus PEC

    info: https://www.kernel.org/doc/Documentation/i2c/dev-interface
    */
	// open Linux I2C device
	i2cOpen();

    int dev_addr = 0x5c;
    
	// set address of the device	
    i2cSetAddress(dev_addr);
    
    __u8 reg = 0x0f; /* Device register to access */
    __s32 res;
    //char buf[10];

      /* Using SMBus commands */
    res = i2c_smbus_read_word_data(file, reg);
    if (res < 0) {
        //  ERROR HANDLING: i2c transaction failed 
    } else {
        // res contains the read word 
        printf(" we got %d and shoud be %d \n",res,0xbb);
    }
    
    /*
    char buf[10];
    
    if (read(g_i2cFile, buf, 1) != 1) {
    // ERROR HANDLING: i2c transaction failed 
        perror("i2cRead error");
		exit(1);
    } else {
    // buf[0] contains the read byte 
        printf(" we got %d and shoud be %d \n",buf[0],0xbb);
    }
    */
    
        
	// close Linux I2C device
	i2cClose();

	return 0;
}