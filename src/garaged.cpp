//
// garage project 
//
// garage daemon
//
// TODO: supervisor http://habrahabr.ru/post/83775/

#include <stdint.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <linux/i2c-dev.h>
//#include <linux/i2c.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <fcntl.h>
#include <mysql/mysql.h>
#include <time.h>

bool debug = false;

// I2C Linux device handle
int g_i2cFile;

time_t rawtime;

char * get_time(void) {
    static char* buf;
    static char* pch;
    
    time (&rawtime);
    buf = ctime(&rawtime);
    pch = strstr (buf,"\n");
    *pch = '\0';
    
    return buf;
}

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

int i2c_LPS331AP_read( float &press, float &temp ) {
// INFO: http://www.st.com/web/en/resource/technical/document/datasheet/DM00036196.pdf
    __u8  res         = 0;
    __s32 writeResult = -1;
    __u8  pressHB     = 0;
    __u8  pressLB     = 0;
    __u8  pressXLB    = 0;
    __u8  tempHB      = 0;
    __u8  tempLB      = 0;
    __s32 pressI      = 0;
    __s16 tempI       = 0;
    
    press = 0;
    temp  = 0;
    
    //
    __u8 ret; 
    struct i2c_client * my_client; 
    struct i2c_adapter * my_adap = i2c_get_adapter(1); // 1 means i2c-1 bus
    my_client = i2c_new_dummy (my_adap, 0x5c); // 0x69 - slave address on i2c bus
    i2c_smbus_write_byte(my_client, 0x0f); 
    //ret = i2c_smbus_read_byte(my_client);
    
    g_i2cFile = my_client;
    
    // open Linux I2C device
    //i2cOpen();
    // set address of the device
    //i2cSetAddress(0x5c);
    // if board installed in system
    res = i2c_smbus_read_byte_data(g_i2cFile, 0x0f);
    if( res == 0xbb ) {
        // power board up
        writeResult = i2c_smbus_write_byte_data(my_client, 0x20, 0x90);
        if( writeResult < 0 ) {
            fprintf(stderr,"Error: Can't power up for LPS331AP\n");
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
            fprintf(stderr,"Error: Can't power down for LPS331AP\n");
            return -3;
        }
        if(debug) {
            printf("%s read 0x%.2x%.2x%.2x 0x%.2x%.2x ",get_time(),pressHB,pressLB,pressXLB,tempHB,tempLB); 
        }
        pressI = pressHB * 0x10000 + pressLB * 0x100 + pressXLB;
        tempI = tempHB * 0x100 + tempLB;
        if(debug) {
            printf("convert ->  0x%.6x 0x%.4x %i %i ",pressI,tempI,pressI,tempI); 
        }
        press = (float)pressI / 4096;
        temp = 42.5 + ( (float)tempI / 480 );
        if(debug) {
            printf("convert-> %f %f\n",press,temp); 
        }
        return 0;
    } else {
        fprintf(stderr,"Error: No LPS331AP device found\n");
        return -1;
    }
    // close Linux I2C device
    //i2cClose();
}

void savePressTemp(float press, float temp) {
    // Дескриптор соединения
    MYSQL conn;
    // Получаем дескриптор соединения
    if(!mysql_init(&conn)) {
        fprintf(stderr,"Error: can't create MySQL-descriptor\n");
    } else {
        // Устанавливаем соединение с базой данных
        if(!mysql_real_connect(&conn,
                             "localhost",
                             "barometer",
                             "pass",
                             "garage",
                             0,
                             NULL,
                             0)) {
            fprintf(stderr,"Error: can't connect to MySQL server\n");
            mysql_close(&conn);
            return;
        }
        // Формируем запрос
        char query[100];
        sprintf (query,"INSERT INTO events (press,temp) VALUES (%f,%f);",press,temp);
        if(debug) {
            printf("%s Exec SQL:%s\n",get_time(),query);
        }
        // Выполняем SQL-запрос
        if(mysql_query(&conn, query) != 0) {
            fprintf(stderr,"Error: can't execute SQL-query\n");
        }
        // Закрываем соединение с сервером базы данных
        mysql_close(&conn);
    }
}

void process_args(int argc, char** argv) {
    if((argc>1) && (strcmp(argv[1],"-d")==0)) {
        debug = true;
        printf("Debug mode on. argc=%i\n",argc);
        for(int i=0;i<argc;++i) {
            printf("%s argc %i = %s\n",get_time(),i,argv[i]);
        }
    }
}

int main(int argc, char** argv)
{
    // INFO: https://www.kernel.org/doc/Documentation/i2c/dev-interface

    process_args(argc, argv);

    float press, temp;
    float last_press = 0; 
    float last_temp  = 0;
    
    struct tm * timeinfo;
    int last_min = -1;
    
    int idle_time           = 10000000;
    int idle_time_min       = 1000;
    int idle_time_max       = 100000000;
    int idle_time_divider   = 2;
    
    float temp_delta        = 0.01;
    float press_delta       = 0.1;
    int time_delta          = 0;
    
    
    if(debug)
        printf("%s Start daemon\n",get_time());

    while(1) {
        if(debug) {
            printf("%s wake up! idle time %i \n",get_time(),idle_time);
        }
        
        // read press and temp from LPS3331AP
        if( i2c_LPS331AP_read(press,temp) == 0 ) {
            time ( &rawtime );
            timeinfo = localtime ( &rawtime );
            if ( (abs(temp-last_temp) > temp_delta) || (abs(press-last_press) > press_delta) || (abs(timeinfo->tm_min-last_min)>time_delta)) {
	    	    // and save it into SQL table
            	savePressTemp(press,temp);
                last_press = press;
                last_temp  = temp;
                last_min   = timeinfo->tm_min;
                idle_time  = idle_time / idle_time_divider;
                if(idle_time < idle_time_min) idle_time = idle_time_min;
	       } else {
                idle_time  = idle_time * idle_time_divider;
                if(idle_time > idle_time_max) idle_time = idle_time_max;
            }
	    } else {
            printf("%s ******** Error ********** i2c \n",get_time());    
        }
        /*
        if(debug) {
            printf("%s idle time %i\n",get_time(),idle_time);
        }*/
        usleep (idle_time);
    }
        
    return 0;
}
