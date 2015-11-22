//
// garage project 
//
// I2C test program for a LLS331AP

#include <stdint.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <linux/i2c-dev.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <fcntl.h>
#include <mysql/mysql.h>

bool debug = false;

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

int i2cLPS331APRead( float &press, float &temp ) {    
// INFO: http://www.st.com/web/en/resource/technical/document/datasheet/DM00036196.pdf
    __u8  res;
    __s32 writeResult;
    __u8  pressHB, pressLB, pressXLB, tempHB, tempLB;
    __s32 pressI, tempI;
    // set address of the device	
    i2cSetAddress(0x5c);
    // if board installed in system
    res = i2c_smbus_read_byte_data(g_i2cFile, 0x0f);
    if( res == 0xbb ) {
        // power board up
        writeResult = i2c_smbus_write_byte_data(g_i2cFile, 0x20, 0x90);
        if( writeResult < 0 ) {
            perror("i2cPowerUp");
            return -2;
        }
        // read press
        pressXLB = i2c_smbus_read_byte_data(g_i2cFile, 0x28);
        pressLB  = i2c_smbus_read_byte_data(g_i2cFile, 0x29);
        pressHB  = i2c_smbus_read_byte_data(g_i2cFile, 0x2a);
        // read temp
        tempLB   = i2c_smbus_read_byte_data(g_i2cFile, 0x2b);
        tempHB   = i2c_smbus_read_byte_data(g_i2cFile, 0x2c);
        // power down
        writeResult = i2c_smbus_write_byte_data(g_i2cFile, 0x20, 0x00);
        if( writeResult < 0 ) {
            perror("i2cWritePowerDown");
            return -3;;
        }
        if(debug)
            printf("0x%02%02%02 0x%02%02\n",pressHB,pressLB,pressXLB,tempHB,tempLB);
        pressI = pressHB * 0x10000 + pressLB * 0x100 + pressXLB;
        press = (float)pressI / 4096;
        tempI = tempHB * 0x100 + tempLB;
        temp = 42.5 + ( (float)tempI / 480 );
        if(debug)
            printf("$f $f\n",press,temp);
        return 0;
    } else {
        perror("i2cNoDeviceFound");
        return -1;
    }
}

void savePressTemp(float press, float temp) {
    
    // Дескриптор соединения
    MYSQL conn;

    // Получаем дескриптор соединения
    if(!mysql_init(&conn))
        perror("Error: can't create MySQL-descriptor\n");

    // Устанавливаем соединение с базой данных
    if(!mysql_real_connect(&conn,
                         "localhost",
                         "barometer",
                         "pass",
                         "garage",
                         0,
                         NULL,
                         0))
        perror("Error: can't connect to MySQL server\n");

    // Формируем запрос
    char str[100];
    sprintf (str,"INSERT INTO events (press,temp) VALUES (%f,%f);",press,temp);
    if(debug)
        printf("SQL:%s\n",str);
    
    // Выполняем SQL-запрос
    if(mysql_query(&conn, str) != 0)
        perror("Error: can't execute SQL-query\n");
    // Закрываем соединение с сервером базы данных
    mysql_close(&conn);
} 

int main(int argc, char** argv)
{
    // INFO: https://www.kernel.org/doc/Documentation/i2c/dev-interface
 
    if((argc>0) && (strcmp(argv[1],"-d")==0)) {
        debug = true;
        printf("Debug mode on\n");
    }
    
	float press, temp;

    // open Linux I2C device
	i2cOpen();
    // read press and temp from LPS3331AP
    if( i2cLPS331APRead(press,temp) == 0 )
        // and save it into SQL table
        savePressTemp(press,temp);
    // close Linux I2C device
	i2cClose();
    
	return 0;
}