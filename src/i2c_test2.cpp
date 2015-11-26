#include <stdlib.h>
#include <unistd.h>
//#include <linux/i2c.h>
#include <linux/i2c-dev.h>
#include <sys/ioctl.h>
#include <fcntl.h>
#include <string.h>
#include <stdio.h>

//#define I2C_SLAVE       0x0703  /* Use this slave address */
//#define I2C_SLAVE_FORCE 0x0706  /* Use this slave address, even if it
//                                    is already in use by a driver! */
//#define I2C_TENBIT      0x0704  /* 0 for 7 bit addrs, != 0 for 10 bit */ 

int main(void)
{
  // Set up some variables that we'll use along the way
  char rxBuffer[32];  // receive buffer
  char txBuffer[32];  // transmit buffer
  int barometerAddress = 0x5c; // gyro device address
  int xlAddress     = 0x28;   // accelerometer device address
  int tenBitAddress = 0;  // is the device's address 10-bit? Usually not.
  int opResult      = 0;   // for error checking of operations

  // Create a file descriptor for the I2C bus
  int i2cHandle = open("/dev/i2c-1", O_RDWR);

  // Tell the I2C peripheral that the device address is (or isn't) a 10-bit
  //   value. Most probably won't be.
  opResult = ioctl(i2cHandle, I2C_TENBIT, tenBitAddress);

  // Tell the I2C peripheral what the address of the device is. We're going to
  //   start out by talking to the gyro.
  opResult = ioctl(i2cHandle, I2C_SLAVE, barometerAddress);

  // Clear our buffers
  memset(rxBuffer, 0, sizeof(rxBuffer));
  memset(txBuffer, 0, sizeof(txBuffer));

  // The easiest way to access I2C devices is through the read/write
  //   commands. We're going to ask the gyro to read back its "WHO_AM_I"
  //   register, which contains the I2C address. The process is easy- write the
  //   desired address, the execute a read command.
  //***write
  txBuffer[0] = 0x0f; // This is the address we want to read from.
  opResult = write(i2cHandle, txBuffer, 1);
  if (opResult != 1) printf("No ACK bit!\n");
  //***read
  opResult = read(i2cHandle, rxBuffer, 1);
  printf("Part ID: 0x%x\n", (int)rxBuffer[0]); // should print 105

  //*** write !!! 0x20, 0x90
  txBuffer[0] = 0x10000000b && 0x20; // This is the address we want to read from.
  printf(" write 0x%x",(int)txBuffer[0]);
  opResult = write(i2cHandle, txBuffer, 1);
  if (opResult != 1) printf("No ACK bit 2!\n");
  txBuffer[0] = 0x90;
  opResult = write(i2cHandle, txBuffer, 1);
  if (opResult != 1) printf("No ACK bit 3!\n");

  //*** read !!! 0x28
  txBuffer[0] = 0x28; // This is the address we want to read from.
  opResult = write(i2cHandle, txBuffer, 1);
  if (opResult != 1) printf("No ACK bit4!\n");
  //***read
  opResult = read(i2cHandle, rxBuffer, 1);
  printf("Part %i ID: ",opResult); // should print 105
  for(int i=0;i<opResult;++i) {
      printf(" 0x%x",i,(int)rxBuffer[i]);
  }
  printf("\n");                  
                                        
                                        
/*
  // Next, we'll query the accelerometer using the same process- but first,
  //   we need to change the slave address!
  opResult = ioctl(i2cHandle, I2C_SLAVE, xlAddress);
  txBuffer[0] = 0x00;  // This is the address to read from.
  opResult = write(i2cHandle, txBuffer, 1);
  if (opResult != 1) printf("No ACK bit!\n");
  opResult = read(i2cHandle, rxBuffer, 1);
  printf("Part ID: %d\n", (int)rxBuffer[0]); // should print 229
  */
}